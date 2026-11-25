<?php

use App\Models\Loja;

/** Coordena a exibição da loja e o envio de solicitações de compra. */
class BolichoController
{
    // A conexão é compartilhada com o Model da loja.
    private PDO $db;

    /** Recebe a conexão PDO preparada pelo front controller. */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Autentica o acesso, processa compras e prepara saldo e catálogo para a View. */
    public function index(): void
    {
        // A loja altera o saldo e o inventário, então só usuários autenticados podem acessá-la.
        $idUsuario = filter_var(
            $_SESSION['usuario']['id_usuario'] ?? null,
            FILTER_VALIDATE_INT
        );
        if ($idUsuario === false || $idUsuario === null || $idUsuario < 1) {
            header('Location: index.php?rota=login');
            exit;
        }

        if (!isset($_SESSION['csrf_bolicho'])) {
            // O token é reutilizado durante esta sessão para validar o formulário de compra.
            $_SESSION['csrf_bolicho'] = bin2hex(random_bytes(32));
        }

        // Recupera uma mensagem após o redirecionamento e a remove para exibi-la uma vez.
        $mensagem = $_SESSION['mensagem_bolicho'] ?? null;
        unset($_SESSION['mensagem_bolicho']);
        $erro = null;
        $loja = new Loja($this->db);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Confere o token antes de aceitar o identificador do item enviado pelo navegador.
            $tokenEnviado = $_POST['csrf_token'] ?? '';
            if (
                !is_string($tokenEnviado)
                || !hash_equals($_SESSION['csrf_bolicho'], $tokenEnviado)
            ) {
                $erro = 'A solicitação expirou. Atualize a página e tente novamente.';
            } else {
                $idItem = filter_var(
                    $_POST['id_item'] ?? null,
                    FILTER_VALIDATE_INT
                );
                if ($idItem === false || $idItem === null || $idItem < 1) {
                    $erro = 'Selecione um item válido.';
                } else {
                    try {
                        // O Model valida saldo e item e grava débito, inventário e compra juntos.
                        $compra = $loja->comprarItem($idUsuario, $idItem);
                        // Redireciona após a compra para evitar repeti-la ao atualizar a página.
                        $_SESSION['mensagem_bolicho'] = [
                            'tipo' => 'compra',
                            'item' => (string) $compra['nome'],
                            'saldo' => (int) $compra['saldo'],
                        ];
                        header('Location: index.php?rota=bolicho');
                        exit;
                    } catch (InvalidArgumentException $exception) {
                        $erro = $exception->getMessage();
                    } catch (PDOException | RuntimeException $exception) {
                        error_log('Erro ao comprar item no Bolicho: ' . $exception->getMessage());
                        http_response_code(500);
                        $erro = 'Não foi possível concluir a compra agora.';
                    }
                }
            }
        }

        try {
            // Dados para a interface são lidos do banco; preço e saldo não vêm do formulário.
            $saldo = $loja->obterSaldo($idUsuario);
            $itens = $loja->listarItens($idUsuario);
        } catch (PDOException | RuntimeException $exception) {
            error_log('Erro ao carregar o Bolicho: ' . $exception->getMessage());
            http_response_code(500);
            $erro = 'Não foi possível carregar a loja agora.';
            $saldo = 0;
            $itens = [];
        }
        $csrfBolicho = $_SESSION['csrf_bolicho'];
        // Entrega à View apenas os dados já carregados e a mensagem de retorno.
        require __DIR__ . '/../Views/bolicho/bolicho.php';
    }
}