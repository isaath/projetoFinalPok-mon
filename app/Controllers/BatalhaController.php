<?php

use App\Models\Batalha;
use App\Services\BatalhaEngine;

require_once __DIR__ . '/../Services/PokeApiService.php';

class BatalhaController
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
            unset($_SESSION['usuario']);
            header('Location: index.php?rota=login');
            exit;
        }

        if (!isset($_SESSION['csrf_batalha'])) {
            $_SESSION['csrf_batalha'] = bin2hex(random_bytes(32));
        }

        $nivel = filter_input(INPUT_GET, 'nivel', FILTER_VALIDATE_INT);
        $nivel = $nivel === null ? 1 : $nivel;
        $dadosBatalha = null;
        $erroBatalha = null;
        $erroImagem = null;
        $imagemJogador = null;
        $imagemAdversario = null;
        $modeloBatalha = new Batalha($this->db);

        if ($nivel === false || $nivel < 1 || $nivel > 8) {
            http_response_code(400);
            $erroBatalha = 'O nível da batalha deve estar entre 1 e 8.';
        } else {
            try {
                if (!$modeloBatalha->nivelEstaLiberado($idUsuario, $nivel)) {
                    http_response_code(403);
                    $erroBatalha = 'Esse nível está bloqueado. Vença o nível anterior para liberá-lo.';
                } else {
                    $estadoSessao = $_SESSION['batalha'] ?? null;
                    if (
                        is_array($estadoSessao)
                        && empty($estadoSessao['finalizada'])
                        && (int) ($estadoSessao['id_usuario'] ?? 0) === $idUsuario
                        && (int) ($estadoSessao['nivel'] ?? 0) !== $nivel
                    ) {
                        header(
                            'Location: index.php?rota=batalha&nivel='
                            . (int) $estadoSessao['nivel']
                        );
                        exit;
                    }

                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $this->processarAcao($idUsuario, $nivel, $modeloBatalha);
                        header('Location: index.php?rota=batalha&nivel=' . $nivel);
                        exit;
                    }

                    $estadoSessao = $_SESSION['batalha'] ?? null;
                    if (
                        !is_array($estadoSessao)
                        || (int) ($estadoSessao['id_usuario'] ?? 0) !== $idUsuario
                        || (int) ($estadoSessao['nivel'] ?? 0) !== $nivel
                    ) {
                        $estadoSessao = $modeloBatalha->carregarDadosCombate($idUsuario, $nivel);
                        $_SESSION['batalha'] = $estadoSessao;
                    }

                    if (
                        !empty($estadoSessao['finalizada'])
                        && empty($estadoSessao['resultado_salvo'])
                    ) {
                        $resultado = $modeloBatalha->registrarResultado($idUsuario, $estadoSessao);
                        $estadoSessao['recompensa'] = $resultado['recompensa'];
                        $estadoSessao['capturas_liberadas'] = $resultado['capturas_liberadas'];
                        $estadoSessao['resultado_salvo'] = true;
                        $_SESSION['batalha'] = $estadoSessao;
                    }

                    $dadosBatalha = $estadoSessao;
                    $pokemonJogador = $this->pokemonAtivo($estadoSessao, 'jogador');
                    $pokemonAdversario = $this->pokemonAtivo($estadoSessao, 'adversario');
                    if ($pokemonJogador !== null && $pokemonAdversario !== null) {
                        try {
                            $servico = new PokeApiService();
                            $imagemJogador = $servico->obterImagem($pokemonJogador['nome']);
                            $imagemAdversario = $servico->obterImagem($pokemonAdversario['nome']);
                        } catch (RuntimeException | JsonException $exception) {
                            error_log('Erro ao carregar imagens da batalha: ' . $exception->getMessage());
                            $erroImagem = 'Não foi possível carregar as imagens pela PokeAPI agora.';
                        }
                    }
                }
            } catch (PDOException $exception) {
                error_log('Erro de banco de dados na batalha: ' . $exception->getMessage());
                http_response_code(500);
                $erroBatalha = 'Não foi possível carregar ou salvar a batalha agora.';
            } catch (InvalidArgumentException $exception) {
                http_response_code(400);
                $erroBatalha = $exception->getMessage();
            } catch (RuntimeException $exception) {
                $erroBatalha = $exception->getMessage();
            }
        }

        if (
            $dadosBatalha === null
            && isset($_SESSION['batalha'])
            && is_array($_SESSION['batalha'])
            && (int) ($_SESSION['batalha']['id_usuario'] ?? 0) === $idUsuario
            && (int) ($_SESSION['batalha']['nivel'] ?? 0) === $nivel
        ) {
            $dadosBatalha = $_SESSION['batalha'];
        }

        $pokemonJogador = $dadosBatalha === null
            ? null
            : $this->pokemonAtivo($dadosBatalha, 'jogador');
        $pokemonAdversario = $dadosBatalha === null
            ? null
            : $this->pokemonAtivo($dadosBatalha, 'adversario');
        $csrfBatalha = $_SESSION['csrf_batalha'];

        define('POKEBAGUAL_BATALHA_VIEW', true);
        require __DIR__ . '/../Views/batalha/index.php';
    }

    private function processarAcao(int $idUsuario, int $nivel, Batalha $modeloBatalha): void
    {
        $tokenSessao = $_SESSION['csrf_batalha'] ?? '';
        $tokenEnviado = $_POST['csrf_token'] ?? '';
        if (
            !is_string($tokenSessao)
            || !is_string($tokenEnviado)
            || $tokenSessao === ''
            || !hash_equals($tokenSessao, $tokenEnviado)
        ) {
            throw new InvalidArgumentException(
                'A solicitação expirou. Atualize a página antes de tentar novamente.'
            );
        }

        $estado = $_SESSION['batalha'] ?? null;
        if (
            ($_POST['acao'] ?? '') === 'reiniciar'
            && is_array($estado)
            && (int) ($estado['id_usuario'] ?? 0) === $idUsuario
            && (int) ($estado['nivel'] ?? 0) === $nivel
            && !empty($estado['finalizada'])
            && !empty($estado['resultado_salvo'])
        ) {
            $novoEstado = $modeloBatalha->carregarDadosCombate($idUsuario, $nivel);
            $_SESSION['batalha'] = $novoEstado;
            return;
        }

        if (
            !is_array($estado)
            || (int) ($estado['id_usuario'] ?? 0) !== $idUsuario
            || (int) ($estado['nivel'] ?? 0) !== $nivel
            || !empty($estado['finalizada'])
        ) {
            throw new InvalidArgumentException(
                'A batalha não está ativa. Atualize a página para iniciar ou continuar.'
            );
        }

        $idMovimento = filter_var(
            $_POST['id_movimento'] ?? null,
            FILTER_VALIDATE_INT
        );
        if ($idMovimento === false || $idMovimento === null || $idMovimento < 1) {
            throw new InvalidArgumentException('Escolha um movimento válido.');
        }

        $estado = BatalhaEngine::resolverTurno($estado, $idMovimento);
        $_SESSION['batalha'] = $estado;
        if (!empty($estado['finalizada'])) {
            $resultado = $modeloBatalha->registrarResultado($idUsuario, $estado);
            $estado['recompensa'] = $resultado['recompensa'];
            $estado['capturas_liberadas'] = $resultado['capturas_liberadas'];
            $estado['resultado_salvo'] = true;
            $_SESSION['batalha'] = $estado;
        }
    }

    private function pokemonAtivo(array $batalha, string $lado): ?array
    {
        $indice = (int) ($batalha[$lado]['ativo'] ?? -1);
        return $batalha[$lado]['equipe'][$indice] ?? null;
    }
}
