<?php

use App\Models\CentroPokemon;

class CentroController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function index(): void
    {
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
            $_SESSION['csrf_centro'] = bin2hex(random_bytes(32));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';

            if (
                !is_string($token)
                || !hash_equals($_SESSION['csrf_centro'], $token)
            ) {
                http_response_code(400);
                $erro = 'A solicitação expirou. Atualize a página e tente novamente.';
            } else {
                try {
                    $resultado = (new CentroPokemon($this->db))
                        ->curarTodosDoUsuario($idUsuario);

                    $_SESSION['flash_centro'] = $this->mensagemResultado($resultado);
                } catch (PDOException | RuntimeException $exception) {
                    error_log('Erro ao curar Pokémon no Pokecenter: ' . $exception->getMessage());
                    $_SESSION['flash_centro'] = 'Não foi possível concluir a visita. Tente novamente.';
                }

                header('Location: index.php?rota=centro');
                exit;
            }
        }

        $mensagem = $_SESSION['flash_centro'] ?? null;
        unset($_SESSION['flash_centro']);

        try {
            $modelo = new CentroPokemon($this->db);
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
        require __DIR__ . '/../Views/centro/index.php';
    }

    private function mensagemResultado(array $resultado): string
    {
        switch ($resultado['status']) {
            case 'curado':
                return sprintf(
                    '%d Pokémon foram curados. A visita custou %d Pokédollars.',
                    $resultado['quantidade'],
                    CentroPokemon::CUSTO_VISITA
                );
            case 'ja_completo':
                return 'Todos os seus Pokémon já estão com HP completo. Nenhuma moeda foi cobrada.';
            case 'saldo_insuficiente':
                return sprintf(
                    'A visita custa %d Pokédollars. Seu saldo atual é %d.',
                    CentroPokemon::CUSTO_VISITA,
                    $resultado['saldo']
                );
            case 'sem_pokemons':
                return 'Você ainda não possui Pokémon associados a um time.';
            case 'usuario_nao_encontrado':
                return 'Não foi possível localizar sua conta. Entre novamente.';
            default:
                throw new RuntimeException('Resultado desconhecido do Pokecenter.');
        }
    }
}
