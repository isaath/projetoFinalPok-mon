<?php
// Na apresentação: após a vitória, BatalhaController entrega o resultado já
// persistido; os formulários pedem a tentativa de captura ou a próxima ação.
if (!defined('POKEBAGUAL_CAPTURA_VIEW')) {
    http_response_code(404);
    exit;
}

// A fase define se a tela oferece captura, mostra o resultado ou apresenta a insígnia.
$treinador = $batalha['treinador'] ?? [];
$nomeTreinador = (string) ($treinador['nome'] ?? 'Treinador');
$nomeInsignia = (string) ($treinador['nome_insignia'] ?? 'Insígnia');
$nivel = (int) ($batalha['nivel'] ?? 1);
$recompensa = (int) ($batalha['recompensa'] ?? 0);
$primeiraVitoria = !empty($batalha['primeira_vitoria']);
$resultadoCaptura = is_array($captura) ? ($captura['resultado'] ?? null) : null;
$nomePokemon = is_array($captura) && is_string($captura['nome'] ?? null)
    ? $captura['nome']
    : null;
$pokemonTentativa = is_array($pokemonTentativa ?? null) ? $pokemonTentativa : null;
$imagemPokemonTentativa = is_string($imagemPokemonTentativa ?? null)
    ? $imagemPokemonTentativa
    : null;
$capturaProcessada = !empty($batalha['tentativa_captura_processada']);
$pokemonsVivos = count(array_filter(
    $batalha['jogador']['equipe'] ?? [],
    static fn (array $pokemon): bool => (int) ($pokemon['hp'] ?? 0) > 0
));
// Cada nível recebe uma combinação própria de cores para representar sua insígnia.
$coresInsignia = [
    1 => ['#9da9b6', '#dce4ef'],
    2 => ['#397bbd', '#a8d7f0'],
    3 => ['#d3a91f', '#fff0a8'],
    4 => ['#39864a', '#b9e3aa'],
    5 => ['#7951a1', '#d7c2ef'],
    6 => ['#c45683', '#f4c2d5'],
    7 => ['#d25032', '#ffc08b'],
    8 => ['#876234', '#e3c88a'],
];
$cores = $coresInsignia[$nivel] ?? ['#69768a', '#dce4ef'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $fasePosVitoria === 'insignia' ? 'Insígnia conquistada' : 'Tentativa de captura' ?> | PokeBagual</title>
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/captura.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/music_player.css">
</head>
<body class="post-battle-page">
    <main class="post-battle-shell">
        <p class="post-battle-eyebrow">JORNADA KANTO · NÍVEL <?= $nivel ?> DE 8</p>

        <!-- Antes da insígnia, o jogador pode tentar capturar ou apenas prosseguir. -->
        <?php if ($fasePosVitoria !== 'insignia'): ?>
            <h1>Uma captura pode estar esperando você!</h1>
            <p class="post-battle-lead">
                Você venceu <?= htmlspecialchars($nomeTreinador, ENT_QUOTES, 'UTF-8') ?>
                e recebeu <?= $recompensa ?>
                <img
                    class="reward-coin"
                    src="/projetoFinalPok-mon/public/css/img/moeda.png"
                    alt="PokePila"
                >.
            </p>

            <?php if ($fasePosVitoria === 'captura'): ?>
                <?php if ($pokemonTentativa !== null): ?>
                    <section class="capture-prompt" aria-live="polite">
                        <?php if ($imagemPokemonTentativa !== null): ?>
                            <img
                                class="capture-pokemon-image"
                                src="<?= htmlspecialchars($imagemPokemonTentativa, ENT_QUOTES, 'UTF-8') ?>"
                                alt="Imagem de <?= htmlspecialchars($pokemonTentativa['nome'], ENT_QUOTES, 'UTF-8') ?>"
                            >
                        <?php else: ?>
                            <div class="capture-pokemon-fallback" aria-hidden="true">?</div>
                        <?php endif; ?>
                        <h2>
                            Olha só, você pode tentar capturar o Pokémon
                            <?= htmlspecialchars($pokemonTentativa['nome'], ENT_QUOTES, 'UTF-8') ?>!
                        </h2>
                    </section>
                    <?php if ($erroImagemCaptura !== null): ?>
                        <p class="post-battle-notice" role="status">
                            <?= htmlspecialchars($erroImagemCaptura, ENT_QUOTES, 'UTF-8') ?>
                            Você ainda pode tentar a captura.
                        </p>
                    <?php endif; ?>
                <?php elseif ($capturaProcessada): ?>
                    <section class="capture-prompt">
                        <h2>Você já capturou todos os Pokémon liberados por este líder.</h2>
                    </section>
                <?php else: ?>
                    <section class="capture-prompt">
                        <h2>Não há outro Pokémon liberado para captura nesta vitória.</h2>
                    </section>
                <?php endif; ?>
            <?php elseif ($captura === null): ?>
                <section class="capture-prompt">
                    <h2>Não há outro Pokémon liberado para captura nesta vitória.</h2>
                </section>
            <?php endif; ?>

            <?php if ($fasePosVitoria === 'resultado' && $captura !== null): ?>
                <?php $capturaSucesso = $resultadoCaptura !== 'falhou'; ?>
                <section class="capture-outcome" aria-live="polite">
                    <svg
                        class="capture-ball <?= $capturaSucesso ? 'capture-ball-success' : 'capture-ball-failure' ?>"
                        viewBox="0 0 120 100"
                        role="img"
                        aria-label="<?= $capturaSucesso ? 'Pokébola fechada: captura bem-sucedida' : 'Pokébola aberta e vazia: captura falhou' ?>"
                    >
                        <?php if ($capturaSucesso): ?>
                            <path d="M24 50a36 36 0 0 1 72 0" fill="#e84b4b" stroke="#17243b" stroke-width="7"/>
                            <path d="M24 50a36 36 0 0 0 72 0" fill="#f8f8f2" stroke="#17243b" stroke-width="7"/>
                            <path d="M24 50h72" stroke="#17243b" stroke-width="8"/>
                            <circle cx="60" cy="50" r="11" fill="#fff" stroke="#17243b" stroke-width="6"/>
                            <path d="m101 6 4 9 10 1-7 7 2 10-9-5-9 5 2-10-7-7 10-1z" fill="#f8d030" stroke="#17243b" stroke-width="2"/>
                        <?php else: ?>
                            <path d="M22 48a38 38 0 0 1 69-22L70 48z" fill="#e84b4b" stroke="#17243b" stroke-width="7" stroke-linejoin="round"/>
                            <path d="M98 54a38 38 0 0 1-69 22l21-22z" fill="#f8f8f2" stroke="#17243b" stroke-width="7" stroke-linejoin="round"/>
                            <path d="m47 43 26 14m-1-14L46 57" stroke="#17243b" stroke-width="6" stroke-linecap="round"/>
                        <?php endif; ?>
                    </svg>
                    <div>
                        <?php if ($resultadoCaptura === 'capturado'): ?>
                            <h2>
                                Pokémon capturado.
                                <?= htmlspecialchars($nomePokemon, ENT_QUOTES, 'UTF-8') ?>
                                está na reserva do seu time!
                            </h2>
                        <?php elseif ($resultadoCaptura === 'ja_possuia'): ?>
                            <h2>Você já tinha <?= htmlspecialchars($nomePokemon, ENT_QUOTES, 'UTF-8') ?>!</h2>
                            <p>Não foi criada uma cópia; ele continua disponível na sua equipe.</p>
                        <?php else: ?>
                            <h2>O Pokémon escapou e não foi capturado, tente na próxima!</h2>
                        <?php endif; ?>
                        <p>
                            Tentativa <?= (int) $captura['numero_tentativa'] ?>,
                            chance de <?= (int) $captura['chance'] ?>%.
                        </p>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($erroCaptura !== null): ?>
                <p class="post-battle-error" role="alert">
                    <?= htmlspecialchars($erroCaptura, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <nav class="post-battle-actions" aria-label="Ações da captura">
                <?php if (
                    $fasePosVitoria === 'captura'
                    && $pokemonTentativa !== null
                    && !$capturaProcessada
                ): ?>
                    <form method="post" action="/projetoFinalPok-mon/public/index.php?rota=captura">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfBatalha, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="acao" value="tentar_capturar">
                        <button class="post-battle-button capture-button" type="submit">Tentar capturar</button>
                    </form>
                <?php endif; ?>
                <form method="post" action="/projetoFinalPok-mon/public/index.php?rota=captura">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfBatalha, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="acao" value="prosseguir">
                    <button class="post-battle-button continue-button" type="submit">Prosseguir</button>
                </form>
            </nav>
        <?php else: ?>
            <!-- A etapa final confirma a insígnia e oferece revanche ou continuação. -->
            <h1>Insígnia conquistada!</h1>
            <section class="badge-award" aria-live="polite">
                <svg
                    class="gym-badge"
                    viewBox="0 0 120 110"
                    role="img"
                    aria-label="Imagem da <?= htmlspecialchars($nomeInsignia, ENT_QUOTES, 'UTF-8') ?>"
                >
                    <path d="M60 4 108 34 92 94 60 106 28 94 12 34z" fill="<?= $cores[0] ?>" stroke="#17243b" stroke-width="7" stroke-linejoin="round"/>
                    <path d="m60 19 32 20-11 42-21 9-21-9-11-42z" fill="<?= $cores[1] ?>" stroke="#17243b" stroke-width="5" stroke-linejoin="round"/>
                    <path d="m60 32 18 20-18 25-18-25z" fill="<?= $cores[0] ?>" stroke="#17243b" stroke-width="4" stroke-linejoin="round"/>
                </svg>
                <h2>
                    <?= $primeiraVitoria ? 'Você conquistou a' : 'Você mantém a' ?>
                    <?= htmlspecialchars($nomeInsignia, ENT_QUOTES, 'UTF-8') ?>!
                </h2>
                <?php if ($primeiraVitoria && $nivel < 8): ?>
                    <p>Você também desbloqueou o próximo nível da jornada.</p>
                <?php elseif ($nivel === 8): ?>
                    <p>Você venceu os oito ginásios e concluiu a jornada de Kanto!</p>
                <?php else: ?>
                    <p>Essa insígnia continua garantida mesmo se você repetir a batalha.</p>
                <?php endif; ?>
            </section>
            <p class="post-battle-reward">
                Você recebeu <?= $recompensa ?>
                <img
                    class="reward-coin"
                    src="/projetoFinalPok-mon/public/css/img/moeda.png"
                    alt="PokePila"
                >
            </p>

            <?php if ($erroCaptura !== null): ?>
                <p class="post-battle-error" role="alert">
                    <?= htmlspecialchars($erroCaptura, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <nav class="post-battle-actions" aria-label="Próximas ações">
                <?php if ($pokemonsVivos > 0): ?>
                    <form
                        method="post"
                        action="/projetoFinalPok-mon/public/index.php?rota=batalha&amp;nivel=<?= $nivel ?>"
                    >
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfBatalha, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="acao" value="reiniciar">
                        <button class="post-battle-button replay-button" type="submit">Repetir batalha</button>
                    </form>
                <?php endif; ?>
                <a class="post-battle-button continue-button" href="<?= htmlspecialchars($destinoContinuar, ENT_QUOTES, 'UTF-8') ?>">
                    Continuar jornada
                </a>
            </nav>
        <?php endif; ?>
    </main>
    <?php require __DIR__ . '/../layouts/music_player.php'; ?>
</body>
</html>
