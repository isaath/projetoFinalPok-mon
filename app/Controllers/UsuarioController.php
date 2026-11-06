<?php

// Carrega o modelo de usuário para autenticação e cadastro.
require_once __DIR__ . '/../Models/Usuario.php';

// Controller responsável pelas ações de login e cadastro de usuários.
class UsuarioController
{
    // Conexão com o banco para manipular dados de usuários.
    private PDO $db;

    // Construtor recebe a conexão e a salva para uso posterior.
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Método que processa o formulário de login.
    public function login(): void
    {
        // Variáveis de estado da tela: erro, e-mail preservado e sucesso do cadastro.
        $erro = null;
        $email = '';
        $sucesso = isset($_GET['cadastro']) && $_GET['cadastro'] === 'sucesso';

        // Verifica se a requisição foi enviada por formulário (POST).
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Lê os dados enviados.
            $emailInformado = $_POST['email'] ?? '';
            $senha = $_POST['senha'] ?? '';

            // Valida se os dados vieram em formato esperado.
            if (
                !is_string($emailInformado)
                || !is_string($senha)
            ) {
                $erro = 'Informe um e-mail válido e uma senha.';
            } else {
                // Remove espaços extras do e-mail.
                $email = trim($emailInformado);

                // Valida o formato do e-mail e se a senha não está vazia.
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
                    $erro = 'Informe um e-mail válido e uma senha.';
                } else {
                    // Chama o modelo para autenticar o usuário.
                    $usuario = (new Usuario($this->db))->autenticar($email, $senha);

                    // Se o retorno for nulo, credenciais inválidas.
                    if ($usuario === null) {
                        $erro = 'E-mail ou senha inválidos.';
                    } else {
                        // Gera novo identificador da sessão para segurança.
                        session_regenerate_id(true);

                        // Guarda os dados do usuário autenticado na sessão.
                        $_SESSION['usuario'] = $usuario;

                        // Redireciona para a rota do painel, que verifica a sessão.
                        header('Location: index.php?rota=jogar');
                        exit;
                    }
                }
            }
        }

        // Carrega a View de login para mostrar o formulário.
        require __DIR__ . '/../Views/usuario/login.php';
    }

    // Exibe o painel do jogo somente para um usuário autenticado.
    public function painel_jogar(): void
    {
        if (!isset($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
            header('Location: index.php?rota=login');
            exit;
        }

        require __DIR__ . '/../Views/layouts/painel_jogar.php';
    }

    // Método responsável pelo cadastro de usuários.
    public function registrar(): void
    {
        // Variáveis para mensagem de erro e valores preenchidos no formulário.
        $erro = null;
        $nome = '';
        $email = '';

        // Processa o formulário quando a requisição for POST.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Lê os campos do formulário.
            $nomeInformado = $_POST['nome'] ?? '';
            $emailInformado = $_POST['email'] ?? '';
            $senha = $_POST['senha'] ?? '';
            $confirmacaoSenha = $_POST['confirmar_senha'] ?? '';

            // Confere se os dados são strings válidas.
            if (
                !is_string($nomeInformado)
                || !is_string($emailInformado)
                || !is_string($senha)
                || !is_string($confirmacaoSenha)
            ) {
                $erro = 'Preencha os campos corretamente.';
            } else {
                // Limpa espaços extras dos dados informados.
                $nome = trim($nomeInformado);
                $email = trim($emailInformado);

                // Valida nome, e-mail e senha.
                if ($nome === '' || strlen($nome) > 255) {
                    $erro = 'Informe um nome com até 255 bytes.';
                } elseif (
                    strlen($email) > 255
                    || !filter_var($email, FILTER_VALIDATE_EMAIL)
                ) {
                    $erro = 'Informe um e-mail válido com até 255 bytes.';
                } elseif (strlen($senha) < 8) {
                    $erro = 'A senha deve ter pelo menos 8 caracteres.';
                } elseif (strlen($senha) > 72) {
                    $erro = 'A senha não pode ultrapassar 72 bytes.';
                } elseif ($senha !== $confirmacaoSenha) {
                    $erro = 'As senhas informadas não são iguais.';
                } else {
                    try {
                        // Tenta cadastrar o usuário no banco.
                        $cadastrado = (new Usuario($this->db))->cadastrar($nome, $email, $senha);

                        // Se já existir e-mail duplicado, mostra erro.
                        if (!$cadastrado) {
                            $erro = 'Este e-mail já está cadastrado.';
                        } else {
                            // Redireciona para a tela de login com mensagem de sucesso.
                            header('Location: index.php?rota=login&cadastro=sucesso');
                            exit;
                        }

                    } catch (PDOException $exception) {
                        // Trata erro de e-mail duplicado no banco.
                        if (
                            $exception->getCode() === '23000'
                            && (int) ($exception->errorInfo[1] ?? 0) === 1062
                        ) {
                            $erro = 'Este e-mail já está cadastrado.';
                        } else {
                            // Caso seja outro problema, registra no log e mostra mensagem genérica.
                            error_log('Erro ao cadastrar usuário: ' . $exception->getMessage());
                            $erro = 'Não foi possível concluir o cadastro. Tente novamente.';
                        }
                    }
                }
            }
        }

        // Carrega a View de cadastro para exibir o formulário.
        require __DIR__ . '/../Views/usuario/registrar.php';
    }

}