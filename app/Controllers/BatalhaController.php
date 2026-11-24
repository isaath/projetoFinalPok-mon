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
                        $estadoAtual = $_SESSION['batalha'] ?? null;
                        if (
                            is_array($estadoAtual)
                            && !empty($estadoAtual['finalizada'])
                            && ($estadoAtual['resultado'] ?? null) === 'vitoria'
                            && !empty($estadoAtual['resultado_salvo'])
                        ) {
                            header('Location: index.php?rota=captura');
                        } else {
                            header('Location: index.php?rota=batalha&nivel=' . $nivel);
                        }
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
                        $estadoSessao['captura'] = $resultado['captura'];
                        $estadoSessao['resultado_salvo'] = true;
                        $_SESSION['batalha'] = $estadoSessao;
                    }

                    if (
                        !empty($estadoSessao['finalizada'])
                        && ($estadoSessao['resultado'] ?? null) === 'vitoria'
                        && !empty($estadoSessao['resultado_salvo'])
                    ) {
                        header('Location: index.php?rota=captura');
                        exit;
                    }

                    $dadosBatalha = $estadoSessao;
                    $pokemonJogador = $this->pokemonAtivo($estadoSessao, 'jogador');
                    $pokemonAdversario = $this->pokemonAtivo($estadoSessao, 'adversario');
                    if ($pokemonJogador !== null || $pokemonAdversario !== null) {
                        try {
                            $servico = new PokeApiService();
                            if ($pokemonJogador !== null) {
                                $imagemJogador = $servico->obterImagem($pokemonJogador['nome']);
                            }
                            if ($pokemonAdversario !== null) {
                                $imagemAdversario = $servico->obterImagem($pokemonAdversario['nome']);
                            }
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
        $inventario = $dadosBatalha === null
            ? []
            : $this->carregarInventario($idUsuario);

        define('POKEBAGUAL_BATALHA_VIEW', true);
        require __DIR__ . '/../Views/batalha/index.php';
    }

    public function captura(): void
    {
        $idUsuario = filter_var(
            $_SESSION['usuario']['id_usuario'] ?? null,
            FILTER_VALIDATE_INT
        );
        if ($idUsuario === false || $idUsuario === null || $idUsuario < 1) {
            header('Location: index.php?rota=login');
            exit;
        }

        $batalha = $_SESSION['batalha'] ?? null;
        if (
            !is_array($batalha)
            || (int) ($batalha['id_usuario'] ?? 0) !== $idUsuario
            || empty($batalha['finalizada'])
            || ($batalha['resultado'] ?? null) !== 'vitoria'
            || empty($batalha['resultado_salvo'])
        ) {
            header('Location: index.php?rota=mapa');
            exit;
        }

        $captura = is_array($batalha['captura'] ?? null)
            ? $batalha['captura']
            : null;
        $csrfBatalha = $_SESSION['csrf_batalha'] ?? null;
        if (!is_string($csrfBatalha) || $csrfBatalha === '') {
            $csrfBatalha = bin2hex(random_bytes(32));
            $_SESSION['csrf_batalha'] = $csrfBatalha;
        }

        define('POKEBAGUAL_CAPTURA_VIEW', true);
        require __DIR__ . '/../Views/batalha/captura.php';
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

        $acao = (string) ($_POST['acao'] ?? '');
        if ($acao === 'trocar_pokemon') {
            $indiceTroca = filter_var(
                $_POST['indice_troca'] ?? null,
                FILTER_VALIDATE_INT
            );
            if ($indiceTroca === false || $indiceTroca === null) {
                throw new InvalidArgumentException('Escolha um Pokémon válido para trocar.');
            }
            $estado = BatalhaEngine::resolverTroca($estado, $indiceTroca);
        } elseif ($acao === 'usar_item') {
            $idItem = filter_var(
                $_POST['id_item'] ?? null,
                FILTER_VALIDATE_INT
            );
            if ($idItem === false || $idItem === null) {
                throw new InvalidArgumentException('Escolha um item válido.');
            }
            $item = $this->buscarItemInventario($idUsuario, $idItem);
            if ($item === null) {
                throw new InvalidArgumentException('Você não possui esse item no inventário.');
            }
            $estado = BatalhaEngine::resolverUsoItem($estado, $item);
            $this->consumirItem($idUsuario, $idItem);
        } elseif ($acao === 'fugir') {
            $estado = BatalhaEngine::resolverFuga($estado);
        } elseif ($acao === '' || $acao === 'atacar') {
            $idMovimento = filter_var(
                $_POST['id_movimento'] ?? null,
                FILTER_VALIDATE_INT
            );
            if ($idMovimento === false || $idMovimento === null || $idMovimento < 1) {
                throw new InvalidArgumentException('Escolha um movimento válido.');
            }
            $estado = BatalhaEngine::resolverTurno($estado, $idMovimento);
        } else {
            throw new InvalidArgumentException('A ação escolhida não é válida.');
        }
        $_SESSION['batalha'] = $estado;
        if (!empty($estado['finalizada'])) {
            $resultado = $modeloBatalha->registrarResultado($idUsuario, $estado);
            $estado['recompensa'] = $resultado['recompensa'];
            $estado['capturas_liberadas'] = $resultado['capturas_liberadas'];
            $estado['captura'] = $resultado['captura'];
            $estado['resultado_salvo'] = true;
            $_SESSION['batalha'] = $estado;
        }
    }

    private function pokemonAtivo(array $batalha, string $lado): ?array
    {
        $indice = (int) ($batalha[$lado]['ativo'] ?? -1);
        return $batalha[$lado]['equipe'][$indice] ?? null;
    }

    private function carregarInventario(int $idUsuario): array
    {
        $consulta = $this->db->prepare(
            'SELECT item.id_item, item.nome, item.tipo,
                    item.valor_efeito, inventario.quantidade
             FROM inventario AS inventario
             INNER JOIN itens_loja AS item
                ON item.id_item = inventario.id_item
             WHERE inventario.id_usuario = :id_usuario
               AND inventario.quantidade > 0
             ORDER BY item.nome'
        );
        $consulta->execute(['id_usuario' => $idUsuario]);
        $itens = $consulta->fetchAll(PDO::FETCH_ASSOC);
        foreach ($itens as &$item) {
            $item['id_item'] = (int) $item['id_item'];
            $item['valor_efeito'] = (int) $item['valor_efeito'];
            $item['quantidade'] = (int) $item['quantidade'];
        }
        unset($item);
        return $itens;
    }

    private function buscarItemInventario(int $idUsuario, int $idItem): ?array
    {
        foreach ($this->carregarInventario($idUsuario) as $item) {
            if ($item['id_item'] === $idItem) {
                return $item;
            }
        }
        return null;
    }

    private function consumirItem(int $idUsuario, int $idItem): void
    {
        $this->db->beginTransaction();
        try {
            $consulta = $this->db->prepare(
                'UPDATE inventario
                 SET quantidade = quantidade - 1
                 WHERE id_usuario = :id_usuario
                   AND id_item = :id_item
                   AND quantidade > 0'
            );
            $consulta->execute([
                'id_usuario' => $idUsuario,
                'id_item' => $idItem,
            ]);
            if ($consulta->rowCount() !== 1) {
                throw new InvalidArgumentException('Não foi possível consumir o item.');
            }
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }
}
