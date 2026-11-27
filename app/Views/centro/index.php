<!-- Na apresentação: CentroController entrega saldo, custo, mensagens e Pokémon
     consultados pelo Model CentroPokemon; o POST solicita uma cura ao servidor. -->
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pokecenter - PokeBagual</title>
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/centro.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/music_player.css">
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
            <p class="saldo">
                <!-- Saldo enviado pelo Controller, sem consulta direta pela View. -->
                Saldo: <strong><?= number_format($saldo, 0, ',', '.') ?></strong> PokePila
                <img class="icone-moeda-saldo" src="/projetoFinalPok-mon/public/css/img/moeda.png" alt="" aria-hidden="true">
            </p>
        </header>

        <?php if (!empty($mensagem)): ?>
            <!-- Diferencia saldo insuficiente dos demais resultados da visita. -->
            <?php if (is_array($mensagem) && ($mensagem['tipo'] ?? null) === 'saldo_insuficiente'): ?>
                <p class="mensagem" role="status">
                    A visita custa <?= (int) ($mensagem['custo'] ?? 0) ?> PokePila.
                    Seu saldo atual é
                    <strong><?= number_format((int) ($mensagem['saldo'] ?? 0), 0, ',', '.') ?></strong>
                    PokePila
                    <img class="icone-moeda-saldo" src="/projetoFinalPok-mon/public/css/img/moeda.png" alt="" aria-hidden="true">
                </p>
            <?php elseif (is_array($mensagem) && is_string($mensagem['texto'] ?? null)): ?>
                <p class="mensagem" role="status"><?= htmlspecialchars($mensagem['texto'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php elseif (is_string($mensagem)): ?>
                <p class="mensagem" role="status"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($erro)): ?>
            <p class="mensagem erro" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($pokemons === []): ?>
            <!-- Orienta o usuário quando ainda não existe equipe associada à conta. -->
            <section class="vazio">
                <h2>Seu time ainda está vazio</h2>
                <p>Quando você tiver Pokémon associados a um time, eles aparecerão aqui.</p>
            </section>
        <?php else: ?>
            <!-- Exibe cada Pokémon e a porcentagem de HP calculada para a barra. -->
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
                    <p class="preco">Custo por visita: <?= $custoVisita ?> PokePila</p>
                </div>
                <form method="post" action="/projetoFinalPok-mon/public/index.php?rota=centro">
                    <!-- O token confirma que a solicitação de cura partiu desta sessão. -->
                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($_SESSION['csrf_centro'], ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn_pixel">
                        <img src="/projetoFinalPok-mon/public/css/img/btn_curar.png" alt="Curar todos"
                             style="width: 260px !important; height: auto;">
                    </button>
                </form>
            </section>
        <?php endif; ?>
    </main>
    <!-- Reutiliza o player comum às telas do jogo. -->
    <?php require __DIR__ . '/../layouts/music_player.php'; ?>
</body>
</html>
