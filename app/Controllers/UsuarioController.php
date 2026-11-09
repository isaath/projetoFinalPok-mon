<?php

require_once __DIR__ . '/../Models/Usuario.php';

class UsuarioController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function login(): void
    {
        $erro = null;
        $email = '';
        $sucesso = isset($_GET['cadastro']) && $_GET['cadastro'] === 'sucesso';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $emailInformado = $_POST['email'] ?? '';
            $senha = $_POST['senha'] ?? '';

            if (
                !is_string($emailInformado)
                || !is_string($senha)
            ) {
                $erro = 'Informe um e-mail válido e uma senha.';
            } else {
                $email = trim($emailInformado);

                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
                    $erro = 'Informe um e-mail válido e uma senha.';
                } else {
                    $usuario = (new Usuario($this->db))->autenticar($email, $senha);

                    if ($usuario === null) {
                        $erro = 'E-mail ou senha inválidos.';
                    } else {
                        // Renova o identificador para evitar reutilizar a sessão anterior ao login.
                        session_regenerate_id(true);
                        $_SESSION['usuario'] = $usuario;

                       header('Location: ../app/Views/layouts/painel_jogar.php');
exit;
                    }
                }
            }
        }

        require __DIR__ . '/../Views/usuario/login.php';
    }

    public function registrar(): void
    {
        $erro = null;
        $nome = '';
        $email = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nomeInformado = $_POST['nome'] ?? '';
            $emailInformado = $_POST['email'] ?? '';
            $senha = $_POST['senha'] ?? '';
            $confirmacaoSenha = $_POST['confirmar_senha'] ?? '';
            $foto = null;
        $erroFoto = null;
         if (!empty($_FILES['foto']['tmp_name'])) {
                if ($_FILES['foto']['size'] > 2 * 1024 * 1024 || !@getimagesize($_FILES['foto']['tmp_name'])) {
                    $erroFoto = 'A foto deve ser uma imagem de até 2MB.';
                } else {
                    $foto = file_get_contents($_FILES['foto']['tmp_name']);
                }
            }

            if (
                !is_string($nomeInformado)
                || !is_string($emailInformado)
                || !is_string($senha)
                || !is_string($confirmacaoSenha)
            ) {
                $erro = 'Preencha os campos corretamente.';
            } else {
                $nome = trim($nomeInformado);
                $email = trim($emailInformado);

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
                } elseif ($erroFoto !== null) {
                    $erro = $erroFoto;
                } else {
                    try {
                        $cadastrado = (new Usuario($this->db))->cadastrar($nome, $email, $senha, $foto);

                        if (!$cadastrado) {
                            $erro = 'Este e-mail já está cadastrado.';
                        } else {
                            header('Location: index.php?rota=login&cadastro=sucesso');
                            exit;
                        }

                    } catch (PDOException $exception) {
                        if (
                            $exception->getCode() === '23000'
                            && (int) ($exception->errorInfo[1] ?? 0) === 1062
                        ) {
                            $erro = 'Este e-mail já está cadastrado.';
                        } else {
                            error_log('Erro ao cadastrar usuário: ' . $exception->getMessage());
                            $erro = 'Não foi possível concluir o cadastro. Tente novamente.';
                        }
                    }
                }
            }
        }

        require __DIR__ . '/../Views/usuario/registrar.php';
    }

}