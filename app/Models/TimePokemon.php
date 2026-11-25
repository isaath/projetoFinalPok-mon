<?php

namespace App\Models;

use PDO;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

/** Consulta e reorganiza os Pokémon que pertencem aos times do jogador. */
class TimePokemon
{
    private PDO $db;

    /** Mantém a conexão PDO para leituras e alterações persistentes. */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Busca espécies, posições e HP individual para montar a tela do time. */
    public function listarDoUsuario(int $idUsuario): array
    {
        $consulta = $this->db->prepare(
            'SELECT membro.id_time_pokemon, membro.id_time, membro.id_pokemon,
                    membro.posicao, membro.nome, membro.tipo,
                    membro.hp_atual, membro.hp_maximo,
                    destino.nome AS nome_evolucao,
                    COALESCE(inventario.quantidade, 0) AS quantidade_item_evolutivo,
                    EXISTS (
                        SELECT 1
                        FROM time_pokemons AS existente
                        WHERE existente.id_time = membro.id_time
                          AND existente.id_pokemon = destino.id_pokemon
                          AND existente.id_time_pokemon <> membro.id_time_pokemon
                    ) AS evolucao_ja_possuida
             FROM vw_time_pokemons AS membro
             LEFT JOIN pokemon_evolucoes AS evolucao
                ON evolucao.id_pokemon_origem = membro.id_pokemon
             LEFT JOIN pokemons AS destino
                ON destino.id_pokemon = evolucao.id_pokemon_destino
             LEFT JOIN itens_loja AS item
                ON item.nome = :nome_item
               AND item.tipo = :tipo_item
             LEFT JOIN inventario
                ON inventario.id_item = item.id_item
               AND inventario.id_usuario = membro.id_usuario
             WHERE membro.id_usuario = :id_usuario
             ORDER BY membro.id_time, membro.posicao'
        );
        $consulta->execute([
            'nome_item' => 'Item Evolutivo',
            'tipo_item' => 'evolucao',
            'id_usuario' => $idUsuario,
        ]);

        $pokemons = $consulta->fetchAll(PDO::FETCH_ASSOC);
        foreach ($pokemons as &$pokemon) {
            foreach ([
                'id_time_pokemon',
                'id_time',
                'id_pokemon',
                'posicao',
                'hp_atual',
                'hp_maximo',
                'quantidade_item_evolutivo',
                'evolucao_ja_possuida',
            ] as $campo) {
                $pokemon[$campo] = (int) $pokemon[$campo];
            }
        }
        unset($pokemon);

        return $pokemons;
    }

    /**
     * Confirma que o jogador possui o Pokémon, um item e um próximo estágio cadastrado.
     *
     * @return array{status:string, nome_evolucao?:string}
     */
    public function prepararEvolucao(int $idUsuario, int $idTimePokemon): array
    {
        if ($idUsuario < 1 || $idTimePokemon < 1) {
            return ['status' => 'dados_invalidos'];
        }

        $pokemon = $this->buscarPokemonDoUsuario($idUsuario, $idTimePokemon);
        if ($pokemon === false) {
            return ['status' => 'pokemon_nao_encontrado'];
        }

        $destino = $this->buscarProximaEvolucao((int) $pokemon['id_pokemon']);
        if ($destino === false) {
            return ['status' => 'nao_elegivel'];
        }

        $consultaItem = $this->db->prepare(
            'SELECT inventario.quantidade
             FROM inventario
             INNER JOIN itens_loja AS item
                ON item.id_item = inventario.id_item
             WHERE inventario.id_usuario = :id_usuario
               AND item.nome = :nome_item
               AND item.tipo = :tipo_item
               AND inventario.quantidade > 0
             LIMIT 1'
        );
        $consultaItem->execute([
            'id_usuario' => $idUsuario,
            'nome_item' => 'Item Evolutivo',
            'tipo_item' => 'evolucao',
        ]);

        if ($consultaItem->fetchColumn() === false) {
            return ['status' => 'sem_item_evolutivo'];
        }

        return [
            'status' => 'pronto',
            'nome_evolucao' => (string) $destino['nome'],
        ];
    }

    /**
     * Atualiza atributos da espécie com a PokeAPI, evolui o membro e consome um item.
     */
    public function evoluirComItem(
        int $idUsuario,
        int $idTimePokemon,
        array $dadosApi
    ): string {
        $this->validarAtributosApi($dadosApi);

        if ($idUsuario < 1 || $idTimePokemon < 1) {
            return 'dados_invalidos';
        }

        $this->db->beginTransaction();

        try {
            if (!$this->bloquearUsuario($idUsuario)) {
                $this->db->commit();
                return 'usuario_nao_encontrado';
            }

            $pokemon = $this->buscarPokemonDoUsuario($idUsuario, $idTimePokemon);
            if ($pokemon === false) {
                $this->db->commit();
                return 'pokemon_nao_encontrado';
            }

            $destino = $this->buscarProximaEvolucao((int) $pokemon['id_pokemon']);
            if ($destino === false) {
                $this->db->commit();
                return 'nao_elegivel';
            }

            if (strtolower((string) $destino['nome']) !== strtolower($dadosApi['nome'])) {
                throw new UnexpectedValueException(
                    'Os dados recebidos da PokeAPI não correspondem à evolução cadastrada.'
                );
            }

            $consultaItem = $this->db->prepare(
                'SELECT inventario.id_inventario
                 FROM inventario
                 INNER JOIN itens_loja AS item
                    ON item.id_item = inventario.id_item
                 WHERE inventario.id_usuario = :id_usuario
                   AND item.nome = :nome_item
                   AND item.tipo = :tipo_item
                   AND inventario.quantidade > 0
                 LIMIT 1
                 FOR UPDATE'
            );
            $consultaItem->execute([
                'id_usuario' => $idUsuario,
                'nome_item' => 'Item Evolutivo',
                'tipo_item' => 'evolucao',
            ]);
            $itemInventario = $consultaItem->fetch(PDO::FETCH_ASSOC);
            if ($itemInventario === false) {
                $this->db->commit();
                return 'sem_item_evolutivo';
            }

            $consultaDuplicado = $this->db->prepare(
                'SELECT id_time_pokemon
                 FROM time_pokemons
                 WHERE id_time = :id_time
                   AND id_pokemon = :id_pokemon
                   AND id_time_pokemon <> :id_time_pokemon
                 FOR UPDATE'
            );
            $consultaDuplicado->execute([
                'id_time' => (int) $pokemon['id_time'],
                'id_pokemon' => (int) $destino['id_pokemon'],
                'id_time_pokemon' => $idTimePokemon,
            ]);
            if ($consultaDuplicado->fetchColumn() !== false) {
                $this->db->commit();
                return 'evolucao_ja_possuida';
            }

            $tipos = $this->obterIdsTiposApi($dadosApi['tipos']);
            $this->atualizarAtributosEspecie(
                (int) $destino['id_pokemon'],
                $dadosApi,
                $tipos
            );

            $atualizarEspecieDoMembro = $this->db->prepare(
                'UPDATE time_pokemons
                 SET id_pokemon = :id_pokemon_destino
                 WHERE id_time_pokemon = :id_time_pokemon
                   AND id_time = :id_time'
            );
            $atualizarEspecieDoMembro->execute([
                'id_pokemon_destino' => (int) $destino['id_pokemon'],
                'id_time_pokemon' => $idTimePokemon,
                'id_time' => (int) $pokemon['id_time'],
            ]);
            if ($atualizarEspecieDoMembro->rowCount() !== 1) {
                throw new RuntimeException('Não foi possível atualizar o Pokémon evoluído.');
            }

            $restaurarHp = $this->db->prepare(
                'INSERT INTO time_pokemons_estado (id_time_pokemon, hp_atual)
                 VALUES (:id_time_pokemon, :hp_atual)
                 ON DUPLICATE KEY UPDATE hp_atual = VALUES(hp_atual)'
            );
            $restaurarHp->execute([
                'id_time_pokemon' => $idTimePokemon,
                'hp_atual' => $dadosApi['hp'],
            ]);

            $consumirItem = $this->db->prepare(
                'UPDATE inventario
                 SET quantidade = quantidade - 1
                 WHERE id_inventario = :id_inventario
                   AND quantidade > 0'
            );
            $consumirItem->execute([
                'id_inventario' => (int) $itemInventario['id_inventario'],
            ]);
            if ($consumirItem->rowCount() !== 1) {
                throw new RuntimeException('Não foi possível consumir o Item Evolutivo.');
            }

            $this->db->commit();
            return 'evoluido';
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * Move um Pokémon ativo e desloca os demais para preservar a ordem relativa.
     */
    public function moverPrioridade(
        int $idUsuario,
        int $idTimePokemon,
        int $posicaoDestino
    ): string {
        if ($idTimePokemon < 1 || $posicaoDestino < 1 || $posicaoDestino > 3) {
            return 'dados_invalidos';
        }

        $this->db->beginTransaction();

        try {
            // O bloqueio serializa alterações concorrentes ao mesmo time.
            if (!$this->bloquearUsuario($idUsuario)) {
                $this->db->commit();
                return 'usuario_nao_encontrado';
            }

            $pokemon = $this->buscarPokemonDoUsuario($idUsuario, $idTimePokemon);

            if ($pokemon === false) {
                $this->db->commit();
                return 'pokemon_nao_encontrado';
            }

            $posicaoAtual = (int) $pokemon['posicao'];
            if ($posicaoAtual < 1 || $posicaoAtual > 3) {
                $this->db->commit();
                return 'nao_esta_ativo';
            }

            if ($posicaoAtual === $posicaoDestino) {
                $this->db->commit();
                return 'mesma_posicao';
            }

            $idTime = (int) $pokemon['id_time'];
            $posicoes = $this->carregarPosicoes($idTime);

            if (!$this->timeAtivoCompleto($posicoes)) {
                $this->db->commit();
                return 'time_incompleto';
            }

            $this->moverComDeslocamento(
                $idTime,
                $idTimePokemon,
                $posicaoAtual,
                $posicaoDestino,
                $posicoes
            );

            $this->db->commit();
            return 'movido';
        } catch (Throwable $exception) {
            // Evita deixar posições parcialmente atualizadas se uma etapa falhar.
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * Substitui o Pokémon da posição 3 por um da reserva e envia o substituído à reserva.
     */
    public function colocarNaPosicaoTres(int $idUsuario, int $idTimePokemon): string
    {
        if ($idTimePokemon < 1) {
            return 'dados_invalidos';
        }

        $this->db->beginTransaction();

        try {
            if (!$this->bloquearUsuario($idUsuario)) {
                $this->db->commit();
                return 'usuario_nao_encontrado';
            }

            $pokemon = $this->buscarPokemonDoUsuario($idUsuario, $idTimePokemon);

            if ($pokemon === false) {
                $this->db->commit();
                return 'pokemon_nao_encontrado';
            }

            $posicaoReserva = (int) $pokemon['posicao'];
            if ($posicaoReserva <= 3) {
                $this->db->commit();
                return 'ja_esta_ativo';
            }

            $idTime = (int) $pokemon['id_time'];
            $posicoes = $this->carregarPosicoes($idTime);

            if (!$this->timeAtivoCompleto($posicoes)) {
                $this->db->commit();
                return 'time_incompleto';
            }

            $idPokemonTerceiro = $posicoes[3];
            // A posição temporária 0 evita colidir com a chave única durante a troca.
            $this->atualizarPosicao($idTime, $idTimePokemon, 0);
            $this->atualizarPosicao($idTime, $idPokemonTerceiro, $posicaoReserva);
            $this->atualizarPosicao($idTime, $idTimePokemon, 3);

            $this->db->commit();
            return 'colocado';
        } catch (Throwable $exception) {
            // Reverte a troca inteira se qualquer atualização não puder ser concluída.
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    /** Bloqueia a linha do usuário e confirma que ela ainda existe. */
    private function bloquearUsuario(int $idUsuario): bool
    {
        $consulta = $this->db->prepare(
            'SELECT id_usuario FROM usuarios WHERE id_usuario = :id_usuario FOR UPDATE'
        );
        $consulta->execute(['id_usuario' => $idUsuario]);

        return $consulta->fetchColumn() !== false;
    }

    /** Busca um membro somente se ele pertencer a um time do usuário autenticado. */
    private function buscarPokemonDoUsuario(int $idUsuario, int $idTimePokemon)
    {
        $consulta = $this->db->prepare(
            'SELECT tp.id_time, tp.id_pokemon, tp.posicao
             FROM time_pokemons tp
             INNER JOIN times t ON t.id_time = tp.id_time
             WHERE t.id_usuario = :id_usuario
               AND tp.id_time_pokemon = :id_time_pokemon
             FOR UPDATE'
        );
        $consulta->execute([
            'id_usuario' => $idUsuario,
            'id_time_pokemon' => $idTimePokemon,
        ]);

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    /** Localiza a próxima espécie cadastrada para o Pokémon de origem. */
    private function buscarProximaEvolucao(int $idPokemon)
    {
        $consulta = $this->db->prepare(
            'SELECT destino.id_pokemon, destino.nome
             FROM pokemon_evolucoes AS evolucao
             INNER JOIN pokemons AS destino
                ON destino.id_pokemon = evolucao.id_pokemon_destino
             WHERE evolucao.id_pokemon_origem = :id_pokemon'
        );
        $consulta->execute(['id_pokemon' => $idPokemon]);

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    /** Confirma o formato e os valores mínimos dos atributos recebidos da PokeAPI. */
    private function validarAtributosApi(array $dadosApi): void
    {
        if (!isset($dadosApi['nome']) || !is_string($dadosApi['nome'])) {
            throw new UnexpectedValueException('A PokeAPI não retornou o nome da espécie.');
        }

        foreach ([
            'hp',
            'forca_ataque',
            'defesa',
            'ataque_especial',
            'defesa_especial',
            'velocidade',
        ] as $atributo) {
            if (!isset($dadosApi[$atributo]) || !is_int($dadosApi[$atributo]) || $dadosApi[$atributo] < 1) {
                throw new UnexpectedValueException(
                    'A PokeAPI retornou um atributo inválido: ' . $atributo . '.'
                );
            }
        }

        if (!isset($dadosApi['tipos']) || !is_array($dadosApi['tipos']) || $dadosApi['tipos'] === []) {
            throw new UnexpectedValueException('A PokeAPI não retornou os tipos da espécie.');
        }
    }

    /** Converte os nomes ingleses da PokeAPI para os tipos cadastrados no projeto. */
    private function obterIdsTiposApi(array $tiposApi): array
    {
        $mapaTipos = [
            'normal' => 'Normal',
            'fighting' => 'Lutador',
            'flying' => 'Voador',
            'poison' => 'Veneno',
            'ground' => 'Terra',
            'rock' => 'Pedra',
            'bug' => 'Inseto',
            'ghost' => 'Fantasma',
            'steel' => 'Aco',
            'fire' => 'Fogo',
            'water' => 'Agua',
            'grass' => 'Planta',
            'electric' => 'Eletrico',
            'psychic' => 'Psiquico',
            'ice' => 'Gelo',
            'dragon' => 'Dragao',
            'dark' => 'Sombrio',
        ];
        $consultaTipo = $this->db->prepare(
            'SELECT id_tipo FROM tipos WHERE nome = :nome_tipo'
        );
        $idsTipos = [];

        foreach (array_values($tiposApi) as $nomeTipoApi) {
            if (!is_string($nomeTipoApi) || !isset($mapaTipos[$nomeTipoApi])) {
                throw new UnexpectedValueException(
                    'O tipo retornado pela PokeAPI não é compatível com o projeto.'
                );
            }

            $consultaTipo->execute(['nome_tipo' => $mapaTipos[$nomeTipoApi]]);
            $idTipo = $consultaTipo->fetchColumn();
            if ($idTipo === false) {
                throw new UnexpectedValueException(
                    'O tipo ' . $mapaTipos[$nomeTipoApi] . ' não está cadastrado no banco.'
                );
            }

            $idsTipos[] = (int) $idTipo;
        }

        return array_values(array_unique($idsTipos));
    }

    /** Atualiza dados compartilhados da espécie e sua tipagem consultada na API. */
    private function atualizarAtributosEspecie(
        int $idPokemon,
        array $dadosApi,
        array $idsTipos
    ): void {
        if ($idsTipos === []) {
            throw new UnexpectedValueException('A espécie evoluída precisa ter ao menos um tipo.');
        }

        $atualizarPokemon = $this->db->prepare(
            'UPDATE pokemons
             SET hp = :hp,
                 hp_maximo = :hp_maximo,
                 forca_ataque = :forca_ataque,
                 defesa = :defesa,
                 ataque_especial = :ataque_especial,
                 defesa_especial = :defesa_especial,
                 velocidade = :velocidade,
                 id_tipo = :id_tipo
             WHERE id_pokemon = :id_pokemon'
        );
        $atualizarPokemon->execute([
            'hp' => $dadosApi['hp'],
            'hp_maximo' => $dadosApi['hp'],
            'forca_ataque' => $dadosApi['forca_ataque'],
            'defesa' => $dadosApi['defesa'],
            'ataque_especial' => $dadosApi['ataque_especial'],
            'defesa_especial' => $dadosApi['defesa_especial'],
            'velocidade' => $dadosApi['velocidade'],
            'id_tipo' => $idsTipos[0],
            'id_pokemon' => $idPokemon,
        ]);

        $removerTipos = $this->db->prepare(
            'DELETE FROM pokemon_tipos WHERE id_pokemon = :id_pokemon'
        );
        $removerTipos->execute(['id_pokemon' => $idPokemon]);

        $adicionarTipo = $this->db->prepare(
            'INSERT INTO pokemon_tipos (id_pokemon, id_tipo, ordem)
             VALUES (:id_pokemon, :id_tipo, :ordem)'
        );
        foreach ($idsTipos as $indice => $idTipo) {
            $adicionarTipo->execute([
                'id_pokemon' => $idPokemon,
                'id_tipo' => $idTipo,
                'ordem' => $indice + 1,
            ]);
        }
    }

    /** Mapeia cada posição ocupada ao identificador do Pokémon associado. */
    private function carregarPosicoes(int $idTime): array
    {
        $consulta = $this->db->prepare(
            'SELECT id_time_pokemon, posicao
             FROM time_pokemons
             WHERE id_time = :id_time
             FOR UPDATE'
        );
        $consulta->execute(['id_time' => $idTime]);

        $posicoes = [];
        foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $pokemon) {
            $posicoes[(int) $pokemon['posicao']] = (int) $pokemon['id_time_pokemon'];
        }

        return $posicoes;
    }

    /** Confirma que as três posições de batalha estão ocupadas. */
    private function timeAtivoCompleto(array $posicoes): bool
    {
        return isset($posicoes[1], $posicoes[2], $posicoes[3]);
    }

    /** Reordena a escalação sem deixar posições duplicadas durante a atualização. */
    private function moverComDeslocamento(
        int $idTime,
        int $idTimePokemon,
        int $posicaoAtual,
        int $posicaoDestino,
        array $posicoes
    ): void {
        $this->atualizarPosicao($idTime, $idTimePokemon, 0);

        if ($posicaoAtual > $posicaoDestino) {
            for ($posicao = $posicaoAtual - 1; $posicao >= $posicaoDestino; $posicao--) {
                $this->atualizarPosicao($idTime, $posicoes[$posicao], $posicao + 1);
            }
        } else {
            for ($posicao = $posicaoAtual + 1; $posicao <= $posicaoDestino; $posicao++) {
                $this->atualizarPosicao($idTime, $posicoes[$posicao], $posicao - 1);
            }
        }

        $this->atualizarPosicao($idTime, $idTimePokemon, $posicaoDestino);
    }

    /** Atualiza uma posição e lança erro se nenhuma linha for alterada. */
    private function atualizarPosicao(int $idTime, int $idTimePokemon, int $posicao): void
    {
        $atualizacao = $this->db->prepare(
            'UPDATE time_pokemons
             SET posicao = :posicao
             WHERE id_time = :id_time AND id_time_pokemon = :id_time_pokemon'
        );
        $atualizacao->execute([
            'posicao' => $posicao,
            'id_time' => $idTime,
            'id_time_pokemon' => $idTimePokemon,
        ]);

        if ($atualizacao->rowCount() !== 1) {
            throw new RuntimeException('Não foi possível atualizar a posição do Pokémon.');
        }
    }
}
