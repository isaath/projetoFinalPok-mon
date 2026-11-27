<?php

use App\Models\TimeInicial;
use App\Models\TimePokemon;


/**
 * Na apresentação: coordena a escolha inicial e a tela Meu time. Usa
 * TimeInicial para criar a jornada e TimePokemon para listar/reorganizar
 * membros; as Views só recebem os dados e tokens já preparados.
 */
class TimeController
{
    // A mesma conexão PDO é compartilhada com os Models desta requisição.
    private PDO $db;

    /** Recebe a conexão criada pelo ponto de entrada da aplicação. */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Exibe as cinco opções iniciais e salva a escolha de três espécies.
     */
    public function escolher(): void
    {
        // A seleção inicial pertence a uma conta autenticada.
        if (!isset($_SESSION['usuario']['id_usuario'])) {
            header('Location: index.php?rota=login');
            exit;
        }

        $idUsuario = filter_var(
            $_SESSION['usuario']['id_usuario'],
            FILTER_VALIDATE_INT
        );

        if ($idUsuario === false || $idUsuario < 1) {
            header('Location: index.php?rota=login');
            exit;
        }

        $modelo = new TimeInicial($this->db);
        $erro = null;
        $token = $_SESSION['csrf_escolher_time'] ?? null;

        // O token protege o formulário contra envios forjados de outro site.
        if (!is_string($token)) {
            $token = bin2hex(random_bytes(32));
            $_SESSION['csrf_escolher_time'] = $token;
        }

        try {
            // Uma jornada já iniciada não deve permitir substituir silenciosamente o time inicial.
            if ($modelo->jornadaIniciada($idUsuario)) {
                header('Location: index.php?rota=jogar');
                exit;
            }

            $pokemons = $modelo->listarDisponiveis();

            if (count($pokemons) !== 5) {
                error_log(
                    'O catálogo inicial retornou ' . count($pokemons)
                    . ' de 5 Pokémon em bancodedados_pokefish.'
                );
                $erro = sprintf(
                    'O banco bancodedados_pokefish retornou %d de 5 Pokémon iniciais. '
                    . 'No DBeaver, execute o script completo database/schema.sql e atualize esta página.',
                    count($pokemons)
                );
            }
        } catch (PDOException | RuntimeException $exception) {
            error_log('Erro ao carregar a escolha de time: ' . $exception->getMessage());
            http_response_code(500);
            $pokemons = [];
            $erro = 'Não foi possível carregar as opções iniciais agora.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erro === null) {
            // Confere o token antes de interpretar qualquer dado enviado pelo formulário.
            $tokenEnviado = $_POST['csrf_token'] ?? '';

            if (
                !is_string($tokenEnviado)
                || !hash_equals($token, $tokenEnviado)
            ) {
                http_response_code(400);
                $erro = 'A solicitação expirou. Atualize a página e tente novamente.';
            } else {
                $idsInformados = $_POST['pokemon_ids'] ?? null;
                $idsPokemons = $this->validarIds($idsInformados);

                if ($idsPokemons === null) {
                    $erro = 'Selecione exatamente três Pokémon diferentes.';
                } else {
                    try {
                        // O Model valida de novo as espécies permitidas e grava o time em transação.
                        $resultado = $modelo->salvarSelecaoInicial($idUsuario, $idsPokemons);

                        if ($resultado === 'salvo' || $resultado === 'jornada_iniciada') {
                            header('Location: index.php?rota=jogar');
                            exit;
                        }

                        if ($resultado === 'usuario_nao_encontrado') {
                            unset($_SESSION['usuario']);
                            header('Location: index.php?rota=login');
                            exit;
                        }

                        $erro = 'A seleção não é válida. Escolha três das opções exibidas.';
                    } catch (PDOException | RuntimeException $exception) {
                        error_log('Erro ao salvar o time inicial: ' . $exception->getMessage());
                        http_response_code(500);
                        $erro = 'Não foi possível salvar seu time agora. Tente novamente.';
                    }
                }
            }
        }

        require __DIR__ . '/../Views/time/escolher_time.php';
    }

    /** Lista os Pokémon do jogador e processa mudanças no time ativo. */
    public function meuTime(): void
    {
        $idUsuario = $this->idUsuarioSessao();
        if ($idUsuario === null) {
            header('Location: index.php?rota=login');
            exit;
        }

        $modelo = new TimePokemon($this->db);
        $token = $_SESSION['csrf_meu_time'] ?? null;
        if (!is_string($token)) {
            $token = bin2hex(random_bytes(32));
            $_SESSION['csrf_meu_time'] = $token;
        }

        $erro = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Cada formulário envia um token para validar a origem da alteração.
            $tokenEnviado = $_POST['csrf_token'] ?? '';

            if (!is_string($tokenEnviado) || !hash_equals($token, $tokenEnviado)) {
                http_response_code(400);
                $erro = 'A solicitação expirou. Atualize a página e tente novamente.';
            } else {
                $idTimePokemon = filter_var(
                    $_POST['id_time_pokemon'] ?? null,
                    FILTER_VALIDATE_INT
                );
                $acao = $_POST['acao'] ?? '';

                if ($idTimePokemon === false || $idTimePokemon < 1 || !is_string($acao)) {
                    $erro = 'A ação solicitada não é válida.';
                } else {
                    try {
                        // Converte a ação do formulário em uma chamada explícita ao Model.
                        if ($acao === 'colocar') {
                            $resultado = $modelo->colocarNaPosicaoTres(
                                $idUsuario,
                                $idTimePokemon
                            );
                        } elseif ($acao === 'evoluir') {
                            $preparacao = $modelo->prepararEvolucao(
                                $idUsuario,
                                $idTimePokemon
                            );
                            $resultado = $preparacao['status'];

                            if ($resultado === 'pronto') {
                                try {
                                    $dadosApi = (new PokeApiService())->obterAtributosBase(
                                        (string) $preparacao['nome_evolucao']
                                    );
                                } catch (RuntimeException $exception) {
                                    error_log(
                                        'Erro ao consultar atributos da evolução na PokeAPI: '
                                        . $exception->getMessage()
                                    );
                                    http_response_code(502);
                                    $erro = 'Não foi possível consultar os atributos na PokeAPI. '
                                        . 'O Pokémon não evoluiu e o item não foi consumido.';
                                }

                                if ($erro === null) {
                                    $resultado = $modelo->evoluirComItem(
                                        $idUsuario,
                                        $idTimePokemon,
                                        $dadosApi
                                    );
                                }
                            }
                        } elseif ($acao === 'prioridade') {
                            $posicao = filter_var(
                                $_POST['posicao'] ?? null,
                                FILTER_VALIDATE_INT
                            );

                            $resultado = $posicao === false
                                ? 'dados_invalidos'
                                : $modelo->moverPrioridade(
                                    $idUsuario,
                                    $idTimePokemon,
                                    $posicao
                                );
                        } else {
                            $resultado = 'dados_invalidos';
                        }

                        if ($erro === null) {
                            $mensagem = $this->mensagemTime($resultado);
                            if ($mensagem['tipo'] === 'sucesso') {
                                // Redireciona após salvar para evitar repetir a ação ao atualizar a página.
                                $_SESSION['flash_meu_time'] = $mensagem['texto'];
                                header('Location: index.php?rota=meu_time');
                                exit;
                            }

                            $erro = $mensagem['texto'];
                        }
                    } catch (PDOException | RuntimeException $exception) {
                        error_log('Erro ao atualizar o time do usuário: ' . $exception->getMessage());
                        http_response_code(500);
                        $erro = 'Não foi possível atualizar seu time agora. Tente novamente.';
                    }
                }
            }
        }

        $mensagem = $_SESSION['flash_meu_time'] ?? null;
        unset($_SESSION['flash_meu_time']);

        try {
            $pokemons = $modelo->listarDoUsuario($idUsuario);
        } catch (PDOException | RuntimeException $exception) {
            error_log('Erro ao listar o time do usuário: ' . $exception->getMessage());
            http_response_code(500);
            $pokemons = [];
            $erro = 'Não foi possível carregar seu time agora.';
        }

        $ativos = array_values(array_filter(
            $pokemons,
            static fn (array $pokemon): bool => (int) $pokemon['posicao'] <= 3
        ));
        // As posições a partir de 4 ficam fora da escalação usada na batalha.
        $reservas = array_values(array_filter(
            $pokemons,
            static fn (array $pokemon): bool => (int) $pokemon['posicao'] > 3
        ));

        require __DIR__ . '/../Views/time/meu_time.php';
    }

    /** Retorna um ID de sessão válido ou null para uma sessão ausente/inválida. */
    private function idUsuarioSessao(): ?int
    {
        if (!isset($_SESSION['usuario']['id_usuario'])) {
            return null;
        }

        $idUsuario = filter_var($_SESSION['usuario']['id_usuario'], FILTER_VALIDATE_INT);
        return $idUsuario !== false && $idUsuario > 0 ? $idUsuario : null;
    }

    /** Traduz os resultados padronizados do Model em mensagens para a tela. */
    private function mensagemTime(string $resultado): array
    {
        switch ($resultado) {
            case 'movido':
                return ['tipo' => 'sucesso', 'texto' => 'A prioridade do time foi atualizada.'];
            case 'colocado':
                return [
                    'tipo' => 'sucesso',
                    'texto' => 'Pokémon colocado na posição 3; o anterior foi para a reserva.',
                ];
            case 'evoluido':
                return [
                    'tipo' => 'sucesso',
                    'texto' => 'Evolução concluída! Os atributos foram atualizados e o HP foi restaurado ao máximo.',
                ];
            case 'sem_item_evolutivo':
                return ['tipo' => 'erro', 'texto' => 'Você precisa ter um Item Evolutivo no inventário.'];
            case 'nao_elegivel':
                return ['tipo' => 'erro', 'texto' => 'Esse Pokémon não possui um próximo estágio cadastrado.'];
            case 'evolucao_ja_possuida':
                return [
                    'tipo' => 'erro',
                    'texto' => 'Seu time já possui essa espécie; o Item Evolutivo não foi consumido.',
                ];
            case 'mesma_posicao':
                return ['tipo' => 'erro', 'texto' => 'Esse Pokémon já ocupa essa posição.'];
            case 'nao_esta_ativo':
                return ['tipo' => 'erro', 'texto' => 'Esse Pokémon não está no time ativo.'];
            case 'ja_esta_ativo':
                return ['tipo' => 'erro', 'texto' => 'Esse Pokémon já está no time ativo.'];
            case 'time_incompleto':
                return ['tipo' => 'erro', 'texto' => 'O time precisa ter três Pokémon ativos para essa ação.'];
            case 'pokemon_nao_encontrado':
                return ['tipo' => 'erro', 'texto' => 'Esse Pokémon não pertence ao seu time.'];
            case 'usuario_nao_encontrado':
                unset($_SESSION['usuario']);
                header('Location: index.php?rota=login');
                exit;
            default:
                return ['tipo' => 'erro', 'texto' => 'A ação solicitada não é válida.'];
        }
    }

    /**
     * Valida o formato do formulário; as regras de propriedade também são conferidas no Model.
     *
     * @param mixed $valor
     * @return int[]|null
     */
    private function validarIds($valor): ?array
    {
        if (!is_array($valor) || count($valor) !== 3) {
            return null;
        }

        $ids = [];

        foreach ($valor as $id) {
            if (!is_string($id) && !is_int($id)) {
                return null;
            }

            $idValidado = filter_var($id, FILTER_VALIDATE_INT);
            if ($idValidado === false || $idValidado < 1) {
                return null;
            }

            $ids[] = $idValidado;
        }

        return count(array_unique($ids)) === 3 ? $ids : null;
    }
}
