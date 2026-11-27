<?php

namespace App\Models;

use PDO;
use RuntimeException;
use Throwable;

/**
 * Na apresentação: este Model carrega treinador, equipe e progresso do banco,
 * depois grava resultado, HP, recompensa e captura em transação. As regras do
 * turno ficam em BatalhaEngine; BatalhaController coordena o fluxo HTTP.
 *
 * A lógica de turnos fica em BatalhaEngine; esta classe consulta e atualiza o banco.
 */
class Batalha
{
    /** Recompensas por nível: primeira vitória e valor de cada repetição. */
    private const RECOMPENSAS = [
        1 => [50, 20], 2 => [70, 25], 3 => [90, 30], 4 => [110, 35],
        5 => [130, 40], 6 => [150, 45], 7 => [170, 50], 8 => [190, 55],
    ];

    private const CHANCE_CAPTURA = 50;

    private PDO $db;

    /** Recebe a conexão compartilhada com o banco de dados. */
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
        // A View fornece a linha de cada ginásio para este jogador, mesmo sem vitórias.
        $consulta = $this->db->prepare(
            'SELECT nivel_jornada, nome_treinador, nome_insignia, especialidade, vitorias
             FROM vw_progresso_ginasios
             WHERE id_usuario = :id_usuario
               AND nivel_jornada BETWEEN 1 AND 8
             ORDER BY nivel_jornada'
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

    /** Carrega os dados do líder e das equipes para iniciar o nível escolhido. */
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

    /**
     * Apaga o progresso jogável do usuário e restaura seu saldo inicial numa transação.
     *
     * O histórico de batalhas permanece; só os dados que definem a jornada atual são limpos.
     */
    public function reiniciarJornada(int $idUsuario): void
    {
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
                throw new RuntimeException('O usuário da jornada não foi encontrado.');
            }

            // Limpa primeiro os registros dependentes para respeitar as relações entre tabelas.
            $limparTentativasCaptura = $this->db->prepare(
                'DELETE FROM batalhas_capturas WHERE id_usuario = :id_usuario'
            );
            $limparTentativasCaptura->execute(['id_usuario' => $idUsuario]);

            $limparCapturas = $this->db->prepare(
                'DELETE FROM capturas_treinadores WHERE id_usuario = :id_usuario'
            );
            $limparCapturas->execute(['id_usuario' => $idUsuario]);

            $limparProgresso = $this->db->prepare(
                'DELETE FROM treinadores_progresso WHERE id_usuario = :id_usuario'
            );
            $limparProgresso->execute(['id_usuario' => $idUsuario]);

            $limparInventario = $this->db->prepare(
                'DELETE FROM inventario WHERE id_usuario = :id_usuario'
            );
            $limparInventario->execute(['id_usuario' => $idUsuario]);

            $limparTimes = $this->db->prepare(
                'DELETE FROM times WHERE id_usuario = :id_usuario'
            );
            $limparTimes->execute(['id_usuario' => $idUsuario]);

            $restaurarSaldo = $this->db->prepare(
                'UPDATE usuarios SET dinheiro = 120 WHERE id_usuario = :id_usuario'
            );
            $restaurarSaldo->execute(['id_usuario' => $idUsuario]);

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * Monta o estado temporário completo que o engine usa durante uma batalha.
     *
     * Inclui atributos, movimentos, status iniciais e índices dos Pokémon ativos.
     */
    public function carregarDadosCombate(int $idUsuario, int $nivel): array
    {
        $previa = $this->carregarPrevia($idUsuario, $nivel);
        $equipeJogador = $this->carregarEquipeJogador($idUsuario);
        // O ajuste vale apenas até a primeira vitória e não altera os dados do catálogo.
        $ajustarBrockInicial = $nivel === 1
            && $this->carregarProgressoNiveis($idUsuario)[1]['vitorias'] === 0;

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
            if ($ajustarBrockInicial) {
                $pokemon['hp_maximo'] = max(
                    1,
                    (int) floor((int) $pokemon['hp_maximo'] * 0.9)
                );
                $pokemon['forca_ataque'] = max(
                    1,
                    (int) floor((int) $pokemon['forca_ataque'] * 0.9)
                );
            }
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
        // Este estado fica na sessão durante a luta; o banco é atualizado ao finalizar.
        $estado = [
            'id_usuario' => $idUsuario,
            'nivel' => $nivel,
            'id_treinador' => (int) $previa['treinador']['id_treinador'],
            'token_execucao' => bin2hex(random_bytes(16)),
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

    /**
     * Persiste o resultado final, o HP, o log, o progresso e a recompensa em uma transação.
     *
     * O token da execução torna a finalização idempotente: repetir a mesma solicitação
     * devolve o resultado já registrado, sem creditar outra recompensa.
     */
    public function registrarResultado(int $idUsuario, array $batalha): array
    {
        if (
            empty($batalha['finalizada'])
            || !in_array($batalha['resultado'] ?? null, ['vitoria', 'derrota'], true)
            || (int) ($batalha['id_usuario'] ?? 0) !== $idUsuario
            || !is_string($batalha['token_execucao'] ?? null)
            || preg_match('/\A[a-f0-9]{32}\z/', $batalha['token_execucao']) !== 1
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

            // Verifica primeiro se uma repetição da requisição já finalizou esta batalha.
            $finalizacaoExistente = $this->db->prepare(
                'SELECT finalizacao.id_batalha,
                        finalizacao.recompensa,
                        finalizacao.capturas_liberadas,
                        batalha.resultado,
                        batalha_treinador.id_treinador,
                        tentativa.resultado AS resultado_captura,
                        tentativa.chance,
                        tentativa.numero_tentativa,
                        pokemon_tentativa.nome AS pokemon_capturado,
                        captura.id_time_pokemon
                 FROM batalhas_finalizacoes AS finalizacao
                 INNER JOIN batalhas AS batalha
                    ON batalha.id_batalha = finalizacao.id_batalha
                 INNER JOIN batalha_treinadores AS batalha_treinador
                    ON batalha_treinador.id_batalha = batalha.id_batalha
                 LEFT JOIN batalhas_capturas AS tentativa
                    ON tentativa.id_batalha = batalha.id_batalha
                 LEFT JOIN capturas_treinadores AS captura
                    ON captura.id_captura = tentativa.id_captura
                 LEFT JOIN pokemons AS pokemon_tentativa
                    ON pokemon_tentativa.id_pokemon = tentativa.id_pokemon
                 WHERE finalizacao.token_execucao = :token_execucao
                   AND finalizacao.id_usuario = :id_usuario
                 FOR UPDATE'
            );
            $finalizacaoExistente->execute([
                'token_execucao' => $batalha['token_execucao'],
                'id_usuario' => $idUsuario,
            ]);
            $resultadoExistente = $finalizacaoExistente->fetch(PDO::FETCH_ASSOC);
            if ($resultadoExistente !== false) {
                if (
                    $resultadoExistente['resultado'] !== $batalha['resultado']
                    || (int) $resultadoExistente['id_treinador'] !== (int) $batalha['id_treinador']
                ) {
                    throw new RuntimeException(
                        'A chave de finalização já pertence a outro resultado de batalha.'
                    );
                }

                $this->db->commit();
                $recompensaPrimeiraVitoria = self::RECOMPENSAS[(int) $batalha['nivel']][0] ?? null;

                return [
                    'id_batalha' => (int) $resultadoExistente['id_batalha'],
                    'recompensa' => (int) $resultadoExistente['recompensa'],
                    'capturas_liberadas' => (int) $resultadoExistente['capturas_liberadas'],
                    'captura' => $this->formatarCaptura($resultadoExistente),
                    'primeira_vitoria' => $resultadoExistente['resultado'] === 'vitoria'
                        && $recompensaPrimeiraVitoria !== null
                        && (int) $resultadoExistente['recompensa'] === $recompensaPrimeiraVitoria,
                ];
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

            // Só uma vitória atualiza progresso, libera captura e credita moedas.
            $vitoria = $batalha['resultado'] === 'vitoria';
            $primeiraVitoria = false;
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

                $primeiraVitoria = $vitoriasAnteriores === 0;
                $recompensa = self::RECOMPENSAS[(int) $batalha['nivel']][$primeiraVitoria ? 0 : 1];

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

            // Registra o combate e relaciona treinador e Pokémon participantes.
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

            $registrarFinalizacao = $this->db->prepare(
                'INSERT INTO batalhas_finalizacoes
                    (token_execucao, id_batalha, id_usuario, recompensa, capturas_liberadas)
                 VALUES
                    (:token_execucao, :id_batalha, :id_usuario, :recompensa, :capturas_liberadas)'
            );
            $registrarFinalizacao->execute([
                'token_execucao' => $batalha['token_execucao'],
                'id_batalha' => $idBatalha,
                'id_usuario' => $idUsuario,
                'recompensa' => $recompensa,
                'capturas_liberadas' => $capturasLiberadas,
            ]);

            $this->db->commit();

            return [
                'id_batalha' => $idBatalha,
                'recompensa' => $recompensa,
                'capturas_liberadas' => $capturasLiberadas,
                'captura' => null,
                'primeira_vitoria' => $primeiraVitoria,
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    /** Retorna o próximo Pokémon liberado sem sortear nem gravar uma tentativa. */
    public function carregarProximaCaptura(int $idUsuario, array $batalha): ?array
    {
        $this->validarBatalhaParaCaptura($idUsuario, $batalha);

        return $this->buscarProximoPokemonCapturavel(
            $idUsuario,
            (int) $batalha['id_treinador'],
            (int) ($batalha['capturas_liberadas'] ?? 0),
            false
        );
    }

    /**
     * Faz e registra uma tentativa de captura após a vitória.
     *
     * A transação e a chave da batalha impedem que recarregar a tela gere outro sorteio.
     */
    public function tentarCaptura(int $idUsuario, array $batalha): ?array
    {
        $this->validarBatalhaParaCaptura($idUsuario, $batalha);
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
                throw new RuntimeException('O jogador da captura não foi encontrado.');
            }

            $finalizacao = $this->db->prepare(
                'SELECT id_batalha, capturas_liberadas
                 FROM batalhas_finalizacoes
                 WHERE id_batalha = :id_batalha
                   AND id_usuario = :id_usuario
                   AND token_execucao = :token_execucao
                 FOR UPDATE'
            );
            $finalizacao->execute([
                'id_batalha' => (int) $batalha['id_batalha'],
                'id_usuario' => $idUsuario,
                'token_execucao' => $batalha['token_execucao'],
            ]);
            $dadosFinalizacao = $finalizacao->fetch(PDO::FETCH_ASSOC);
            if (!is_array($dadosFinalizacao)) {
                throw new RuntimeException('A vitória não está registrada para esta captura.');
            }

            // Se já houve tentativa nesta batalha, retorna o resultado salvo sem novo sorteio.
            $tentativaExistente = $this->db->prepare(
                'SELECT tentativa.resultado AS resultado_captura,
                        tentativa.chance,
                        tentativa.numero_tentativa,
                        pokemon.nome AS pokemon_capturado,
                        captura.id_time_pokemon
                 FROM batalhas_capturas AS tentativa
                 INNER JOIN pokemons AS pokemon
                    ON pokemon.id_pokemon = tentativa.id_pokemon
                 LEFT JOIN capturas_treinadores AS captura
                    ON captura.id_captura = tentativa.id_captura
                 WHERE tentativa.id_batalha = :id_batalha
                   AND tentativa.id_usuario = :id_usuario
                 FOR UPDATE'
            );
            $tentativaExistente->execute([
                'id_batalha' => (int) $dadosFinalizacao['id_batalha'],
                'id_usuario' => $idUsuario,
            ]);
            $dadosTentativa = $tentativaExistente->fetch(PDO::FETCH_ASSOC);
            if (is_array($dadosTentativa)) {
                $this->db->commit();
                return $this->formatarCaptura($dadosTentativa);
            }

            $captura = $this->registrarCapturaDaVitoria(
                $idUsuario,
                (int) $batalha['id_treinador'],
                (int) $dadosFinalizacao['capturas_liberadas'],
                (int) $dadosFinalizacao['id_batalha']
            );
            $this->db->commit();

            return $captura;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    /** Confere usuário, vitória, token e finalização antes de permitir capturar. */
    private function validarBatalhaParaCaptura(int $idUsuario, array $batalha): void
    {
        if (
            empty($batalha['finalizada'])
            || ($batalha['resultado'] ?? null) !== 'vitoria'
            || empty($batalha['resultado_salvo'])
            || (int) ($batalha['id_usuario'] ?? 0) !== $idUsuario
            || (int) ($batalha['id_batalha'] ?? 0) < 1
            || (int) ($batalha['id_treinador'] ?? 0) < 1
            || !is_string($batalha['token_execucao'] ?? null)
            || preg_match('/\A[a-f0-9]{32}\z/', $batalha['token_execucao']) !== 1
        ) {
            throw new RuntimeException('A batalha não está válida para uma tentativa de captura.');
        }
    }

    /**
     * Encontra o primeiro Pokémon liberado que o jogador ainda não possui.
     *
     * Linhas já capturadas ou espécies já presentes no time são puladas.
     */
    private function buscarProximoPokemonCapturavel(
        int $idUsuario,
        int $idTreinador,
        int $capturasLiberadas,
        bool $bloquearLinhas
    ): ?array {
        if ($capturasLiberadas < 1) {
            return null;
        }

        $consulta = $this->db->prepare(
            'SELECT treinador_pokemon.id_pokemon,
                    treinador_pokemon.ordem_captura,
                    pokemon.nome,
                    captura.id_captura,
                    EXISTS (
                        SELECT 1
                        FROM time_pokemons AS pokemon_usuario
                        INNER JOIN times AS time_usuario
                            ON time_usuario.id_time = pokemon_usuario.id_time
                        WHERE time_usuario.id_usuario = :id_usuario_dono
                          AND pokemon_usuario.id_pokemon = treinador_pokemon.id_pokemon
                    ) AS ja_possui
             FROM treinador_pokemons AS treinador_pokemon
             INNER JOIN pokemons AS pokemon
                ON pokemon.id_pokemon = treinador_pokemon.id_pokemon
             LEFT JOIN capturas_treinadores AS captura
                ON captura.id_usuario = :id_usuario
               AND captura.id_treinador = treinador_pokemon.id_treinador
               AND captura.ordem_captura = treinador_pokemon.ordem_captura
             WHERE treinador_pokemon.id_treinador = :id_treinador
             ORDER BY treinador_pokemon.ordem_captura'
             . ($bloquearLinhas ? ' FOR UPDATE' : '')
        );
        $consulta->execute([
            'id_usuario' => $idUsuario,
            'id_treinador' => $idTreinador,
            'id_usuario_dono' => $idUsuario,
        ]);

        $ordemMaximaLiberada = $capturasLiberadas;
        while (($pokemon = $consulta->fetch(PDO::FETCH_ASSOC)) !== false) {
            $ordemCaptura = (int) $pokemon['ordem_captura'];
            if ($ordemCaptura > $ordemMaximaLiberada) {
                break;
            }
            if ($pokemon['id_captura'] !== null) {
                continue;
            }
            if ((int) $pokemon['ja_possui'] === 1) {
                $ordemMaximaLiberada++;
                continue;
            }

            return [
                'id_pokemon' => (int) $pokemon['id_pokemon'],
                'nome' => (string) $pokemon['nome'],
            ];
        }

        return null;
    }

    /**
     * Usa a chance fixa aprovada e, no sucesso, adiciona o Pokémon à reserva com HP completo.
     */
    private function registrarCapturaDaVitoria(
        int $idUsuario,
        int $idTreinador,
        int $capturasLiberadas,
        int $idBatalha
    ): ?array {
        $proximoPokemon = $this->buscarProximoPokemonCapturavel(
            $idUsuario,
            $idTreinador,
            $capturasLiberadas,
            true
        );
        if ($proximoPokemon === null) {
            return null;
        }

        $buscarCaptura = $this->db->prepare(
            'SELECT pokemon.id_pokemon,
                    pokemon.nome,
                    pokemon.hp_maximo,
                    treinador_pokemon.ordem_captura
             FROM treinador_pokemons AS treinador_pokemon
             INNER JOIN pokemons AS pokemon
                ON pokemon.id_pokemon = treinador_pokemon.id_pokemon
             WHERE treinador_pokemon.id_treinador = :id_treinador
               AND treinador_pokemon.id_pokemon = :id_pokemon
             FOR UPDATE'
        );
        $buscarCaptura->execute([
            'id_treinador' => $idTreinador,
            'id_pokemon' => $proximoPokemon['id_pokemon'],
        ]);
        $pokemonCapturado = $buscarCaptura->fetch(PDO::FETCH_ASSOC);
        if (!is_array($pokemonCapturado)) {
            throw new RuntimeException('O Pokémon disponível para captura não foi encontrado.');
        }

        $buscarTentativas = $this->db->prepare(
            'SELECT COUNT(*)
             FROM batalhas_capturas
             WHERE id_usuario = :id_usuario
               AND id_treinador = :id_treinador
               AND ordem_captura = :ordem_captura'
        );
        $buscarTentativas->execute([
            'id_usuario' => $idUsuario,
            'id_treinador' => $idTreinador,
            'ordem_captura' => (int) $pokemonCapturado['ordem_captura'],
        ]);
        // A chance é fixa, mas o número da tentativa continua sendo registrado.
        $numeroTentativa = (int) $buscarTentativas->fetchColumn() + 1;
        $chance = self::CHANCE_CAPTURA;
        $sucesso = random_int(1, 100) <= $chance;
        $resultado = 'falhou';
        $idCaptura = null;
        $idTimePokemon = null;

        if ($sucesso) {
            $buscarTime = $this->db->prepare(
                'SELECT id_time
                 FROM times
                 WHERE id_usuario = :id_usuario
                 ORDER BY id_time
                 LIMIT 1
                 FOR UPDATE'
            );
            $buscarTime->execute(['id_usuario' => $idUsuario]);
            $idTime = $buscarTime->fetchColumn();
            if ($idTime === false) {
                throw new RuntimeException(
                    'Não foi encontrado o time do jogador para guardar a captura.'
                );
            }
            $idTime = (int) $idTime;

            $buscarExistente = $this->db->prepare(
                'SELECT id_time_pokemon
                 FROM time_pokemons
                 WHERE id_time = :id_time
                   AND id_pokemon = :id_pokemon
                 FOR UPDATE'
            );
            $buscarExistente->execute([
                'id_time' => $idTime,
                'id_pokemon' => (int) $pokemonCapturado['id_pokemon'],
            ]);
            $idTimePokemon = $buscarExistente->fetchColumn();

            if ($idTimePokemon === false) {
                // Capturas novas entram depois das três posições reservadas ao time ativo.
                $buscarUltimaPosicao = $this->db->prepare(
                    'SELECT COALESCE(MAX(posicao), 3)
                     FROM time_pokemons
                     WHERE id_time = :id_time'
                );
                $buscarUltimaPosicao->execute(['id_time' => $idTime]);
                $posicao = max(3, (int) $buscarUltimaPosicao->fetchColumn()) + 1;
                if ($posicao > 255) {
                    throw new RuntimeException(
                        'Não há mais posições disponíveis na reserva do time.'
                    );
                }

                $adicionarPokemon = $this->db->prepare(
                    'INSERT INTO time_pokemons (posicao, id_time, id_pokemon)
                     VALUES (:posicao, :id_time, :id_pokemon)'
                );
                $adicionarPokemon->execute([
                    'posicao' => $posicao,
                    'id_time' => $idTime,
                    'id_pokemon' => (int) $pokemonCapturado['id_pokemon'],
                ]);
                $idTimePokemon = (int) $this->db->lastInsertId();

                $salvarHp = $this->db->prepare(
                    'INSERT INTO time_pokemons_estado (id_time_pokemon, hp_atual)
                     VALUES (:id_time_pokemon, :hp_atual)'
                );
                $salvarHp->execute([
                    'id_time_pokemon' => $idTimePokemon,
                    'hp_atual' => (int) $pokemonCapturado['hp_maximo'],
                ]);
                $resultado = 'capturado';
            } else {
                $idTimePokemon = null;
                $resultado = 'ja_possuia';
            }

            $registrarCaptura = $this->db->prepare(
                'INSERT INTO capturas_treinadores
                    (id_usuario, id_treinador, id_pokemon, id_time_pokemon, ordem_captura)
                 VALUES
                    (:id_usuario, :id_treinador, :id_pokemon, :id_time_pokemon, :ordem_captura)'
            );
            $registrarCaptura->execute([
                'id_usuario' => $idUsuario,
                'id_treinador' => $idTreinador,
                'id_pokemon' => (int) $pokemonCapturado['id_pokemon'],
                'id_time_pokemon' => $idTimePokemon,
                'ordem_captura' => (int) $pokemonCapturado['ordem_captura'],
            ]);
            $idCaptura = (int) $this->db->lastInsertId();
        }

        $registrarTentativa = $this->db->prepare(
            'INSERT INTO batalhas_capturas
                (
                    id_batalha, id_usuario, id_treinador, id_pokemon, ordem_captura,
                    numero_tentativa, chance, resultado, id_captura
                )
             VALUES
                (
                    :id_batalha, :id_usuario, :id_treinador, :id_pokemon, :ordem_captura,
                    :numero_tentativa, :chance, :resultado, :id_captura
                )'
        );
        $registrarTentativa->execute([
            'id_batalha' => $idBatalha,
            'id_usuario' => $idUsuario,
            'id_treinador' => $idTreinador,
            'id_pokemon' => (int) $pokemonCapturado['id_pokemon'],
            'ordem_captura' => (int) $pokemonCapturado['ordem_captura'],
            'numero_tentativa' => $numeroTentativa,
            'chance' => $chance,
            'resultado' => $resultado,
            'id_captura' => $idCaptura,
        ]);

        return [
            'nome' => (string) $pokemonCapturado['nome'],
            'id_pokemon' => (int) $pokemonCapturado['id_pokemon'],
            'resultado' => $resultado,
            'chance' => $chance,
            'numero_tentativa' => $numeroTentativa,
            'proxima_chance' => self::CHANCE_CAPTURA,
            'nova' => $resultado === 'capturado',
        ];
    }

    /** Converte os campos do banco no formato que as telas de captura consomem. */
    private function formatarCaptura(array $resultado): ?array
    {
        if (
            !is_string($resultado['pokemon_capturado'] ?? null)
            || !is_string($resultado['resultado_captura'] ?? null)
        ) {
            return null;
        }

        return [
            'nome' => $resultado['pokemon_capturado'],
            'resultado' => $resultado['resultado_captura'],
            'chance' => (int) $resultado['chance'],
            'numero_tentativa' => (int) $resultado['numero_tentativa'],
            'proxima_chance' => self::CHANCE_CAPTURA,
            'nova' => $resultado['resultado_captura'] === 'capturado'
                && $resultado['id_time_pokemon'] !== null,
        ];
    }

    /** Carrega os três Pokémon ativos e seu HP individual persistido. */
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

    /** Busca os movimentos cadastrados para a espécie e normaliza os campos numéricos. */
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

    /**
     * Normaliza atributos e inicializa os estados temporários usados pelo engine.
     *
     * Status, estágios, proteção e limites de uso começam zerados a cada batalha.
     */
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

    /** Transforma os tipos concatenados pela consulta SQL em uma lista PHP. */
    private function separarTipos(?string $tipos): array
    {
        return is_string($tipos) && $tipos !== ''
            ? explode('|', $tipos)
            : [];
    }

    /**
     * Persiste o HP final sem tocar no HP global da espécie.
     *
     * Antes de salvar, confirma que cada registro de Pokémon ainda pertence ao usuário.
     */
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
