<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pokémon | Pokebagual</title>
    <link rel="stylesheet" href="css/pokemon.css">
</head>
<body>
    <main class="container">
        <header class="page-header">
            <p class="eyebrow">Pokebagual</p>
            <h1>Pokémon</h1>
            <p class="subtitle">Conheça os Pokémon e seus atributos.</p>
        </header>

        <?php if ($erro !== null): ?>
            <p class="message message-error" role="alert">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php elseif (empty($pokemons)): ?>
            <p class="message">Nenhum Pokémon encontrado.</p>
        <?php else: ?>
            <section class="pokemon-grid" aria-label="Lista de Pokémon">
                <?php foreach ($pokemons as $pokemon): ?>
                    <article class="pokemon-card">
                        <?php if (!empty($pokemon['imagem'])): ?>
                            <img
                                class="pokemon-image"
                                src="<?= htmlspecialchars($pokemon['imagem'], ENT_QUOTES, 'UTF-8') ?>"
                                alt="Imagem de <?= htmlspecialchars($pokemon['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="pokemon-image image-placeholder" aria-hidden="true">?</div>
                        <?php endif; ?>

                        <div class="pokemon-info">
                            <h2><?= htmlspecialchars(ucfirst($pokemon['nome']), ENT_QUOTES, 'UTF-8') ?></h2>

                            <div class="pokemon-types" aria-label="Tipos">
                                <?php foreach ($pokemon['tipos'] as $tipo): ?>
                                    <span class="type-badge">
                                        <?= htmlspecialchars(ucfirst($tipo), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>

                            <dl class="pokemon-stats">
                                <div><dt>HP</dt><dd><?= (int) $pokemon['hp'] ?></dd></div>
                                <div><dt>Ataque</dt><dd><?= (int) $pokemon['ataque'] ?></dd></div>
                                <div><dt>Defesa</dt><dd><?= (int) $pokemon['defesa'] ?></dd></div>
                                <div><dt>Velocidade</dt><dd><?= (int) $pokemon['velocidade'] ?></dd></div>
                            </dl>

                            <div class="evolution-info">
                                <h3>Linha evolutiva</h3>
                                <?php foreach ($pokemon['caminhos_evolutivos'] as $caminho): ?>
                                    <p>
                                        <?= htmlspecialchars(
                                            implode(' → ', array_map('ucfirst', $caminho)),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
