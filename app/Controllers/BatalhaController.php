<?php

use App\Models\Batalha;
use App\Services\BatalhaEngine;


class BatalhaController
{
    private PDO $db;

    /** Recebe a conexão usada pelos modelos da batalha. */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Carrega ou processa a batalha do nível solicitado e prepara os dados da tela.
     *
     * A sessão guarda o combate temporário; resultados finais são enviados ao Model
     * para serem gravados no banco.
     */
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
        $imagensEquipeJogador = [];
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
                        // Guarda a luta incompleta para permitir alternar entre ginásios sem perder o estado.
                        $nivelSuspenso = (int) $estadoSessao['nivel'];
                        if (!isset($_SESSION['batalhas_suspensas'][$idUsuario])) {
                            $_SESSION['batalhas_suspensas'][$idUsuario] = [];
                        }
                        $_SESSION['batalhas_suspensas'][$idUsuario][$nivelSuspenso] = $estadoSessao;
                    }

                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $this->processarAcao($idUsuario, $nivel, $modeloBatalha);
                        $estadoAtual = $_SESSION['batalha'] ?? null;
                        if (
                            is_array($estadoAtual)
                            && !empty($estadoAtual['fugiu'])
                            && !empty($estadoAtual['resultado_salvo'])
                        ) {
                            unset($_SESSION['batalha']);
                            header('Location: index.php?rota=mapa');
                        } elseif (
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
                        // Ao voltar a um ginásio, restaura a luta pausada; caso não exista, inicia uma nova.
                        $estadoSuspenso = $_SESSION['batalhas_suspensas'][$idUsuario][$nivel] ?? null;
                        if (
                            is_array($estadoSuspenso)
                            && (int) ($estadoSuspenso['id_usuario'] ?? 0) === $idUsuario
                            && (int) ($estadoSuspenso['nivel'] ?? 0) === $nivel
                            && empty($estadoSuspenso['finalizada'])
                        ) {
                            $estadoSessao = $estadoSuspenso;
                            unset($_SESSION['batalhas_suspensas'][$idUsuario][$nivel]);
                        } else {
                            $estadoSessao = $modeloBatalha->carregarDadosCombate($idUsuario, $nivel);
                        }
                        $_SESSION['batalha'] = $estadoSessao;
                    }

                    if (
                        !empty($estadoSessao['finalizada'])
                        && empty($estadoSessao['resultado_salvo'])
                    ) {
                        $resultado = $modeloBatalha->registrarResultado($idUsuario, $estadoSessao);
                        $estadoSessao['recompensa'] = $resultado['recompensa'];
                        $estadoSessao['capturas_liberadas'] = $resultado['capturas_liberadas'];
                        $estadoSessao['id_batalha'] = $resultado['id_batalha'];
                        $estadoSessao['captura'] = $resultado['captura'];
                        $estadoSessao['primeira_vitoria'] = $resultado['primeira_vitoria'];
                        $estadoSessao['fase_pos_vitoria'] = $resultado['captura'] === null
                            ? 'captura'
                            : 'resultado';
                        $estadoSessao['tentativa_captura_processada'] = $resultado['captura'] !== null;
                        $estadoSessao['resultado_salvo'] = true;
                        $_SESSION['batalha'] = $estadoSessao;
                    }

                    if (
                        !empty($estadoSessao['fugiu'])
                        && !empty($estadoSessao['resultado_salvo'])
                    ) {
                        unset($_SESSION['batalha']);
                        header('Location: index.php?rota=mapa');
                        exit;
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
                            // A PokeAPI fornece apenas a imagem; os dados e regras da batalha vêm do sistema.
                            $servico = new PokeApiService();
                            if ($pokemonJogador !== null) {
                                $imagemJogador = $servico->obterImagem($pokemonJogador['nome']);
                            }
                            if ($pokemonAdversario !== null) {
                                $imagemAdversario = $servico->obterImagem($pokemonAdversario['nome']);
                            }
                        } catch (RuntimeException | JsonException $exception) {
                            // Falha visual não cancela a batalha nem altera seu estado persistido.
                            error_log('Erro ao carregar imagens da batalha: ' . $exception->getMessage());
                            $erroImagem = 'Não foi possível carregar as imagens pela PokeAPI agora.';
                        }

                        foreach ($estadoSessao['jogador']['equipe'] as $indice => $pokemonEquipe) {
                            if (
                                $indice === (int) ($estadoSessao['jogador']['ativo'] ?? -1)
                                && is_string($imagemJogador)
                            ) {
                                $imagensEquipeJogador[$indice] = $imagemJogador;
                                continue;
                            }
                            if (
                                $indice === (int) ($estadoSessao['jogador']['ativo'] ?? -1)
                                && $erroImagem !== null
                            ) {
                                $imagensEquipeJogador[$indice] = null;
                                continue;
                            }
                            try {
                                $imagensEquipeJogador[$indice] = (new PokeApiService())
                                    ->obterImagem((string) $pokemonEquipe['nome']);
                            } catch (RuntimeException | JsonException $exception) {
                                error_log(
                                    'Erro ao carregar sprite de '
                                    . $pokemonEquipe['nome'] . ': '
                                    . $exception->getMessage()
                                );
                                $imagensEquipeJogador[$indice] = null;
                            }
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

    /**
     * Conduz as etapas pós-vitória: tentativa de captura, resultado e insígnia.
     *
     * A tentativa acontece somente após um POST válido com token CSRF.
     */
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

        $csrfBatalha = $_SESSION['csrf_batalha'] ?? null;
        if (!is_string($csrfBatalha) || $csrfBatalha === '') {
            $csrfBatalha = bin2hex(random_bytes(32));
            $_SESSION['csrf_batalha'] = $csrfBatalha;
        }

        $erroCaptura = null;
        $fasePosVitoria = $batalha['fase_pos_vitoria'] ?? null;
        if (!in_array($fasePosVitoria, ['captura', 'resultado', 'insignia'], true)) {
            $fasePosVitoria = is_array($batalha['captura'] ?? null)
                ? 'resultado'
                : 'captura';
            $batalha['fase_pos_vitoria'] = $fasePosVitoria;
            $batalha['tentativa_captura_processada'] = is_array($batalha['captura'] ?? null);
            $_SESSION['batalha'] = $batalha;
        }

        $modeloBatalha = new Batalha($this->db);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tokenEnviado = $_POST['csrf_token'] ?? '';
            if (
                !is_string($tokenEnviado)
                || !hash_equals($csrfBatalha, $tokenEnviado)
            ) {
                http_response_code(400);
                $erroCaptura = 'A solicitação expirou. Atualize a página e tente novamente.';
            } else {
                $acao = $_POST['acao'] ?? '';
                try {
                    if (
                        $acao === 'tentar_capturar'
                        && $fasePosVitoria === 'captura'
                        && empty($batalha['tentativa_captura_processada'])
                    ) {
                        $captura = $modeloBatalha->tentarCaptura($idUsuario, $batalha);
                        $batalha['captura'] = $captura;
                        $batalha['tentativa_captura_processada'] = true;
                        $batalha['fase_pos_vitoria'] = 'resultado';
                        $_SESSION['batalha'] = $batalha;
                    } elseif ($acao === 'prosseguir') {
                        $batalha['fase_pos_vitoria'] = 'insignia';
                        $_SESSION['batalha'] = $batalha;
                    } else {
                        http_response_code(400);
                        $erroCaptura = 'A ação solicitada não é válida para esta etapa.';
                    }
                } catch (PDOException $exception) {
                    error_log('Erro de banco de dados na tentativa de captura: ' . $exception->getMessage());
                    http_response_code(500);
                    $erroCaptura = 'Não foi possível processar a tentativa de captura agora.';
                } catch (RuntimeException $exception) {
                    error_log('Erro ao processar tentativa de captura: ' . $exception->getMessage());
                    http_response_code(400);
                    $erroCaptura = $exception->getMessage();
                }
            }

            if ($erroCaptura === null) {
                header('Location: index.php?rota=captura');
                exit;
            }
        }

        $batalha = $_SESSION['batalha'];
        $fasePosVitoria = $batalha['fase_pos_vitoria'];
        $captura = is_array($batalha['captura'] ?? null)
            ? $batalha['captura']
            : null;
        $pokemonTentativa = null;
        $imagemPokemonTentativa = null;
        $erroImagemCaptura = null;

        if ($fasePosVitoria === 'captura' && $captura === null) {
            try {
                $pokemonTentativa = $modeloBatalha->carregarProximaCaptura($idUsuario, $batalha);
                if ($pokemonTentativa !== null) {
                    // Carrega a arte da espécie apresentada na tela de tentativa de captura.
                    $imagemPokemonTentativa = (new PokeApiService())
                        ->obterImagem($pokemonTentativa['nome']);
                }
            } catch (PDOException | RuntimeException | JsonException $exception) {
                error_log('Erro ao preparar a tentativa de captura: ' . $exception->getMessage());
                $erroImagemCaptura = 'Não foi possível carregar a imagem do Pokémon agora.';
            }
        }

        $destinoContinuar = '/projetoFinalPok-mon/public/index.php?rota=mapa';
        if ((int) $batalha['nivel'] === 8) {
            try {
                $progressoNiveis = $modeloBatalha->carregarProgressoNiveis($idUsuario);
                $niveisVencidos = count(array_filter(
                    $progressoNiveis,
                    static fn (array $nivel): bool => (int) $nivel['vitorias'] > 0
                ));
                if ($niveisVencidos === 8) {
                    $destinoContinuar = '/projetoFinalPok-mon/public/index.php?rota=campeao';
                }
            } catch (PDOException | RuntimeException $exception) {
                error_log('Erro ao verificar a conclusão da jornada: ' . $exception->getMessage());
                http_response_code(500);
                $erroCaptura = 'Não foi possível carregar o progresso da jornada agora.';
            }
        }

        define('POKEBAGUAL_CAPTURA_VIEW', true);
        require __DIR__ . '/../Views/batalha/captura.php';
    }

    /** Exibe a conclusão da jornada e processa o reinício confirmado pelo jogador. */
    public function campeao(): void
    {
        $idUsuario = filter_var(
            $_SESSION['usuario']['id_usuario'] ?? null,
            FILTER_VALIDATE_INT
        );
        if ($idUsuario === false || $idUsuario === null || $idUsuario < 1) {
            header('Location: index.php?rota=login');
            exit;
        }

        $erroCampeao = null;
        $progressoNiveis = [];
        $perfil = null;
        $batalhaFinal = is_array($_SESSION['batalha'] ?? null)
            ? $_SESSION['batalha']
            : [];
        $capturaFinal = is_array($batalhaFinal['captura'] ?? null)
            ? $batalhaFinal['captura']
            : null;

        if (
            !is_string($_SESSION['csrf_reiniciar_jornada'] ?? null)
            || $_SESSION['csrf_reiniciar_jornada'] === ''
        ) {
            $_SESSION['csrf_reiniciar_jornada'] = bin2hex(random_bytes(32));
        }
        $csrfReiniciarJornada = $_SESSION['csrf_reiniciar_jornada'];

        try {
            $modeloBatalha = new Batalha($this->db);
            $progressoNiveis = $modeloBatalha->carregarProgressoNiveis($idUsuario);
            $niveisVencidos = count(array_filter(
                $progressoNiveis,
                static fn (array $nivel): bool => (int) $nivel['vitorias'] > 0
            ));
            if ($niveisVencidos !== 8) {
                header('Location: index.php?rota=mapa');
                exit;
            }

            $perfil = (new Usuario($this->db))->buscarPerfil($idUsuario);
            if ($perfil === null) {
                unset($_SESSION['usuario']);
                header('Location: index.php?rota=login');
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $tokenEnviado = $_POST['csrf_token'] ?? '';
                if (
                    ($_POST['acao'] ?? '') !== 'reiniciar_jornada'
                    || !is_string($tokenEnviado)
                    || !hash_equals($csrfReiniciarJornada, $tokenEnviado)
                ) {
                    http_response_code(400);
                    $erroCampeao = 'A solicitação expirou. Atualize a página e tente novamente.';
                } else {
                    $modeloBatalha->reiniciarJornada($idUsuario);
                    unset(
                        $_SESSION['batalha'],
                        $_SESSION['csrf_batalha'],
                        $_SESSION['csrf_escolher_time'],
                        $_SESSION['csrf_reiniciar_jornada'],
                        $_SESSION['batalhas_suspensas']
                    );
                    header('Location: index.php?rota=escolher_time');
                    exit;
                }
            }
        } catch (PDOException $exception) {
            error_log('Erro de banco de dados na tela de campeão: ' . $exception->getMessage());
            http_response_code(500);
            $erroCampeao = 'Não foi possível carregar ou reiniciar a jornada agora.';
        } catch (RuntimeException $exception) {
            error_log('Erro na jornada de campeão: ' . $exception->getMessage());
            http_response_code(500);
            $erroCampeao = 'Não foi possível carregar ou reiniciar a jornada agora.';
        }

        define('POKEBAGUAL_CAMPEAO_VIEW', true);
        require __DIR__ . '/../Views/batalha/campeao.php';
    }

    /**
     * Valida o formulário e encaminha a ação para o engine de batalha.
     *
     * Após a ação, o estado atualizado fica na sessão e um combate encerrado
     * é registrado pelo Model.
     */
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
        if (isset($_POST['trocar_pokemon'])) {
            $acao = 'trocar_pokemon';
        } elseif (isset($_POST['usar_item'])) {
            $acao = 'usar_item';
        }
        if ($acao === 'trocar_pokemon') {
            $indiceTroca = filter_var(
                $_POST['trocar_pokemon'] ?? $_POST['indice_troca'] ?? null,
                FILTER_VALIDATE_INT
            );
            if ($indiceTroca === false || $indiceTroca === null) {
                throw new InvalidArgumentException('Escolha um Pokémon válido para trocar.');
            }
            $estado = BatalhaEngine::resolverTroca($estado, $indiceTroca);
        } elseif ($acao === 'usar_item') {
            $idItem = filter_var(
                $_POST['usar_item'] ?? $_POST['id_item'] ?? null,
                FILTER_VALIDATE_INT
            );
            if ($idItem === false || $idItem === null) {
                throw new InvalidArgumentException('Escolha um item válido.');
            }
            $item = $this->buscarItemInventario($idUsuario, $idItem);
            if ($item === null) {
                throw new InvalidArgumentException('Você não possui esse item no inventário.');
            }
            $indiceAlvo = filter_var(
                $_POST['indice_alvo_item'] ?? $estado['jogador']['ativo'],
                FILTER_VALIDATE_INT
            );
            if ($indiceAlvo === false || $indiceAlvo === null) {
                throw new InvalidArgumentException('Escolha um Pokémon válido para usar o item.');
            }
            $estado = BatalhaEngine::resolverUsoItem($estado, $item, $indiceAlvo);
            $this->consumirItem($idUsuario, $idItem);
        } elseif ($acao === 'confirmar_fuga') {
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
            $estado['primeira_vitoria'] = $resultado['primeira_vitoria'];
            $estado['resultado_salvo'] = true;
            $_SESSION['batalha'] = $estado;
        }
    }

    /** Obtém o Pokémon indicado pelo índice ativo de uma das equipes. */
    private function pokemonAtivo(array $batalha, string $lado): ?array
    {
        $indice = (int) ($batalha[$lado]['ativo'] ?? -1);
        return $batalha[$lado]['equipe'][$indice] ?? null;
    }

    /** Carrega os itens que podem ser usados durante a batalha. */
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
               AND item.tipo IN (\'cura_hp\', \'cura_hp_status\', \'cura_status\', \'revive\')
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

    /** Retorna o item somente quando ele pertence ao inventário do usuário. */
    private function buscarItemInventario(int $idUsuario, int $idItem): ?array
    {
        foreach ($this->carregarInventario($idUsuario) as $item) {
            if ($item['id_item'] === $idItem) {
                return $item;
            }
        }
        return null;
    }

    /** Desconta uma unidade do item usado após a validação do inventário. */
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
