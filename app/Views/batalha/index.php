<?php
// Na apresentação: BatalhaController monta $dadosBatalha usando Batalha e
// BatalhaEngine. Esta View mostra o estado e envia a escolha do turno por POST.
if (!defined('POKEBAGUAL_BATALHA_VIEW')) {
    http_response_code(404);
    exit;
}

// O controller entrega o estado do combate; aqui os dados são preparados para apresentação.
$dadosBatalha = $dadosBatalha ?? null;
$treinador = is_array($dadosBatalha['treinador'] ?? null)
    ? $dadosBatalha['treinador']
    : [];
$equipeJogador = is_array($dadosBatalha['jogador']['equipe'] ?? null)
    ? $dadosBatalha['jogador']['equipe']
    : [];
$equipeAdversario = is_array($dadosBatalha['adversario']['equipe'] ?? null)
    ? $dadosBatalha['adversario']['equipe']
    : [];

$nomeTreinador = $treinador['nome'] ?? 'Treinador';
$insignia = $treinador['nome_insignia'] ?? 'Insignia';
$especialidade = $treinador['especialidade'] ?? 'Tipo';
$nivel = (int) ($dadosBatalha['nivel'] ?? $nivel ?? 1);
$finalizada = !empty($dadosBatalha['finalizada']);
$vitoria = ($dadosBatalha['resultado'] ?? null) === 'vitoria';
$mensagens = is_array($dadosBatalha['mensagens'] ?? null)
    ? $dadosBatalha['mensagens']
    : [];

$pokemonJogador = $pokemonJogador ?? null;
$pokemonAdversario = $pokemonAdversario ?? null;
$hpJogador = (int) ($pokemonJogador['hp'] ?? 0);
$hpMaximoJogador = (int) ($pokemonJogador['hp_maximo'] ?? 0);
$hpAdversario = (int) ($pokemonAdversario['hp'] ?? 0);
$hpMaximoAdversario = (int) ($pokemonAdversario['hp_maximo'] ?? 0);
// Converte o HP atual em porcentagem para dimensionar visualmente as barras de vida.
$percentualHpJogador = $hpMaximoJogador > 0
    ? max(0, min(100, (int) round($hpJogador / $hpMaximoJogador * 100)))
    : 0;
$percentualHpAdversario = $hpMaximoAdversario > 0
    ? max(0, min(100, (int) round($hpAdversario / $hpMaximoAdversario * 100)))
    : 0;

// O mapa de nomes cobre variações como "Lt. Surge"; se a imagem faltar, o HTML usa iniciais.
$iniciaisTreinador = strtoupper(substr($nomeTreinador, 0, 2));
$imagensTreinadores = [
    'brock' => 'foto_brock.png',
    'misty' => 'foto_misty.png',
    'lt. surge' => 'foto_ltsurge.png',
    'erika' => 'foto_erika.png',
    'koga' => 'foto_koga.png',
    'sabrina' => 'foto_sabrina.png',
    'blaine' => 'foto_blaine.png',
    'giovanni' => 'foto_giovanni.png',
];
$arquivoImagemTreinador = $imagensTreinadores[strtolower(trim((string) $nomeTreinador))] ?? null;
$imagemTreinadorDisponivel = is_string($arquivoImagemTreinador)
    && is_file(__DIR__ . '/../../../public/css/img/' . $arquivoImagemTreinador);

// Traduz as chaves internas de condição para os rótulos que aparecem na tela.
$nomeStatus = [
    'queimadura' => 'Queimado',
    'veneno' => 'Envenenado',
    'toxico' => 'Envenenamento grave',
    'paralisia' => 'Paralisado',
    'sono' => 'Dormindo',
    'congelamento' => 'Congelado',
    'confusao' => 'Confuso',
];
$rotuloStatus = static function (?string $status) use ($nomeStatus): string {
    return $status === null ? 'Sem condicao' : ($nomeStatus[$status] ?? $status);
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batalha - PokeBagual</title>
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/batalha.css?v=battle-viewport-fit-4">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/music_player.css">
    <script src="/projetoFinalPok-mon/public/js/pocoes_images.js?v=2" defer></script>
</head>
<body class="battle-page">
    <div class="textos_cabecalho">
        <p class="eyebrow">JORNADA KANTO · NÍVEL <?= $nivel ?> DE 8</p>
        <h1>Ginásio <?= htmlspecialchars($especialidade, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="battle-lead">
            Batalha contra <?= htmlspecialchars($nomeTreinador, ENT_QUOTES, 'UTF-8') ?>
            · <?= htmlspecialchars($insignia, ENT_QUOTES, 'UTF-8') ?>
        </p>
    </div>

    <div class="lado-a-lado">
        <div class="info_direita">
            <!-- O cartão resume o líder, sua insígnia e a equipe adversária. -->
            <aside class="trainer-card" aria-labelledby="trainer-title">
                <p class="eyebrow">LÍDER DO GINÁSIO</p>
                <div class="trainer-avatar">
                    <?php if ($imagemTreinadorDisponivel): ?>
                        <img
                            class="trainer-image"
                            src="/projetoFinalPok-mon/public/css/img/<?= rawurlencode($arquivoImagemTreinador) ?>"
                            alt="Retrato de <?= htmlspecialchars($nomeTreinador, ENT_QUOTES, 'UTF-8') ?>"
                        >
                    <?php else: ?>
                        <span aria-hidden="true">
                            <?= htmlspecialchars($iniciaisTreinador, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h2 id="trainer-title"><?= htmlspecialchars($nomeTreinador, ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="trainer-specialty">
                    Especialidade: <?= htmlspecialchars($especialidade, ENT_QUOTES, 'UTF-8') ?>
                </p>
                <div class="badge-preview">
                    <span class="badge-stone" aria-hidden="true"></span>
                    <span><?= htmlspecialchars($insignia, ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <h3>Equipe do lider</h3>
                <ol class="trainer-team">
                    <?php foreach ($equipeAdversario as $indice => $pokemon): ?>
                        <?php $desmaiado = (int) $pokemon['hp'] <= 0; ?>
                        <li class="<?= $desmaiado ? 'is-fainted' : '' ?>">
                            <span><?= $indice + 1 ?></span>
                            <span class="trainer-team-name">
                                <?= htmlspecialchars($pokemon['nome'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($desmaiado): ?>
                                    <small>(Desmaiado)</small>
                                <?php endif; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </aside>
        </div>

        <div class="info_meio">
            <!-- O painel central mostra os combatentes, o registro e as ações do jogador. -->
            <main class="battle-shell">
                <?php if ($erroBatalha !== null): ?>
                    <p class="battle-message battle-message-error" role="alert">
                        <?= htmlspecialchars($erroBatalha, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>

                <?php if ($erroImagem !== null): ?>
                    <p class="battle-message" role="status">
                        <?= htmlspecialchars($erroImagem, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>

                <?php if ($dadosBatalha !== null): ?>
                    <article class="combatant combatant-player">
                        <div class="combatant-portrait" id="pokemon-jogador">
                            <?php if (is_string($imagemJogador)): ?>
                                <img
                                    class="combatant-image"
                                    src="<?= htmlspecialchars($imagemJogador, ENT_QUOTES, 'UTF-8') ?>"
                                    alt="Sprite de <?= htmlspecialchars($pokemonJogador['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            <?php else: ?>
                                <div class="combatant-symbol" aria-hidden="true">?</div>
                            <?php endif; ?>
                        </div>
                        <div class="combatant-copy">
                            <p class="combatant-label">SEU POKÉMON ATIVO · POSIÇÃO <?= (int) ($dadosBatalha['jogador']['ativo'] ?? 0) + 1 ?></p>
                            <h2>
                                <?= $pokemonJogador !== null
                                    ? htmlspecialchars($pokemonJogador['nome'], ENT_QUOTES, 'UTF-8')
                                    : 'Sem Pokémon ativo' ?>
                            </h2>
                            <?php if ($pokemonJogador !== null): ?>
                                <p><?= htmlspecialchars(implode(' / ', $pokemonJogador['tipos']), ENT_QUOTES, 'UTF-8') ?></p>
                                <div class="hp-track" aria-label="HP do seu Pokemon">
                                    <span style="width: <?= $percentualHpJogador ?>%;"></span>
                                </div>
                                <small>
                                HP <?= $hpJogador ?> / <?= $hpMaximoJogador ?>
                                · <?= htmlspecialchars($rotuloStatus($pokemonJogador['status']), ENT_QUOTES, 'UTF-8') ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </article>

                    <article class="combatant combatant-opponent">
                        <div class="combatant-portrait" id="pokemon-adversario">
                            <?php if (is_string($imagemAdversario)): ?>
                                <img
                                    class="combatant-image"
                                    src="<?= htmlspecialchars($imagemAdversario, ENT_QUOTES, 'UTF-8') ?>"
                                    alt="Sprite de <?= htmlspecialchars($pokemonAdversario['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            <?php else: ?>
                                <div class="combatant-symbol" aria-hidden="true">?</div>
                            <?php endif; ?>
                        </div>
                        <div class="combatant-copy">
                            <p class="combatant-label">POKÉMON DO LÍDER · POSIÇÃO <?= (int) ($dadosBatalha['adversario']['ativo'] ?? 0) + 1 ?></p>
                            <h2>
                                <?= $pokemonAdversario !== null
                                    ? htmlspecialchars($pokemonAdversario['nome'], ENT_QUOTES, 'UTF-8')
                                    : 'Equipe adversaria derrotada' ?>
                            </h2>
                            <?php if ($pokemonAdversario !== null): ?>
                                <p><?= htmlspecialchars(implode(' / ', $pokemonAdversario['tipos']), ENT_QUOTES, 'UTF-8') ?></p>
                                <div class="hp-track" aria-label="HP do Pokemon adversario">
                                    <span style="width: <?= $percentualHpAdversario ?>%;"></span>
                                </div>
                                <small>
                                    HP <?= $hpAdversario ?> / <?= $hpMaximoAdversario ?>
                                    · <?= htmlspecialchars($rotuloStatus($pokemonAdversario['status']), ENT_QUOTES, 'UTF-8') ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endif; ?>

                <section class="battle-log" aria-labelledby="battle-log-title" aria-live="polite">
                    <h2 id="battle-log-title">Registro da batalha</h2>
                    <?php if ($mensagens !== []): ?>
                        <ol>
                            <?php foreach ($mensagens as $mensagem): ?>
                                <?php
                                $marcadorTurno = preg_match(
                                    '/\ATurno \d+\.\z/u',
                                    (string) $mensagem
                                ) === 1;
                                ?>
                                <li class="<?= $marcadorTurno ? 'battle-log-turn' : 'battle-log-event' ?>">
                                    <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php else: ?>
                        <p>A batalha comecara quando voce escolher um movimento.</p>
                    <?php endif; ?>
                </section>

                <?php if (!$finalizada && $pokemonJogador !== null): ?>
                    <form class="battle-actions" method="post" action="">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($csrfBatalha, ENT_QUOTES, 'UTF-8') ?>"
                        >
                        <div class="battle-moves">
                            <button type="button" id="abrir-ataques" class="btn-atacar-principal" aria-expanded="false" aria-controls="opcoes-ataques">
                                <img
                                    src="/projetoFinalPok-mon/public/css/img/btn_atacar.png"
                                    alt="Atacar"
                                >
                            </button>

                            <div class="opcoes-ataques" id="opcoes-ataques" hidden>
                                <?php foreach ($pokemonJogador['movimentos'] as $movimento): ?>
                                    <?php
                                    $danoEstimado = (int) $movimento['potencia'] > 0
                                        && $pokemonAdversario !== null
                                        ? \App\Services\BatalhaEngine::calcularDano(
                                            $pokemonJogador,
                                            $pokemonAdversario,
                                            $movimento
                                        )
                                        : 0;
                                    $tipoAtaque = strtolower(
                                        (string) ($movimento['tipo'] ?? 'normal')
                                    );
                                    $tipoClasse = preg_replace(
                                        '/[^a-z0-9_-]/',
                                        '-',
                                        $tipoAtaque
                                    );
                                    ?>
                                    <button
                                        type="submit"
                                        name="id_movimento"
                                        value="<?= (int) $movimento['id_movimento'] ?>"
                                        class="opcao-ataque tipo-<?= htmlspecialchars($tipoClasse, ENT_QUOTES, 'UTF-8') ?>"
                                        title="<?= htmlspecialchars($movimento['nome'] . ' · ' . $movimento['tipo'], ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        <strong><?= htmlspecialchars($movimento['nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <small class="attack-details">
                                            <?= htmlspecialchars($movimento['tipo'], ENT_QUOTES, 'UTF-8') ?>
                                            · Dano
                                            <?= $danoEstimado > 0 ? $danoEstimado . ' HP' : '—' ?>
                                        </small>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="battle-options">
                            <?php
                            $indiceAtivoJogador = (int) ($dadosBatalha['jogador']['ativo'] ?? -1);
                            ?>
                            <div class="battle-option-control">
                                <button
                                    type="button"
                                    id="abrir-troca"
                                    class="battle-menu-button"
                                    aria-expanded="false"
                                    aria-controls="opcoes-pokemon-troca"
                                >
                                    <img src="/projetoFinalPok-mon/public/css/img/btn_trocar_pokemon.png" alt="Trocar Pokémon">
                                </button>
                                <div class="battle-choice-panel" id="opcoes-pokemon-troca" hidden>
                                    <?php foreach ($equipeJogador as $indice => $pokemonEquipe): ?>
                                        <?php if ($indice === $indiceAtivoJogador): ?>
                                            <?php continue; ?>
                                        <?php endif; ?>
                                        <button
                                            type="submit"
                                            name="trocar_pokemon"
                                            value="<?= (int) $indice ?>"
                                            class="battle-choice pokemon-choice"
                                            <?= (int) $pokemonEquipe['hp'] <= 0 ? 'disabled' : '' ?>
                                        >
                                            <?php if (is_string($imagensEquipeJogador[$indice] ?? null)): ?>
                                                <img
                                                    src="<?= htmlspecialchars($imagensEquipeJogador[$indice], ENT_QUOTES, 'UTF-8') ?>"
                                                    alt=""
                                                    loading="lazy"
                                                >
                                            <?php else: ?>
                                                <span class="pokemon-choice-fallback" aria-hidden="true">
                                                    <?= htmlspecialchars(strtoupper(substr($pokemonEquipe['nome'], 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            <?php endif; ?>
                                            <span>
                                                <strong><?= htmlspecialchars($pokemonEquipe['nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                <small>HP <?= (int) $pokemonEquipe['hp'] ?> / <?= (int) $pokemonEquipe['hp_maximo'] ?></small>
                                            </span>
                                        </button>
                                    <?php endforeach; ?>
                                    <?php if (count(array_filter(
                                        $equipeJogador,
                                        static fn (array $pokemon): bool =>
                                            (int) ($pokemon['hp'] ?? 0) > 0
                                            && (int) ($pokemon['posicao'] ?? 0) !== $indiceAtivoJogador + 1
                                    )) === 0): ?>
                                        <p>Não há outros Pokémon no time para trocar.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="battle-option-control">
                                <button
                                    type="button"
                                    id="abrir-itens"
                                    class="battle-menu-button"
                                    aria-expanded="false"
                                    aria-controls="opcoes-itens"
                                >
                                    <img src="/projetoFinalPok-mon/public/css/img/btn_usar_item.png" alt="Usar item">
                                </button>
                                <div class="battle-choice-panel item-choice-panel" id="opcoes-itens" hidden>
                                    <?php if ($inventario !== []): ?>
                                        <label class="item-target-label" for="indice-alvo-item">Usar em:</label>
                                        <select name="indice_alvo_item" id="indice-alvo-item">
                                            <?php foreach ($equipeJogador as $indice => $pokemonEquipe): ?>
                                                <option value="<?= (int) $indice ?>">
                                                    <?= htmlspecialchars($pokemonEquipe['nome'], ENT_QUOTES, 'UTF-8') ?>
                                                    · HP <?= (int) $pokemonEquipe['hp'] ?>/<?= (int) $pokemonEquipe['hp_maximo'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                    <?php foreach ($inventario as $item): ?>
                                        <button
                                            type="submit"
                                            name="usar_item"
                                            value="<?= (int) $item['id_item'] ?>"
                                            class="battle-choice item-choice"
                                        >
                                            <img
                                                class="imagem-item"
                                                data-nome="<?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                                alt=""
                                                loading="lazy"
                                            >
                                            <span class="item-choice-copy">
                                                <strong><?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                <small>Quantidade: <?= (int) $item['quantidade'] ?></small>
                                            </span>
                                        </button>
                                    <?php endforeach; ?>
                                    <?php if ($inventario === []): ?>
                                        <p>Você ainda não possui itens para usar.</p>
                                        <a href="/projetoFinalPok-mon/public/index.php?rota=bolicho">Ir ao Bolicho</a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <button
                                type="button"
                                id="abrir-confirmacao-fuga"
                                class="battle-menu-button"
                            >
                                <img src="/projetoFinalPok-mon/public/css/img/btn_fugir.png" alt="Fugir">
                            </button>

                            <dialog
                                id="confirmacao-fuga"
                                class="flee-confirmation"
                                aria-labelledby="confirmacao-fuga-titulo"
                            >
                                <h2 id="confirmacao-fuga-titulo">Você tem certeza que vai fugir?</h2>
                                <p>
                                    O HP atual dos seus Pokémon será salvo. O time do adversário
                                    estará recuperado quando você iniciar outra batalha.
                                </p>
                                <div class="flee-confirmation-actions">
                                    <button
                                        type="button"
                                        id="cancelar-fuga"
                                        class="flee-cancel-button"
                                    >
                                        Continuar batalhando
                                    </button>
                                    <button
                                        type="submit"
                                        name="acao"
                                        value="confirmar_fuga"
                                        class="flee-confirm-button"
                                    >
                                        Confirmar e ir ao mapa
                                    </button>
                                </div>
                            </dialog>
                        </div>
                    </form>
                <?php elseif ($finalizada): ?>
                    <section class="battle-finish" aria-live="polite">
                        <?php if ($vitoria): ?>
                            <h2>Voce venceu!</h2>
                            <p>Recompensa: <strong><?= (int) $dadosBatalha['recompensa'] ?> PokePila</strong></p>
                            <a class="battle-submit" href="/projetoFinalPok-mon/public/index.php?rota=captura">Ver captura</a>
                        <?php else: ?>
                            <h2>Voce perdeu a batalha</h2>
                            <p>Visite o Pokecenter e tente novamente.</p>
                            <form method="post" action="">
                                <input type="hidden" name="csrf_token"
                                    value="<?= htmlspecialchars($csrfBatalha, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="acao" value="reiniciar">
                                <button class="battle-submit" type="submit">Reiniciar batalha</button>
                            </form>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <a href="/projetoFinalPok-mon/public/index.php?rota=mapa" class="btn_pixel">
        <img src="/projetoFinalPok-mon/public/css/img/btn_voltar.png" alt="Voltar"
             style="width: 200px !important; height: auto;">
    </a>
    <script>
        // Mantém apenas um painel de ação aberto por vez.
        const menusAcao = [
            ['abrir-ataques', 'opcoes-ataques'],
            ['abrir-troca', 'opcoes-pokemon-troca'],
            ['abrir-itens', 'opcoes-itens'],
        ].map(function ([idBotao, idPainel]) {
            const botao = document.getElementById(idBotao);
            const painel = document.getElementById(idPainel);
            return { botao: botao, painel: painel };
        }).filter(function (menu) {
            return menu.botao && menu.painel;
        });

        menusAcao.forEach(function (menuSelecionado) {
            menuSelecionado.botao.addEventListener('click', function () {
                const deveAbrir = menuSelecionado.painel.hidden;

                menusAcao.forEach(function (menu) {
                    menu.painel.hidden = true;
                    menu.botao.setAttribute('aria-expanded', 'false');
                });

                if (deveAbrir) {
                    menuSelecionado.painel.hidden = false;
                    menuSelecionado.botao.setAttribute('aria-expanded', 'true');
                }
            });
        });

        const botaoFugir = document.getElementById('abrir-confirmacao-fuga');
        const confirmacaoFuga = document.getElementById('confirmacao-fuga');
        const cancelarFuga = document.getElementById('cancelar-fuga');
        if (botaoFugir && confirmacaoFuga && cancelarFuga) {
            // showModal abre a confirmação nativa sem enviar o formulário de fuga.
            botaoFugir.addEventListener('click', function () {
                confirmacaoFuga.showModal();
            });
            // Cancelar fecha o diálogo e mantém a batalha atual.
            cancelarFuga.addEventListener('click', function () {
                confirmacaoFuga.close();
            });
        }
    </script>
    <?php require __DIR__ . '/../layouts/music_player.php'; ?>
</body>
</html>
