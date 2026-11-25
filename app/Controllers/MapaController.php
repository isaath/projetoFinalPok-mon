<?php

use App\Models\Batalha;

/**
 * Controla o acesso ao mapa e prepara o progresso individual do jogador.
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

        // A View recebe o progresso pronto para desenhar os níveis liberados e bloqueados.
        $erroMapa = null;
        try {
            $progressoNiveis = (new Batalha($this->db))
                ->carregarProgressoNiveis($idUsuario);
        } catch (PDOException | RuntimeException $exception) {
            // Registra o erro técnico e mostra uma mensagem compreensível na tela.
            error_log('Erro ao carregar o progresso do mapa: ' . $exception->getMessage());
            http_response_code(500);
            $erroMapa = 'Não foi possível carregar o mapa agora.';
            $progressoNiveis = [];
        }

        // Exibe o mapa usando os dados preparados acima.
        require __DIR__ . '/../Views/batalha/mapa_jogo.php';
    }
}
