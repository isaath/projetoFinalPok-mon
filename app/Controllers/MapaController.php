<?php

use App\Models\Batalha;
use App\Models\EventoMapa;

/**
 * Na apresentação: valida sessão/CSRF, usa Batalha para carregar o progresso
 * e EventoMapa para sortear/persistir a exploração, depois prepara a View.
 */
class MapaController
{
    // Guarda a conexão necessária para consultar o progresso da jornada.
    private PDO $db;

    // Recebe a conexão PDO criada pelo ponto de entrada da aplicação.
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Confere a sessão, consulta os níveis liberados e carrega a tela do mapa.
     */
    public function index(): void
    {
        // O mapa e o progresso são pessoais, então exigem um usuário autenticado.
        if (!isset($_SESSION['usuario']['id_usuario'])) {
            header('Location: index.php?rota=login');
            exit;
        }

        // Converte o ID da sessão para inteiro antes de usá-lo na consulta.
        $idUsuario = filter_var(
            $_SESSION['usuario']['id_usuario'],
            FILTER_VALIDATE_INT
        );

        // Uma sessão com ID inválido deve ser encerrada como uma sessão não autenticada.
        if ($idUsuario === false || $idUsuario < 1) {
            unset($_SESSION['usuario']);
            header('Location: index.php?rota=login');
            exit;
        }

        if (!isset($_SESSION['csrf_mapa'])) {
            $_SESSION['csrf_mapa'] = bin2hex(random_bytes(32));
        }

        $erroMapa = null;
        $resultadoEvento = null;
        $modeloEvento = new EventoMapa($this->db);
        $niveisRequeridosPorPonto = EventoMapa::niveisRequeridosPorPonto();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $tokenRecebido = $_POST['csrf_token'] ?? null;
            if (
                !is_string($tokenRecebido)
                || !hash_equals($_SESSION['csrf_mapa'], $tokenRecebido)
            ) {
                http_response_code(400);
                $erroMapa = 'A solicitação do evento expirou. Atualize o mapa e tente novamente.';
            } else {
                $idPonto = filter_var(
                    $_POST['id_ponto'] ?? null,
                    FILTER_VALIDATE_INT
                );

                if ($idPonto === false || $idPonto === null) {
                    http_response_code(400);
                    $erroMapa = 'O ponto de exploração selecionado é inválido.';
                } else {
                    try {
                        $modeloEvento->sortear($idUsuario, $idPonto);
                        header('Location: index.php?rota=mapa&evento=' . $idPonto);
                        exit;
                    } catch (InvalidArgumentException $exception) {
                        http_response_code(400);
                        $erroMapa = $exception->getMessage();
                    } catch (\DomainException $exception) {
                        http_response_code(403);
                        $erroMapa = $exception->getMessage();
                    } catch (PDOException | RuntimeException $exception) {
                        error_log(
                            'Erro ao processar evento do mapa: '
                            . $exception->getMessage()
                        );
                        http_response_code(500);
                        $erroMapa = 'Não foi possível processar o evento agora.';
                    }
                }
            }
        }

        // A View recebe o progresso e os resultados persistidos dos pontos explorados.
        try {
            $progressoNiveis = (new Batalha($this->db))
                ->carregarProgressoNiveis($idUsuario);
            $eventosMapa = $modeloEvento->listarDoUsuario($idUsuario);
            $idEventoSelecionado = filter_var(
                $_GET['evento'] ?? null,
                FILTER_VALIDATE_INT
            );
            if (
                $idEventoSelecionado !== false
                && $idEventoSelecionado !== null
            ) {
                $resultadoEvento = $eventosMapa[$idEventoSelecionado] ?? null;
            }
        } catch (PDOException | RuntimeException | InvalidArgumentException $exception) {
            // Registra o erro técnico e mostra uma mensagem compreensível na tela.
            error_log('Erro ao carregar o progresso do mapa: ' . $exception->getMessage());
            http_response_code(500);
            $erroMapa = 'Não foi possível carregar o mapa agora.';
            $progressoNiveis = [];
            $eventosMapa = [];
        }

        $csrfMapa = $_SESSION['csrf_mapa'];

        // Exibe o mapa usando os dados preparados acima.
        require __DIR__ . '/../Views/batalha/mapa_jogo.php';
    }
}
