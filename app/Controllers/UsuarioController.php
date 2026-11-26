<?php
use App\Models\TimeInicial;

// Carrega o modelo de usuário para autenticação e cadastro.
require __DIR__ . '/../Models/Usuario.php';

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

    /**
     * Valida as credenciais e inicia a sessão do usuário.
     *
     * Depois da autenticação, encaminha quem ainda não iniciou a jornada
     * para escolher o time inicial.
     */
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
                        try {
                            $jornadaIniciada = (new TimeInicial($this->db))
                                ->jornadaIniciada((int) $usuario['id_usuario']);
                        } catch (PDOException | RuntimeException $exception) {
                            error_log('Erro ao consultar o time inicial no login: ' . $exception->getMessage());
                            $erro = 'Não foi possível carregar seu progresso. Tente novamente.';
                        }

                        if ($erro === null) {
                            // Gera novo identificador da sessão para segurança.
                            session_regenerate_id(true);

                            // Guarda os dados do usuário autenticado na sessão.
                            $_SESSION['usuario'] = $usuario;

                            $rota = $jornadaIniciada ? 'jogar' : 'escolher_time';
                            header('Location: index.php?rota=' . $rota);
                            exit;
                        }
                    }
                }
            }
        }

        // Carrega a View de login para mostrar o formulário.
        require __DIR__ . '/../Views/usuario/login.php';
    }

    /**
     * Exibe o painel somente para usuários autenticados que já iniciaram a jornada.
     */
    public function painel_jogar(): void
    {
        if (
            !isset($_SESSION['usuario'])
            || !is_array($_SESSION['usuario'])
            || !isset($_SESSION['usuario']['id_usuario'])
        ) {
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

        try {
            $jornadaIniciada = (new TimeInicial($this->db))
                ->jornadaIniciada($idUsuario);
        } catch (PDOException | RuntimeException $exception) {
            error_log('Erro ao verificar o time antes de abrir o painel: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Não foi possível carregar seu progresso agora.';
            return;
        }

        if (!$jornadaIniciada) {
            header('Location: index.php?rota=escolher_time');
            exit;
        }

        require __DIR__ . '/../Views/layouts/painel_jogar.php';
    }

    /** Carrega os dados do perfil do usuário autenticado para a View. */
    public function perfil(): void
    {
        $idUsuario = filter_var(
            $_SESSION['usuario']['id_usuario'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($idUsuario === false || $idUsuario === null || $idUsuario < 1) {
            header('Location: index.php?rota=login');
            exit;
        }

        $erro = null;
        $insignias = [];

        try {
            $modeloUsuario = new Usuario($this->db);
            $perfil = $modeloUsuario->buscarPerfil($idUsuario);
            $insignias = $modeloUsuario->buscarInsigniasConquistadas($idUsuario);
        } catch (PDOException $exception) {
            error_log('Erro ao carregar o perfil do usuário: ' . $exception->getMessage());
            http_response_code(500);
            $perfil = null;
            $erro = 'Não foi possível carregar seu perfil agora.';
        }

        if ($perfil === null && $erro === null) {
            unset($_SESSION['usuario']);
            header('Location: index.php?rota=login');
            exit;
        }

        require __DIR__ . '/../Views/usuario/perfil.php';
    }

    /**
     * Valida e cadastra uma nova conta, incluindo uma foto opcional.
     *
     * Senhas, limites dos campos e o tipo/tamanho da imagem são conferidos no servidor.
     */
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
            $foto = null;
            $tipoFoto = null;
            $erroFoto = null;

            // A imagem é validada pelo conteúdo MIME e pelo tamanho, não apenas pelo nome do arquivo.
            if (!empty($_FILES['foto']['tmp_name'])) {
                $informacoesImagem = @getimagesize($_FILES['foto']['tmp_name']);

                if (
                    $_FILES['foto']['size'] > 2 * 1024 * 1024
                    || $informacoesImagem === false
                    || !in_array(
                        $informacoesImagem['mime'] ?? '',
                        ['image/jpeg', 'image/png'],
                        true
                    )
                ) {
                    $erroFoto = 'A foto deve ser uma imagem de até 2MB.';
                } else {
                    $conteudoFoto = file_get_contents($_FILES['foto']['tmp_name']);

                    if ($conteudoFoto === false) {
                        $erroFoto = 'Não foi possível ler a foto enviada.';
                    } else {
                        $foto = $conteudoFoto;
                        $tipoFoto = $informacoesImagem['mime'];
                    }
                }
            }

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
                } elseif ($erroFoto !== null) {
                    $erro = $erroFoto;
                } else {
                    try {
                        // Tenta cadastrar o usuário no banco.
                        $cadastrado = (new Usuario($this->db))->cadastrar(
                            $nome,
                            $email,
                            $senha,
                            $foto,
                            $tipoFoto
                        );

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