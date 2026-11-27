<?php

/**
 * Na apresentação: recebe apenas POST, confere o token CSRF, apaga os dados
 * da sessão e seu cookie; depois redireciona ao login sem usar o banco.
 */
class LogoutController
{
    public function sair(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo 'Método não permitido.';
            return;
        }

        $tokenSessao = $_SESSION['csrf_usuario'] ?? null;
        $tokenRecebido = $_POST['csrf_token'] ?? null;
        if (
            !is_string($tokenSessao)
            || !is_string($tokenRecebido)
            || !hash_equals($tokenSessao, $tokenRecebido)
        ) {
            http_response_code(403);
            echo 'Não foi possível validar a solicitação de saída. Atualize a página e tente novamente.';
            return;
        }

        $_SESSION = [];
        $parametrosCookie = session_get_cookie_params();
        if (!setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $parametrosCookie['path'],
            'domain' => $parametrosCookie['domain'],
            'secure' => $parametrosCookie['secure'],
            'httponly' => $parametrosCookie['httponly'],
            'samesite' => $parametrosCookie['samesite'] ?? 'Lax',
        ])) {
            error_log('Não foi possível remover o cookie da sessão durante o logout.');
        }

        if (!session_destroy()) {
            http_response_code(500);
            echo 'Não foi possível encerrar a sessão. Tente novamente.';
            return;
        }

        header('Location: index.php?rota=login');
        exit;
    }
}
