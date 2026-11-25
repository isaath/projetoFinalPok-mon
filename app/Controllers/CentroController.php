<?php

use App\Models\CentroPokemon;

/** Valida o acesso ao Pokecenter e prepara a recuperação para a interface. */
class CentroController
{
    private PDO $db;

    /** Recebe a conexão que será usada pelo Model de recuperação. */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Exibe os Pokémon do usuário e processa pedidos de cura enviados por formulário. */
    public function index(): void
    {
        // Apenas o dono da sessão pode consultar ou curar os Pokémon associados à conta.
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

        if (!isset($_SESSION['csrf_centro'])) {
            // O token impede que outro site envie uma solicitação de cura em nome do usuário.
            $_SESSION['csrf_centro'] = bin2hex(random_bytes(32));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // A cura altera saldo e HP; valide a origem antes de chamar o Model.
            $token = $_POST['csrf_token'] ?? '';

            if (
                !is_string($token)
                || !hash_equals($_SESSION['csrf_centro'], $token)
            ) {
                http_response_code(400);
                $erro = 'A solicitação expirou. Atualize a página e tente novamente.';
            } else {
                try {
                    // O Model decide custo, elegibilidade e atualização de HP dentro de transação.
                    $resultado = (new CentroPokemon($this->db))
                        ->curarTodosDoUsuario($idUsuario);

                    // Guarda o resultado para mostrar após o redirecionamento.
                    $_SESSION['flash_centro'] = $this->mensagemResultado($resultado);
                } catch (PDOException | RuntimeException $exception) {
                    error_log('Erro ao curar Pokémon no Pokecenter: ' . $exception->getMessage());
                    $_SESSION['flash_centro'] = 'Não foi possível concluir a visita. Tente novamente.';
                }

                header('Location: index.php?rota=centro');
                exit;
            }
        }

        // Lê uma mensagem de uso único, evitando repetir o aviso ao atualizar a página.
        $mensagem = $_SESSION['flash_centro'] ?? null;
        unset($_SESSION['flash_centro']);

        try {
            $modelo = new CentroPokemon($this->db);
            // O saldo e o estado de HP são lidos separadamente para montar a tela.
            $saldo = $modelo->obterSaldo($idUsuario);
            $pokemons = $modelo->listarPokemonsUsuario($idUsuario);
        } catch (PDOException | RuntimeException $exception) {
            error_log('Erro ao carregar o Pokecenter: ' . $exception->getMessage());
            http_response_code(500);
            $erro = 'Não foi possível carregar o Pokecenter agora.';
            $saldo = 0;
            $pokemons = [];
        }

        $custoVisita = CentroPokemon::CUSTO_VISITA;
        // A View recebe dados prontos e não executa consultas SQL.
        require __DIR__ . '/../Views/centro/index.php';
    }

    /** Converte os estados retornados pelo Model em mensagens adequadas à interface. */
    private function mensagemResultado(array $resultado): array
    {
        switch ($resultado['status']) {
            case 'curado':
                return [
                    'tipo' => 'texto',
                    'texto' => sprintf(
                        '%d Pokémon foram curados. A visita custou %d PokePila.',
                        $resultado['quantidade'],
                        CentroPokemon::CUSTO_VISITA
                    ),
                ];
            case 'ja_completo':
                return [
                    'tipo' => 'texto',
                    'texto' => 'Todos os seus Pokémon já estão com HP completo. Nenhuma moeda foi cobrada.',
                ];
            case 'saldo_insuficiente':
                return [
                    'tipo' => 'saldo_insuficiente',
                    'custo' => CentroPokemon::CUSTO_VISITA,
                    'saldo' => (int) $resultado['saldo'],
                ];
            case 'sem_pokemons':
                return [
                    'tipo' => 'texto',
                    'texto' => 'Você ainda não possui Pokémon associados a um time.',
                ];
            case 'usuario_nao_encontrado':
                return [
                    'tipo' => 'texto',
                    'texto' => 'Não foi possível localizar sua conta. Entre novamente.',
                ];
            default:
                throw new RuntimeException('Resultado desconhecido do Pokecenter.');
        }
    }
}
