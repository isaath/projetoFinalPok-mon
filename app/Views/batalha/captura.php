<?php
if (!defined('POKEBAGUAL_CAPTURA_VIEW')) {
    http_response_code(404);
    exit;
}

$treinador = $batalha['treinador'] ?? [];
$nomeTreinador = $treinador['nome'] ?? 'Treinador';
$captura = $captura ?? null;
$resultadoCaptura = is_array($captura) ? ($captura['resultado'] ?? null) : null;
$nomePokemon = is_array($captura) && is_string($captura['nome'] ?? null)
    ? $captura['nome']
    : null;
$nivel = (int) ($batalha['nivel'] ?? 1);
$recompensa = (int) ($batalha['recompensa'] ?? 0);
$proximaChance = is_array($captura) ? (int) ($captura['proxima_chance'] ?? 30) : null;
$pokemonsVivos = count(array_filter(
    $batalha['jogador']['equipe'] ?? [],
    static fn (array $pokemon): bool => (int) ($pokemon['hp'] ?? 0) > 0
));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Captura - PokeBagual</title>
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/batalha.css">
</head>
<body class="battle-page">
    <main class="battle-shell">
        <header class="battle-header">
            <p class="eyebrow">JORNADA KANTO · NÍVEL <?= $nivel ?> DE 8</p>
            <h1>Tentativa de captura</h1>
            <p class="battle-lead">
                Após a vitória contra <?= htmlspecialchars($nomeTreinador, ENT_QUOTES, 'UTF-8') ?>.
                Recompensa da batalha: <?= $recompensa ?> Pokédollars.
            </p>
            <?php if ($resultadoCaptura !== null): ?>
                <span class="battle-result <?= $resultadoCaptura === 'falhou' ? 'battle-result-loss' : 'battle-result-win' ?>">
                    <?= $resultadoCaptura === 'falhou' ? 'ESCAPOU' : 'CAPTURA BEM-SUCEDIDA' ?>
                </span>
            <?php endif; ?>
        </header>

        <section class="battle-main">
            <div class="battle-finish" aria-live="polite">
                <?php if ($resultadoCaptura === 'capturado' && $nomePokemon !== null): ?>
                    <h2>Captura bem-sucedida!</h2>
                    <p>
                        <?= htmlspecialchars($nomePokemon, ENT_QUOTES, 'UTF-8') ?>
                        foi adicionado à sua reserva com HP completo.
                    </p>
                    <p>O Pokémon do líder continua na equipe adversária.</p>
                    <p>
                        Tentativa <?= (int) $captura['numero_tentativa'] ?>,
                        com chance de <?= (int) $captura['chance'] ?>%.
                    </p>
                    <a class="battle-submit" href="/projetoFinalPok-mon/public/index.php?rota=meu_time">
                        Gerenciar meu time
                    </a>
                <?php elseif ($resultadoCaptura === 'ja_possuia' && $nomePokemon !== null): ?>
                    <h2>Captura bem-sucedida!</h2>
                    <p>
                        Você já possuía <?= htmlspecialchars($nomePokemon, ENT_QUOTES, 'UTF-8') ?>.
                        Não foi criada uma cópia; a sequência de capturas pode avançar.
                    </p>
                    <p>O Pokémon do líder continua na equipe adversária.</p>
                    <p>
                        Tentativa <?= (int) $captura['numero_tentativa'] ?>,
                        com chance de <?= (int) $captura['chance'] ?>%.
                    </p>
                <?php elseif ($resultadoCaptura === 'falhou' && $nomePokemon !== null): ?>
                    <h2><?= htmlspecialchars($nomePokemon, ENT_QUOTES, 'UTF-8') ?> escapou!</h2>
                    <p>
                        Tentativa <?= (int) $captura['numero_tentativa'] ?>:
                        a chance era de <?= (int) $captura['chance'] ?>%.
                    </p>
                    <p>
                        Vença este líder novamente para tentar outra vez.
                        A próxima tentativa terá <?= $proximaChance ?>% de chance.
                        Você precisa capturar este Pokémon antes de tentar o próximo da sequência.
                    </p>
                <?php else: ?>
                    <h2>Todos os Pokémon já foram capturados</h2>
                    <p>Você já possui todos os Pokémon liberados por este líder.</p>
                <?php endif; ?>
            </div>

            <nav class="battle-finish" aria-label="Próximas ações">
                <?php if ($pokemonsVivos > 0): ?>
                    <form
                        method="post"
                        action="/projetoFinalPok-mon/public/index.php?rota=batalha&amp;nivel=<?= $nivel ?>"
                    >
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($csrfBatalha, ENT_QUOTES, 'UTF-8') ?>"
                        >
                        <input type="hidden" name="acao" value="reiniciar">
                        <button class="battle-submit" type="submit">Repetir batalha</button>
                    </form>
                <?php endif; ?>
                <a class="battle-submit" href="/projetoFinalPok-mon/public/index.php?rota=mapa">
                    <?= $captura === null ? 'Continuar' : 'Voltar ao mapa' ?>
                </a>
            </nav>
        </section>
    </main>
</body>
</html>
