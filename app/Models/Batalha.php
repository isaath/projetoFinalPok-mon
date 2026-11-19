<?php

namespace App\Models;

use PDO;
use RuntimeException;
use Throwable;

class Batalha
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Carrega as vitórias do jogador e indica quais níveis estão acessíveis.
     *
     * Os níveis vencidos continuam liberados para repetição. O primeiro nível
     * ainda não vencido também fica liberado para permitir a próxima batalha.
     */
    public function carregarProgressoNiveis(int $idUsuario): array
    {
        // Busca os oito líderes e junta as vitórias somente deste jogador.
        $consulta = $this->db->prepare(
            'SELECT treinador.nivel_jornada,
                    treinador.nome AS nome_treinador,
                    treinador.nome_insignia,
                    tipo.nome AS especialidade,
                    COALESCE(progresso.vitorias, 0) AS vitorias
             FROM treinadores AS treinador
             INNER JOIN tipos AS tipo
                ON tipo.id_tipo = treinador.id_tipo_especialidade
             LEFT JOIN treinadores_progresso AS progresso
                ON progresso.id_treinador = treinador.id_treinador
               AND progresso.id_usuario = :id_usuario
             WHERE treinador.nivel_jornada BETWEEN 1 AND 8
             ORDER BY treinador.nivel_jornada'
        );
        $consulta->execute(['id_usuario' => $idUsuario]);
        $linhas = $consulta->fetchAll(PDO::FETCH_ASSOC);

        // A jornada precisa ter os oito líderes cadastrados para montar o mapa.
        if (count($linhas) !== 8) {
            throw new RuntimeException('O banco não possui os oito níveis da jornada.');
        }

        // O nível 1 começa liberado; as próximas fases dependem da vitória anterior.
        $proximoNivel = 1;
        $progresso = [];

        foreach ($linhas as $linha) {
            $nivel = (int) $linha['nivel_jornada'];
            $vitorias = (int) $linha['vitorias'];

            // Níveis anteriores e o próximo nível podem ser acessados.
            $liberado = $nivel <= $proximoNivel;

            // Guarda os dados que a tela precisa para mostrar cada treinador.
            $progresso[$nivel] = [
                'nome' => (string) $linha['nome_treinador'],
                'insignia' => (string) $linha['nome_insignia'],
                'especialidade' => (string) $linha['especialidade'],
                'vitorias' => $vitorias,
                'liberado' => $liberado,
            ];

            // Só avança para a próxima fase quando o nível atual já foi vencido.
            if ($nivel === $proximoNivel && $vitorias > 0) {
                $proximoNivel++;
            }
        }

        return $progresso;
    }

    /**
     * Confere se o jogador pode abrir o nível informado.
     */
    public function nivelEstaLiberado(int $idUsuario, int $nivel): bool
    {
        // Números fora da jornada nunca correspondem a uma fase válida.
        if ($nivel < 1 || $nivel > 8) {
            return false;
        }

        // Usa a mesma regra de progressão exibida no mapa.
        $progresso = $this->carregarProgressoNiveis($idUsuario);

        return $progresso[$nivel]['liberado'] ?? false;
    }

    public function carregarPrevia(int $idUsuario, int $nivel): array
    {
        if ($nivel < 1 || $nivel > 8) {
            throw new RuntimeException('O nível informado não pertence à jornada de Kanto.');
        }

        $pokemonsDoUsuario = (new TimePokemon($this->db))->listarDoUsuario($idUsuario);
        $pokemonInicial = null;

        foreach ($pokemonsDoUsuario as $pokemon) {
            if ((int) $pokemon['posicao'] === 1) {
                $pokemonInicial = $pokemon;
                break;
            }
        }

        $consulta = $this->db->prepare(
            'SELECT treinador.id_treinador,
                    treinador.nivel_jornada,
                    treinador.nome AS nome_treinador,
                    treinador.nome_insignia,
                    especialidade.nome AS especialidade,
                    pokemon.id_pokemon,
                    pokemon.nome,
                    pokemon.nome AS nome_pokemon,
                    pokemon.hp_maximo AS hp_atual,
                    pokemon.hp_maximo,
                    pokemon.forca_ataque,
                    pokemon.defesa,
                    pokemon.ataque_especial,
                    pokemon.defesa_especial,
                    pokemon.velocidade,
                    pokemon.id_tipo,
                    COALESCE(
                        (
                            SELECT GROUP_CONCAT(tipo.nome ORDER BY pokemon_tipo.ordem SEPARATOR \'|\')
                            FROM pokemon_tipos AS pokemon_tipo
                            INNER JOIN tipos AS tipo ON tipo.id_tipo = pokemon_tipo.id_tipo
                            WHERE pokemon_tipo.id_pokemon = pokemon.id_pokemon
                        ),
                        tipo_principal.nome
                    ) AS tipos,
                    treinador_pokemon.ordem_batalha,
                    treinador_pokemon.ordem_captura
             FROM treinadores AS treinador
             INNER JOIN tipos AS especialidade
                ON especialidade.id_tipo = treinador.id_tipo_especialidade
             INNER JOIN treinador_pokemons AS treinador_pokemon
                ON treinador_pokemon.id_treinador = treinador.id_treinador
             INNER JOIN pokemons AS pokemon
                ON pokemon.id_pokemon = treinador_pokemon.id_pokemon
             LEFT JOIN tipos AS tipo_principal
                ON tipo_principal.id_tipo = pokemon.id_tipo
             WHERE treinador.nivel_jornada = :nivel
             ORDER BY treinador_pokemon.ordem_batalha'
        );
        $consulta->execute(['nivel' => $nivel]);
        $equipeTreinador = $consulta->fetchAll(PDO::FETCH_ASSOC);

        $quantidadeEsperada = $nivel >= 5 ? 4 : 3;
        if (count($equipeTreinador) !== $quantidadeEsperada) {
            throw new RuntimeException(
                'A equipe do treinador do nível ' . $nivel
                . ' não possui os ' . $quantidadeEsperada . ' Pokémon esperados.'
            );
        }

        $primeiro = $equipeTreinador[0];

        return [
            'treinador' => [
                'id_treinador' => (int) $primeiro['id_treinador'],
                'nivel_jornada' => (int) $primeiro['nivel_jornada'],
                'nome' => $primeiro['nome_treinador'],
                'nome_insignia' => $primeiro['nome_insignia'],
                'especialidade' => $primeiro['especialidade'],
            ],
            'pokemon_jogador' => $pokemonInicial,
            'pokemon_adversario' => $primeiro,
            'equipe_treinador' => $equipeTreinador,
        ];
    }

    public function carregarDadosCombate(int $idUsuario, int $nivel): array
    {
        $previa = $this->carregarPrevia($idUsuario, $nivel);
        $equipeJogador = $this->carregarEquipeJogador($idUsuario);

        if (count($equipeJogador) !== 3) {
            throw new RuntimeException('O time ativo precisa conter exatamente três Pokémon.');
        }

        foreach ($equipeJogador as &$pokemon) {
            $pokemon['movimentos'] = $this->carregarMovimentos((int) $pokemon['id_pokemon']);
            $pokemon = $this->prepararCombatente($pokemon);
        }
        unset($pokemon);

        foreach ($previa['equipe_treinador'] as &$pokemon) {
            $pokemon['id_time_pokemon'] = null;
            $pokemon['hp_atual'] = (int) $pokemon['hp_maximo'];
            $pokemon['movimentos'] = $this->carregarMovimentos((int) $pokemon['id_pokemon']);
            $pokemon = $this->prepararCombatente($pokemon);
        }
        unset($pokemon);

        $indiceInicial = null;
        foreach ($equipeJogador as $indice => $pokemon) {
            if ((int) $pokemon['hp'] > 0) {
                $indiceInicial = $indice;
                break;
            }
        }

        if ($indiceInicial === null) {
            throw new RuntimeException(
                'Todos os Pokémon ativos estão desmaiados. Visite o Pokecenter antes de batalhar.'
            );
        }

        $adversarios = $previa['equipe_treinador'];
        $estado = [
            'id_usuario' => $idUsuario,
            'nivel' => $nivel,
            'id_treinador' => (int) $previa['treinador']['id_treinador'],
            'treinador' => $previa['treinador'],
            'jogador' => [
                'equipe' => $equipeJogador,
                'ativo' => $indiceInicial,
            ],
            'adversario' => [
                'equipe' => $adversarios,
                'ativo' => 0,
            ],
            'turnos' => 0,
            'finalizada' => false,
            'resultado' => null,
            'recompensa' => 0,
            'capturas_liberadas' => 0,
            'resultado_salvo' => false,
            'mensagens' => [
                $previa['treinador']['nome'] . ' enviou '
                    . $adversarios[0]['nome'] . ' para a batalha.',
                'Você enviou ' . $equipeJogador[$indiceInicial]['nome'] . '.',
            ],
        ];

        return $estado;
    }

    public function registrarResultado(int $idUsuario, array $batalha): array
    {
        if (
            empty($batalha['finalizada'])
            || !in_array($batalha['resultado'] ?? null, ['vitoria', 'derrota'], true)
            || (int) ($batalha['id_usuario'] ?? 0) !== $idUsuario
        ) {
            throw new RuntimeException('O resultado da batalha não é válido para registro.');
        }

        $this->db->beginTransaction();

        try {
            $bloquearUsuario = $this->db->prepare(
                'SELECT id_usuario
                 FROM usuarios
                 WHERE id_usuario = :id_usuario
                 FOR UPDATE'
            );
            $bloquearUsuario->execute(['id_usuario' => $idUsuario]);
            if ($bloquearUsuario->fetchColumn() === false) {
                throw new RuntimeException('O jogador da batalha não foi encontrado.');
            }

            $treinador = $this->db->prepare(
                'SELECT id_treinador
                 FROM treinadores
                 WHERE id_treinador = :id_treinador
                   AND nivel_jornada = :nivel
                 FOR UPDATE'
            );
            $treinador->execute([
                'id_treinador' => (int) $batalha['id_treinador'],
                'nivel' => (int) $batalha['nivel'],
            ]);
            if ($treinador->fetchColumn() === false) {
                throw new RuntimeException('O treinador do resultado não corresponde ao nível.');
            }

            $this->salvarHpDaEquipe($idUsuario, $batalha['jogador']['equipe']);

            $vitoria = $batalha['resultado'] === 'vitoria';
            $recompensa = 0;
            $capturasLiberadas = 0;
            if ($vitoria) {
                $progresso = $this->db->prepare(
                    'SELECT vitorias
                     FROM treinadores_progresso
                     WHERE id_usuario = :id_usuario
                       AND id_treinador = :id_treinador
                     FOR UPDATE'
                );
                $progresso->execute([
                    'id_usuario' => $idUsuario,
                    'id_treinador' => (int) $batalha['id_treinador'],
                ]);
                $vitoriasAnteriores = $progresso->fetchColumn();
                $vitoriasAnteriores = $vitoriasAnteriores === false
                    ? 0
                    : (int) $vitoriasAnteriores;

                $recompensas = [
                    1 => [50, 20], 2 => [70, 25], 3 => [90, 30], 4 => [110, 35],
                    5 => [130, 40], 6 => [150, 45], 7 => [170, 50], 8 => [190, 55],
                ];
                $recompensa = $recompensas[(int) $batalha['nivel']][$vitoriasAnteriores > 0 ? 1 : 0];

                $quantidadeAdversarios = count($batalha['adversario']['equipe']);
                $capturasLiberadas = min($vitoriasAnteriores + 1, $quantidadeAdversarios);
                $salvarProgresso = $this->db->prepare(
                    'INSERT INTO treinadores_progresso
                        (
                            id_usuario, id_treinador, vitorias,
                            capturas_desbloqueadas,
                            data_primeira_vitoria, data_ultima_vitoria
                        )
                     VALUES
                        (:id_usuario, :id_treinador, 1, :capturas, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                     ON DUPLICATE KEY UPDATE
                        vitorias = vitorias + 1,
                        capturas_desbloqueadas = GREATEST(
                            capturas_desbloqueadas,
                            VALUES(capturas_desbloqueadas)
                        ),
                        data_ultima_vitoria = CURRENT_TIMESTAMP'
                );
                $salvarProgresso->execute([
                    'id_usuario' => $idUsuario,
                    'id_treinador' => (int) $batalha['id_treinador'],
                    'capturas' => $capturasLiberadas,
                ]);

                $creditarRecompensa = $this->db->prepare(
                    'UPDATE usuarios
                     SET dinheiro = dinheiro + :recompensa
                     WHERE id_usuario = :id_usuario'
                );
                $creditarRecompensa->execute([
                    'recompensa' => $recompensa,
                    'id_usuario' => $idUsuario,
                ]);
            }

            $salvarBatalha = $this->db->prepare(
                'INSERT INTO batalhas (turnos, dinheiro_ganho, resultado)
                 VALUES (:turnos, :dinheiro_ganho, :resultado)'
            );
            $salvarBatalha->execute([
                'turnos' => (int) $batalha['turnos'],
                'dinheiro_ganho' => $recompensa,
                'resultado' => $batalha['resultado'],
            ]);
            $idBatalha = (int) $this->db->lastInsertId();

            $relacionarTreinador = $this->db->prepare(
                'INSERT INTO batalha_treinadores (id_batalha, id_treinador)
                 VALUES (:id_batalha, :id_treinador)'
            );
            $relacionarTreinador->execute([
                'id_batalha' => $idBatalha,
                'id_treinador' => (int) $batalha['id_treinador'],
            ]);

            $registrarPokemon = $this->db->prepare(
                'INSERT INTO batalha_pokemons
                    (hp_final, desmaiou, id_usuario, id_batalha, id_pokemon)
                 VALUES
                    (:hp_final, :desmaiou, :id_usuario, :id_batalha, :id_pokemon)'
            );
            foreach ($batalha['jogador']['equipe'] as $pokemon) {
                $registrarPokemon->execute([
                    'hp_final' => (int) $pokemon['hp'],
                    'desmaiou' => (int) $pokemon['hp'] <= 0 ? 1 : 0,
                    'id_usuario' => $idUsuario,
                    'id_batalha' => $idBatalha,
                    'id_pokemon' => (int) $pokemon['id_pokemon'],
                ]);
            }

            $this->db->commit();

            return [
                'recompensa' => $recompensa,
                'capturas_liberadas' => $capturasLiberadas,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    private function carregarEquipeJogador(int $idUsuario): array
    {
        $consulta = $this->db->prepare(
            'SELECT tp.id_time_pokemon,
                    tp.posicao,
                    pokemon.id_pokemon,
                    pokemon.nome,
                    COALESCE(estado.hp_atual, pokemon.hp) AS hp_atual,
                    pokemon.hp_maximo,
                    pokemon.forca_ataque,
                    pokemon.defesa,
                    pokemon.ataque_especial,
                    pokemon.defesa_especial,
                    pokemon.velocidade,
                    COALESCE(
                        (
                            SELECT GROUP_CONCAT(tipo.nome ORDER BY relacao.ordem SEPARATOR \'|\')
                            FROM pokemon_tipos AS relacao
                            INNER JOIN tipos AS tipo
                                ON tipo.id_tipo = relacao.id_tipo
                            WHERE relacao.id_pokemon = pokemon.id_pokemon
                        ),
                        tipo_principal.nome
                    ) AS tipos
             FROM time_pokemons AS tp
             INNER JOIN times AS time_usuario
                ON time_usuario.id_time = tp.id_time
             INNER JOIN pokemons AS pokemon
                ON pokemon.id_pokemon = tp.id_pokemon
             LEFT JOIN time_pokemons_estado AS estado
                ON estado.id_time_pokemon = tp.id_time_pokemon
             LEFT JOIN tipos AS tipo_principal
                ON tipo_principal.id_tipo = pokemon.id_tipo
             WHERE time_usuario.id_usuario = :id_usuario
               AND time_usuario.id_time = (
                    SELECT MIN(id_time)
                    FROM times
                    WHERE id_usuario = :id_usuario_time
               )
               AND tp.posicao BETWEEN 1 AND 3
             ORDER BY tp.posicao'
        );
        $consulta->execute([
            'id_usuario' => $idUsuario,
            'id_usuario_time' => $idUsuario,
        ]);
        $equipe = $consulta->fetchAll(PDO::FETCH_ASSOC);

        foreach ($equipe as &$pokemon) {
            $pokemon['id_time_pokemon'] = (int) $pokemon['id_time_pokemon'];
            $pokemon['id_pokemon'] = (int) $pokemon['id_pokemon'];
            $pokemon['posicao'] = (int) $pokemon['posicao'];
            $pokemon['hp_atual'] = (int) $pokemon['hp_atual'];
            $pokemon['hp_maximo'] = (int) $pokemon['hp_maximo'];
            $pokemon['tipos'] = $this->separarTipos($pokemon['tipos']);
        }
        unset($pokemon);

        return $equipe;
    }

    private function carregarMovimentos(int $idPokemon): array
    {
        $consulta = $this->db->prepare(
            'SELECT movimento.id_movimento,
                    movimento.nome,
                    tipo.nome AS tipo,
                    movimento.categoria,
                    movimento.potencia,
                    movimento.precisao,
                    movimento.prioridade,
                    movimento.efeito,
                    movimento.chance_efeito
             FROM pokemon_movimentos AS relacao
             INNER JOIN movimentos AS movimento
                ON movimento.id_movimento = relacao.id_movimento
             INNER JOIN tipos AS tipo
                ON tipo.id_tipo = movimento.id_tipo
             WHERE relacao.id_pokemon = :id_pokemon
             ORDER BY relacao.ordem'
        );
        $consulta->execute(['id_pokemon' => $idPokemon]);

        $movimentos = $consulta->fetchAll(PDO::FETCH_ASSOC);
        foreach ($movimentos as &$movimento) {
            foreach ([
                'id_movimento',
                'potencia',
                'precisao',
                'prioridade',
                'chance_efeito',
            ] as $campo) {
                $movimento[$campo] = (int) $movimento[$campo];
            }
        }
        unset($movimento);

        return $movimentos;
    }

    private function prepararCombatente(array $pokemon): array
    {
        $pokemon['id_pokemon'] = (int) $pokemon['id_pokemon'];
        $pokemon['hp_maximo'] = (int) $pokemon['hp_maximo'];
        $pokemon['hp'] = max(
            0,
            min((int) $pokemon['hp_atual'], (int) $pokemon['hp_maximo'])
        );
        $pokemon['forca_ataque'] = (int) $pokemon['forca_ataque'];
        $pokemon['defesa'] = (int) $pokemon['defesa'];
        $pokemon['ataque_especial'] = (int) $pokemon['ataque_especial'];
        $pokemon['defesa_especial'] = (int) $pokemon['defesa_especial'];
        $pokemon['velocidade'] = (int) $pokemon['velocidade'];
        $pokemon['tipos'] = is_array($pokemon['tipos'])
            ? $pokemon['tipos']
            : $this->separarTipos($pokemon['tipos'] ?? null);
        $pokemon['status'] = null;
        $pokemon['status_turnos'] = 0;
        $pokemon['toxico_contagem'] = 0;
        $pokemon['protegido'] = false;
        $pokemon['protecao_usada'] = false;
        $pokemon['cura_usada'] = false;
        $pokemon['recuo'] = false;
        $pokemon['estagios'] = [
            'ataque' => 0,
            'defesa' => 0,
            'ataque_especial' => 0,
            'defesa_especial' => 0,
            'velocidade' => 0,
            'precisao' => 0,
        ];

        if ($pokemon['movimentos'] === []) {
            throw new RuntimeException(
                'O Pokémon ' . $pokemon['nome'] . ' não possui movimentos cadastrados.'
            );
        }

        return $pokemon;
    }

    private function separarTipos(?string $tipos): array
    {
        return is_string($tipos) && $tipos !== ''
            ? explode('|', $tipos)
            : [];
    }

    private function salvarHpDaEquipe(int $idUsuario, array $equipe): void
    {
        $proprietario = $this->db->prepare(
            'SELECT pokemon.id_pokemon
             FROM time_pokemons AS time_pokemon
             INNER JOIN times AS time_usuario
                ON time_usuario.id_time = time_pokemon.id_time
             INNER JOIN pokemons AS pokemon
                ON pokemon.id_pokemon = time_pokemon.id_pokemon
             WHERE time_usuario.id_usuario = :id_usuario
               AND time_pokemon.id_time_pokemon = :id_time_pokemon
             FOR UPDATE'
        );
        $salvarHp = $this->db->prepare(
            'INSERT INTO time_pokemons_estado (id_time_pokemon, hp_atual)
             VALUES (:id_time_pokemon, :hp_atual)
             ON DUPLICATE KEY UPDATE hp_atual = VALUES(hp_atual)'
        );

        foreach ($equipe as $pokemon) {
            $idTimePokemon = (int) ($pokemon['id_time_pokemon'] ?? 0);
            $proprietario->execute([
                'id_usuario' => $idUsuario,
                'id_time_pokemon' => $idTimePokemon,
            ]);
            if ((int) $proprietario->fetchColumn() !== (int) $pokemon['id_pokemon']) {
                throw new RuntimeException('Um Pokémon da equipe não pertence mais a este jogador.');
            }

            $salvarHp->execute([
                'id_time_pokemon' => $idTimePokemon,
                'hp_atual' => max(
                    0,
                    min((int) $pokemon['hp'], (int) $pokemon['hp_maximo'])
                ),
            ]);
        }
    }
}
