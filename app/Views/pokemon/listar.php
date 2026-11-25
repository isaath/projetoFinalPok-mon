<?php
// A View espera receber duas variáveis vindas do Controller:
// $erro para mensagens de erro e $pokemons para a lista dos monstros.
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- Metadados da página e folha de estilos da listagem. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pokémon | PokeBagual</title>
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pokemon.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
    <script src="/projetoFinalPok-mon/public/js/pokemon_images.js" defer></script>
</head>
<body>
    <!-- Conteúdo principal da tela de Pokémon. -->
    <main class="container">
        <!-- Cabeçalho com nome do projeto, título e resumo da página. -->
        <header class="page-header">
            <p class="eyebrow">PokeBagual</p>
            <h1>Pokémon</h1>
            <p class="subtitle">Conheça os Pokémon e seus atributos.</p>
        </header>

        <!-- Escolhe entre mostrar erro, lista vazia ou os cards recebidos. -->
        <?php if ($erro !== null): ?>
            <p class="message message-error" role="alert">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php elseif (empty($pokemons)): ?>
            <p class="message">Nenhum Pokémon encontrado.</p>
        <?php else: ?>
            <section class="pokemon-grid" aria-label="Lista de Pokémon">
                <!-- Cria um card para cada Pokémon recebido pelo Controller. -->
                <?php foreach ($pokemons as $pokemon): ?>
                    <article class="pokemon-card">
                        <?php $nomePokemon = $pokemon->getNome(); ?>
                        <?php $tiposPokemon = $pokemon->getTipos(); ?>
                        <img
                            class="pokemon-image imagem-pokemon"
                            data-nome="<?= htmlspecialchars(strtolower($nomePokemon), ENT_QUOTES, 'UTF-8') ?>"
                            alt="Imagem de <?= htmlspecialchars($nomePokemon, ENT_QUOTES, 'UTF-8') ?>"
                            loading="lazy"
                        >
                        <div class="pokemon-image image-placeholder" aria-hidden="true" hidden>?</div>

                        <!-- Exibe os dados textuais e estatísticas do Pokémon atual. -->
                        <div class="pokemon-info">
                            <h2><?= htmlspecialchars(ucfirst($nomePokemon), ENT_QUOTES, 'UTF-8') ?></h2>

                            <!-- Mostra todos os tipos associados a este Pokémon. -->
                            <div class="pokemon-types" aria-label="Tipos">
                                <span class="type-badge">
                                    <?= htmlspecialchars(ucfirst($pokemon->getTipo()), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <?php foreach (array_slice($tiposPokemon, 1) as $tipo): ?>
                                    <span class="type-badge">
                                        <?= htmlspecialchars(ucfirst($tipo), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>

                            <!-- Lista atributos básicos cadastrados no banco de dados. -->
                            <dl class="pokemon-stats">
                                <div><dt>HP</dt><dd><?= $pokemon->getHpMaximo() ?></dd></div>
                                <div><dt>Ataque</dt><dd><?= $pokemon->getForcaAtaque() ?></dd></div>
                                <div><dt>Defesa</dt><dd><?= $pokemon->getDefesa() ?></dd></div>
                                <div><dt>Velocidade</dt><dd><?= $pokemon->getVelocidade() ?></dd></div>
                            </dl>

                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
