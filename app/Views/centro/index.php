<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pokecenter - PokeBagual</title>
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/centro.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
    <script src="/projetoFinalPok-mon/public/js/pokemon_images.js" defer></script>
</head>
<body>
    <main class="painel-pixel">
        <a href="/projetoFinalPok-mon/public/index.php?rota=jogar" class="btn_pixel">
            <img src="/projetoFinalPok-mon/public/css/img/btn_voltar.png" alt="Voltar ao painel"
                 style="width: 150px !important; height: auto;">
        </a>

        <header class="cabecalho">
            <div>
                <p class="etiqueta">CUIDADO COM SEU TIME</p>
                <h1>Pokecenter</h1>
                <p>Recupere o HP de todos os Pokémon associados aos seus times.</p>
            </div>
            <p class="saldo">Saldo: <strong><?= number_format($saldo, 0, ',', '.') ?></strong> Pokédollars</p>
        </header>

        <?php if (!empty($mensagem)): ?>
            <p class="mensagem" role="status"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if (!empty($erro)): ?>
            <p class="mensagem erro" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($pokemons === []): ?>
            <section class="vazio">
                <h2>Seu time ainda está vazio</h2>
                <p>Quando você tiver Pokémon associados a um time, eles aparecerão aqui.</p>
            </section>
        <?php else: ?>
            <section class="lista-pokemons" aria-label="Pokémon dos seus times">
                <?php foreach ($pokemons as $pokemon): ?>
                    <?php
                    $hpAtual = (int) $pokemon['hp_atual'];
                    $hpMaximo = (int) $pokemon['hp_maximo'];
                    $porcentagem = $hpMaximo > 0
                        ? min(100, (int) round(($hpAtual / $hpMaximo) * 100))
                        : 0;
                    ?>
                    <article class="cartao-pokemon">
                        <img
    class="imagem-pokemon"
    data-nome="<?= htmlspecialchars(strtolower($pokemon['nome']), ENT_QUOTES, 'UTF-8') ?>"
    alt="Imagem de <?= htmlspecialchars($pokemon['nome'], ENT_QUOTES, 'UTF-8') ?>"
    loading="lazy"
>
                        <div class="pokemon-dados">
                            <h2><?= htmlspecialchars($pokemon['nome'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <p>
                                <?= htmlspecialchars($pokemon['nome_time'], ENT_QUOTES, 'UTF-8') ?>
                                · Posição <?= (int) $pokemon['posicao'] ?>
                            </p>
                        </div>
                        <div class="barra-hp" role="progressbar"
                             aria-label="HP de <?= htmlspecialchars($pokemon['nome'], ENT_QUOTES, 'UTF-8') ?>"
                             aria-valuenow="<?= $hpAtual ?>"
                             aria-valuemin="0"
                             aria-valuemax="<?= $hpMaximo ?>">
                            <span style="width: <?= $porcentagem ?>%"></span>
                        </div>
                        <p class="hp"><?= $hpAtual ?> / <?= $hpMaximo ?> HP</p>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="visita">
                <div>
                    <h2>Tratamento completo</h2>
                    <p>Todos os Pokémon dos seus times, inclusive os desmaiados, voltam ao HP máximo.</p>
                    <p class="preco">Custo por visita: <?= $custoVisita ?> Pokédollars</p>
                </div>
                <form method="post" action="/projetoFinalPok-mon/public/index.php?rota=centro">
                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION['csrf_centro'], ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn_pixel">
                        <img src="/projetoFinalPok-mon/public/css/img/btn_curar.png" alt="Curar todos"
                             style="width: 170px !important; height: auto;">
                    </button>
                </form>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
