<?php
if (!defined('POKEBAGUAL_BATALHA_VIEW')) {
    http_response_code(404);
    exit;
}

$treinador = $dadosBatalha['treinador'] ?? [];
$equipeJogador = $dadosBatalha['jogador']['equipe'] ?? [];
$equipeAdversario = $dadosBatalha['adversario']['equipe'] ?? [];
$pokemonJogador = $pokemonJogador ?? null;
$pokemonAdversario = $pokemonAdversario ?? null;
$nivel = (int) ($dadosBatalha['nivel'] ?? $nivel ?? 1);
$nomeTreinador = $treinador['nome'] ?? 'Treinador';
$insignia = $treinador['nome_insignia'] ?? 'Insígnia';
$especialidade = $treinador['especialidade'] ?? 'Ginásio';
$finalizada = !empty($dadosBatalha['finalizada']);
$vitoria = ($dadosBatalha['resultado'] ?? null) === 'vitoria';
$mensagens = $dadosBatalha['mensagens'] ?? [];
$nomeStatus = [
    'queimadura' => 'Queimado',
    'veneno' => 'Envenenado',
    'toxico' => 'Envenenamento grave',
    'paralisia' => 'Paralisado',
    'sono' => 'Dormindo',
    'congelamento' => 'Congelado',
    'confusao' => 'Confuso',
];
$arquivoImagemTreinador = strtolower(
    trim((string) preg_replace('/[^a-z0-9]+/i', '-', $nomeTreinador), '-')
);
$imagemTreinadorDisponivel = is_file(
    __DIR__ . '/../../../public/css/img/treinadores/' . $arquivoImagemTreinador . '.png'
);

$porcentagemHp = static function (?array $pokemon): int {
    if ($pokemon === null || (int) $pokemon['hp_maximo'] <= 0) {
        return 0;
    }

    return max(0, min(100, (int) round(
        (int) $pokemon['hp'] / (int) $pokemon['hp_maximo'] * 100
    )));
};

$rotuloStatus = static function (?string $status) use ($nomeStatus): string {
    return $status === null ? 'Sem condição' : ($nomeStatus[$status] ?? $status);
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batalha - PokeBagual</title>
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/batalha.css">
</head>
<body class="battle-page">
    <main class="battle-shell">
        <header class="battle-header">
            <a class="battle-back" href="/projetoFinalPok-mon/public/index.php?rota=mapa">
                &larr; Voltar ao mapa
            </a>
            <p class="eyebrow">JORNADA KANTO · NÍVEL <?= $nivel ?> DE 8</p>
            <h1>Ginásio <?= htmlspecialchars($especialidade, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="battle-lead">
                Batalha contra <?= htmlspecialchars($nomeTreinador, ENT_QUOTES, 'UTF-8') ?>
                · <?= htmlspecialchars($insignia, ENT_QUOTES, 'UTF-8') ?>
            </p>
            <?php if ($finalizada): ?>
                <span class="battle-result <?= $vitoria ? 'battle-result-win' : 'battle-result-loss' ?>">
                    <?= $vitoria ? 'VITÓRIA' : 'DERROTA' ?>
                </span>
            <?php else: ?>
                <span class="battle-live-label">BATALHA EM ANDAMENTO</span>
            <?php endif; ?>
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

        <?php if ($dadosBatalha !== null): ?>
            <div class="battle-layout">
                <section class="battle-main" aria-label="Arena de batalha">
                    <div class="battle-score">
                        <span>
                            SEU TIME
                            <strong><?= count($equipeJogador) ?> Pokémon</strong>
                        </span>
                        <span class="score-divider" aria-hidden="true">VS</span>
                        <span>
                            GINÁSIO
                            <strong><?= count($equipeAdversario) ?> Pokémon</strong>
                        </span>
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
                                <p class="combatant-label">SEU POKÉMON ATIVO</p>
                                <h2>
                                    <?= $pokemonJogador !== null
                                        ? htmlspecialchars($pokemonJogador['nome'], ENT_QUOTES, 'UTF-8')
                                        : 'Sem Pokémon ativo' ?>
                                </h2>
                                <?php if ($pokemonJogador !== null): ?>
                                    <p><?= htmlspecialchars(implode(' / ', $pokemonJogador['tipos']), ENT_QUOTES, 'UTF-8') ?></p>
                                    <div class="hp-track" aria-label="HP do seu Pokémon">
                                        <span style="width: <?= $porcentagemHp($pokemonJogador) ?>%;"></span>
                                    </div>
                                    <small>
                                        HP <?= (int) $pokemonJogador['hp'] ?> /
                                        <?= (int) $pokemonJogador['hp_maximo'] ?>
                                        · <?= htmlspecialchars($rotuloStatus($pokemonJogador['status']), ENT_QUOTES, 'UTF-8') ?>
                                    </small>
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
                                        alt="Sprite de <?= htmlspecialchars($pokemonAdversario['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                <?php else: ?>
                                    <div class="combatant-symbol" aria-hidden="true">?</div>
                                <?php endif; ?>
                            </div>
                            <div class="combatant-copy">
                                <p class="combatant-label">POKÉMON DO LÍDER</p>
                                <h2>
                                    <?= $pokemonAdversario !== null
                                        ? htmlspecialchars($pokemonAdversario['nome'], ENT_QUOTES, 'UTF-8')
                                        : 'Equipe adversária derrotada' ?>
                                </h2>
                                <?php if ($pokemonAdversario !== null): ?>
                                    <p><?= htmlspecialchars(implode(' / ', $pokemonAdversario['tipos']), ENT_QUOTES, 'UTF-8') ?></p>
                                    <div class="hp-track" aria-label="HP do Pokémon adversário">
                                        <span style="width: <?= $porcentagemHp($pokemonAdversario) ?>%;"></span>
                                    </div>
                                    <small>
                                        HP <?= (int) $pokemonAdversario['hp'] ?> /
                                        <?= (int) $pokemonAdversario['hp_maximo'] ?>
                                        · <?= htmlspecialchars($rotuloStatus($pokemonAdversario['status']), ENT_QUOTES, 'UTF-8') ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </article>
                    </div>

                    <section class="battle-log" aria-labelledby="battle-log-title" aria-live="polite">
                        <h2 id="battle-log-title">Registro da batalha</h2>
                        <?php if ($mensagens !== []): ?>
                            <ol>
                                <?php foreach ($mensagens as $mensagem): ?>
                                    <li><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></li>
                                <?php endforeach; ?>
                            </ol>
                        <?php else: ?>
                            <p>A batalha começará quando você escolher um movimento.</p>
                        <?php endif; ?>
                    </section>

                    <?php if (!$finalizada && $pokemonJogador !== null): ?>
                        <form class="battle-actions" method="post" action="">
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars($csrfBatalha, ENT_QUOTES, 'UTF-8') ?>"
                            >
                            <fieldset class="move-list">
                                <legend>Escolha o movimento</legend>
                                <?php foreach ($pokemonJogador['movimentos'] as $indice => $movimento): ?>
                                    <label class="move-option">
                                        <input
                                            type="radio"
                                            name="id_movimento"
                                            value="<?= (int) $movimento['id_movimento'] ?>"
                                            <?= $indice === 0 ? 'checked' : '' ?>
                                            required
                                        >
                                        <span class="move-name">
                                            <?= htmlspecialchars($movimento['nome'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <span class="move-details">
                                            <?= htmlspecialchars($movimento['tipo'], ENT_QUOTES, 'UTF-8') ?>
                                            ·
                                            <?= (int) $movimento['potencia'] > 0
                                                ? 'Poder ' . (int) $movimento['potencia']
                                                : 'Tático' ?>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                            <button class="battle-submit" type="submit">Usar movimento</button>
                        </form>
                    <?php elseif ($finalizada): ?>
                        <section class="battle-finish" aria-live="polite">
                            <?php if ($vitoria): ?>
                                <h2>Você venceu!</h2>
                                <p>Recompensa: <strong><?= (int) $dadosBatalha['recompensa'] ?> Pokédollars</strong></p>
                                <p>
                                    Pokémon liberados para uma futura captura:
                                    <strong><?= (int) $dadosBatalha['capturas_liberadas'] ?></strong>
                                </p>
                                <p class="log-note">
                                    A captura ainda será implementada em uma etapa própria; por enquanto,
                                    o progresso de desbloqueio é salvo.
                                </p>
                            <?php else: ?>
                                <h2>Você perdeu a batalha</h2>
                                <p>Seu HP foi salvo. Recupere a equipe no Pokecenter antes de tentar novamente.</p>
                                <a class="battle-submit battle-map-link" href="/projetoFinalPok-mon/public/index.php?rota=centro">
                                    Ir ao Pokecenter
                                </a>
                            <?php endif; ?>
                            <?php if (
                                count(array_filter(
                                    $equipeJogador,
                                    static fn (array $pokemon): bool => (int) $pokemon['hp'] > 0
                                )) > 0
                            ): ?>
                                <form method="post" action="">
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars($csrfBatalha, ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                    <input type="hidden" name="acao" value="reiniciar">
                                    <button class="battle-submit" type="submit">Repetir batalha</button>
                                </form>
                            <?php endif; ?>
                            <a class="battle-submit battle-map-link" href="/projetoFinalPok-mon/public/index.php?rota=mapa">
                                Voltar ao mapa
                            </a>
                        </section>
                    <?php endif; ?>

                    <?php if (is_array($dadosBatalha)): ?>
                        <p class="log-note">
                            Turnos: <?= (int) $dadosBatalha['turnos'] ?>.
                            Dano usa ataque físico ou especial, defesa correspondente, STAB e efetividade de tipo.
                        </p>
                    <?php endif; ?>
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
                                <?= htmlspecialchars(strtoupper(substr($nomeTreinador, 0, 2)), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <h2 id="trainer-title"><?= htmlspecialchars($nomeTreinador, ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="trainer-specialty">
                        Especialidade: <?= htmlspecialchars($especialidade, ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <h3>Equipe adversária</h3>
                    <ol class="trainer-team">
                        <?php foreach ($equipeAdversario as $indice => $pokemon): ?>
                            <li class="<?= (int) $dadosBatalha['adversario']['ativo'] === $indice ? 'team-active' : '' ?>">
                                <span><?= $indice + 1 ?></span>
                                <span>
                                    <?= htmlspecialchars($pokemon['nome'], ENT_QUOTES, 'UTF-8') ?>
                                    <small>HP <?= (int) $pokemon['hp'] ?>/<?= (int) $pokemon['hp_maximo'] ?></small>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ol>

                    <p class="capture-preview">
                        A cada vitória contra este líder, mais um Pokémon fica disponível para captura.
                        A captura ainda não pode ser realizada na batalha.
                    </p>
                </aside>
            </div>
        <?php endif; ?>

        <?php if ($dadosBatalha !== null && $pokemonAdversario !== null): ?>
            <p class="trainer-image-note">
                Os retratos de treinadores não são fornecidos pela PokeAPI. Para adicionar um,
                coloque um PNG em
                <code>public/css/img/treinadores/<?= htmlspecialchars($arquivoImagemTreinador, ENT_QUOTES, 'UTF-8') ?>.png</code>.
            </p>
        <?php endif; ?>
    </main>
</body>
</html>
