<?php
if (!defined('POKEBAGUAL_BATALHA_VIEW')) {
    http_response_code(404);
    exit;
}

$treinador = $dadosPrevia['treinador'] ?? null;
$pokemonJogador = $dadosPrevia['pokemon_jogador'] ?? null;
$pokemonAdversario = $dadosPrevia['pokemon_adversario'] ?? null;
$equipeTreinador = $dadosPrevia['equipe_treinador'] ?? [];
$nomeTreinador = $treinador['nome'] ?? 'Treinador';
$insignia = $treinador['nome_insignia'] ?? 'Insígnia pendente';
$especialidade = $treinador['especialidade'] ?? 'Tipo pendente';
$nivel = (int) ($treinador['nivel_jornada'] ?? $nivelPrevia);
$iniciaisTreinador = strtoupper(substr($nomeTreinador, 0, 2));
$arquivoImagemTreinador = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $nomeTreinador), '-'));
$imagemTreinadorDisponivel = is_file(
    __DIR__ . '/../../../public/css/img/treinadores/' . $arquivoImagemTreinador . '.png'
);
$hpJogador = (int) ($pokemonJogador['hp_atual'] ?? 0);
$hpMaximoJogador = (int) ($pokemonJogador['hp_maximo'] ?? 0);
$hpAdversario = (int) ($pokemonAdversario['hp_atual'] ?? 0);
$hpMaximoAdversario = (int) ($pokemonAdversario['hp_maximo'] ?? 0);
$percentualHpJogador = $hpMaximoJogador > 0
    ? max(0, min(100, (int) round($hpJogador / $hpMaximoJogador * 100)))
    : 0;
$percentualHpAdversario = $hpMaximoAdversario > 0
    ? max(0, min(100, (int) round($hpAdversario / $hpMaximoAdversario * 100)))
    : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prévia de batalha - Pokebagual</title>
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/batalha.css">
</head>
<body class="battle-page">
    <main class="battle-shell">
        <header class="battle-header">
            <a class="battle-back" href="/projetoFinalPok-mon/public/index.php?rota=jogar">
                &larr; Voltar ao painel
            </a>
            <p class="eyebrow">JORNADA KANTO · NÍVEL <?= $nivel ?> DE 8</p>
            <h1>Ginásio <?= htmlspecialchars($especialidade, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="battle-lead">
                Desafio contra <?= htmlspecialchars($nomeTreinador, ENT_QUOTES, 'UTF-8') ?>
                · <?= htmlspecialchars($insignia, ENT_QUOTES, 'UTF-8') ?>
            </p>
            <span class="preview-label">PRÉVIA DO PRIMEIRO CONFRONTO — AÇÕES EM DESENVOLVIMENTO</span>
        </header>

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

        <div class="battle-layout">
            <section class="battle-main" aria-label="Prévia da arena de batalha">
                <div class="battle-score">
                    <span>SEU TIME <strong>3 Pokémon</strong></span>
                    <span class="score-divider" aria-hidden="true">VS</span>
                    <span>GINÁSIO <strong><?= count($equipeTreinador) ?> Pokémon</strong></span>
                </div>

                <div class="arena">
                    <article class="combatant combatant-player">
                        <div class="combatant-portrait">
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
                            <p class="combatant-label">SEU POKÉMON ATIVO · POSIÇÃO 1</p>
                            <h2>
                                <?= $pokemonJogador !== null
                                    ? htmlspecialchars($pokemonJogador['nome'], ENT_QUOTES, 'UTF-8')
                                    : 'Time ativo não encontrado' ?>
                            </h2>
                            <?php if ($pokemonJogador !== null): ?>
                                <p><?= htmlspecialchars($pokemonJogador['tipo'] ?? 'Tipo não cadastrado', ENT_QUOTES, 'UTF-8') ?></p>
                                <div class="hp-track" aria-label="HP do seu Pokémon">
                                    <span style="width: <?= $percentualHpJogador ?>%;"></span>
                                </div>
                                <small>HP <?= $hpJogador ?> / <?= $hpMaximoJogador ?></small>
                            <?php else: ?>
                                <p>Monte um time para visualizar o primeiro Pokémon.</p>
                            <?php endif; ?>
                        </div>
                    </article>

                    <div class="arena-vs" aria-hidden="true">VS</div>

                    <article class="combatant combatant-opponent">
                        <div class="combatant-portrait">
                            <?php if (is_string($imagemAdversario)): ?>
                                <img
                                    class="combatant-image"
                                    src="<?= htmlspecialchars($imagemAdversario, ENT_QUOTES, 'UTF-8') ?>"
                                    alt="Sprite de <?= htmlspecialchars($pokemonAdversario['nome_pokemon'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            <?php else: ?>
                                <div class="combatant-symbol" aria-hidden="true">?</div>
                            <?php endif; ?>
                        </div>
                        <div class="combatant-copy">
                            <p class="combatant-label">POKÉMON DO LÍDER · POSIÇÃO 1</p>
                            <h2>
                                <?= $pokemonAdversario !== null
                                    ? htmlspecialchars($pokemonAdversario['nome_pokemon'], ENT_QUOTES, 'UTF-8')
                                    : 'Pokémon não encontrado' ?>
                            </h2>
                            <?php if ($pokemonAdversario !== null): ?>
                                <p><?= htmlspecialchars($pokemonAdversario['tipos'] ?? 'Tipo não cadastrado', ENT_QUOTES, 'UTF-8') ?></p>
                                <div class="hp-track" aria-label="HP do Pokémon adversário">
                                    <span style="width: <?= $percentualHpAdversario ?>%;"></span>
                                </div>
                                <small>HP <?= $hpAdversario ?> / <?= $hpMaximoAdversario ?></small>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>

                <section class="battle-log" aria-labelledby="battle-log-title">
                    <h2 id="battle-log-title">Registro da batalha</h2>
                    <p>As mensagens dos turnos serão exibidas aqui.</p>
                    <p class="log-note">Esta prévia não executa ataques nem altera HP.</p>
                </section>

                <section class="battle-actions" aria-label="Ações de batalha futuras">
                    <button type="button" disabled>Ataque</button>
                    <button type="button" disabled>Trocar Pokémon</button>
                    <button type="button" disabled>Usar item</button>
                    <button type="button" disabled>Fugir</button>
                </section>
            </section>

            <aside class="trainer-card" aria-labelledby="trainer-title">
                <p class="eyebrow">LÍDER DO GINÁSIO</p>
                <div class="trainer-avatar">
                    <?php if ($imagemTreinadorDisponivel): ?>
                        <img
                            class="trainer-image"
                            src="/projetoFinalPok-mon/public/css/img/treinadores/<?= rawurlencode($arquivoImagemTreinador) ?>.png"
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

                <h3>Ordem de batalha</h3>
                <ol class="trainer-team">
                    <?php foreach ($equipeTreinador as $pokemon): ?>
                        <li>
                            <span><?= (int) $pokemon['ordem_batalha'] ?></span>
                            <?= htmlspecialchars($pokemon['nome_pokemon'], ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    <?php endforeach; ?>
                </ol>

                <div class="capture-preview">
                    <h3>Capturas por vitória</h3>
                    <?php foreach ($equipeTreinador as $pokemon): ?>
                        <p>
                            <?= (int) $pokemon['ordem_captura'] ?>ª vitória:
                            <?= htmlspecialchars($pokemon['nome_pokemon'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    <?php endforeach; ?>
                    <small>Esta tela é uma prévia; a captura será conectada depois.</small>
                </div>
            </aside>
        </div>

        <p class="trainer-image-note">
            Para adicionar o retrato, coloque um PNG em
            <code>public/css/img/treinadores/<?= htmlspecialchars($arquivoImagemTreinador, ENT_QUOTES, 'UTF-8') ?>.png</code>.
        </p>
    </main>
</body>
</html>
