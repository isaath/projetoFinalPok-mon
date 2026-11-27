<?php
// Na apresentação: MapaController entrega progresso e eventos ao mapa. Links
// levam à rota "batalha"; os formulários POST de exploração voltam à rota "mapa".
// Associa o número de cada nível à classe CSS que posiciona seu botão no mapa.
$classesDosNiveis = [
    1 => 'brock',
    2 => 'misty',
    3 => 'lt_surge',
    4 => 'erika',
    5 => 'koga',
    6 => 'sabrina',
    7 => 'blaine',
    8 => 'giovanni',
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa do Jogo - Pokebagual</title>
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/mapa_jogo.css?v=map-events-3">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/music_player.css">
</head>
<body>
    
    <main class="mapa-container">
        <!-- Os pontos de batalha são posicionados sobre esta imagem pelo CSS. -->
        <img
            src="/projetoFinalPok-mon/public/css/img/img_mapa/mapa_do_jogo.png"
            alt="Mapa do Jogo"
            class="mapa"
        >

        <?php if ($erroMapa !== null): ?>
            <!-- Informa quando não foi possível consultar o progresso no banco. -->
            <p class="erro-mapa" role="alert">
                <?= htmlspecialchars($erroMapa, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php else: ?>
            <!-- Percorre os oito níveis e escolhe link ou ponto bloqueado. -->
            <?php foreach ($progressoNiveis as $nivel => $dadosNivel): ?>
                <?php
                // Prepara nome e classes para montar o ponto do treinador com segurança.
                $nomeTreinador = htmlspecialchars(
                    $dadosNivel['nome'],
                    ENT_QUOTES,
                    'UTF-8'
                );
                $classeTreinador = $classesDosNiveis[$nivel];
                $nivelVencido = $dadosNivel['vitorias'] > 0;
                ?>

                <?php if ($dadosNivel['liberado']): ?>
                    <!-- Nível liberado: o link leva à prévia correspondente. -->
                    <a
                        class="ponto-mapa <?= $classeTreinador ?> <?= $nivelVencido ? 'vencido' : 'liberado' ?>"
                        href="/projetoFinalPok-mon/public/index.php?rota=batalha&amp;nivel=<?= (int) $nivel ?>"
                        aria-label="Desafiar <?= $nomeTreinador ?>"
                        title="<?= $nivelVencido ? 'Nível vencido; pode jogar novamente' : 'Nível liberado' ?>"
                    ></a>
                <?php else: ?>
                    <!-- Nível bloqueado: é um span sem href, portanto não pode ser clicado. -->
                    <span
                        class="ponto-mapa <?= $classeTreinador ?> bloqueado"
                        aria-label="<?= $nomeTreinador ?>: nível bloqueado"
                        aria-disabled="true"
                        title="Vença o nível anterior para liberar <?= $nomeTreinador ?>"
                    ></span>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- Os cinco pontos entre ginásios são exploráveis uma única vez por jogador. -->
            <?php for ($idPonto = 1; $idPonto <= 5; $idPonto++): ?>
                <?php
                $pontoUsado = isset($eventosMapa[$idPonto]);
                $nivelNecessario = $niveisRequeridosPorPonto[$idPonto];
                $nomeLiderNecessario = $progressoNiveis[$nivelNecessario]['nome'];
                $pontoBloqueado = !$pontoUsado
                    && $progressoNiveis[$nivelNecessario]['vitorias'] < 1;
                $classeDisponibilidade = $pontoUsado
                    ? 'usado'
                    : ($pontoBloqueado ? 'bloqueado' : '');
                $rotuloPonto = $pontoUsado
                    ? 'Ponto de exploração já utilizado'
                    : ($pontoBloqueado
                        ? 'Bloqueado: vença ' . $nomeLiderNecessario . ' para liberar'
                        : 'Explorar ponto de interrogação ' . $idPonto);
                $tituloPonto = $pontoUsado
                    ? 'Este ponto já foi explorado'
                    : ($pontoBloqueado
                        ? 'Vença ' . $nomeLiderNecessario . ' para liberar este ponto'
                        : 'Explorar');
                ?>
                <form
                    class="evento-mapa evento-ponto-<?= $idPonto ?> <?= $classeDisponibilidade ?>"
                    method="post"
                    action="/projetoFinalPok-mon/public/index.php?rota=mapa"
                >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($csrfMapa, ENT_QUOTES, 'UTF-8') ?>"
                    >
                    <input type="hidden" name="id_ponto" value="<?= $idPonto ?>">
                    <button
                        type="submit"
                        <?= $pontoUsado || $pontoBloqueado ? 'disabled' : '' ?>
                        aria-label="<?= htmlspecialchars($rotuloPonto, ENT_QUOTES, 'UTF-8') ?>"
                        title="<?= htmlspecialchars($tituloPonto, ENT_QUOTES, 'UTF-8') ?>"
                    >
                        <span class="sr-only">
                            <?= htmlspecialchars($rotuloPonto, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </button>
                </form>
            <?php endfor; ?>

            <?php if ($resultadoEvento !== null): ?>
                <p class="resultado-evento" role="status">
                    <?= htmlspecialchars($resultadoEvento['mensagem'], ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>
        <?php endif; ?>

    </main>

    <!-- Retorna ao painel do jogador sem alterar o progresso da jornada. -->
    <a href="/projetoFinalPok-mon/public/index.php?rota=jogar" class="btn_pixel">
            <img src="/projetoFinalPok-mon/public/css/img/btn_voltar.png" alt="Voltar" style="width: 200px !important; height: auto;">
    </a>
    <!-- Reutiliza o player compartilhado para manter a música entre as telas. -->
    <?php require __DIR__ . '/../layouts/music_player.php'; ?>
</body>
</html>