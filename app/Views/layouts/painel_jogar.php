<?php
// Na apresentação: UsuarioController protege e carrega este painel após o
// login; os links levam às rotas do mapa, perfil, loja, time e páginas públicas.
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- Define codificação, responsividade, título e CSS do painel. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PokeBagual - Jogar</title>
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/painel_jogar.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/music_player.css">
</head>

<body>
    <!-- Menu de navegação lateral para as telas auxiliares. -->
    <div class="menu_lateral">
        <a href="/projetoFinalPok-mon/public/index.php?rota=sobre" class="btn_pixel">
        <img src="/projetoFinalPok-mon/public/css/img/btn_sobre.png" alt="Sobre" style="width: 185px !important; height: auto;">
    </a>
    <form method="post" action="/projetoFinalPok-mon/public/index.php?rota=sair" class="form-sair">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($csrfAutenticacao, ENT_QUOTES, 'UTF-8') ?>"
        >
        <button type="submit" class="btn_pixel">
            <img src="/projetoFinalPok-mon/public/css/img/btn_sair.png" alt="Sair" style="width: 170px !important; height: auto;">
        </button>
    </form>
    <a href="/projetoFinalPok-mon/public/index.php?rota=perfil" class="btn_pixel">
        <img src="/projetoFinalPok-mon/public/css/img/btn_perfil.png" alt="Perfil" style="width: 160px !important; height: auto;">
    </a>
    <a href="/projetoFinalPok-mon/public/index.php?rota=bolicho" class="btn_pixel">
        <img src="/projetoFinalPok-mon/public/css/img/btn_bolicho.png" alt="Bolicho" style="width: 200px !important; height: auto;">
    </a>
    <a href="/projetoFinalPok-mon/public/index.php?rota=centro" class="btn_pixel">
        <img src="/projetoFinalPok-mon/public/css/img/btn_pokecenter.png" alt="Pokecenter"
             style="width: 220px !important; height: auto;">
    </a>
    <a href="/projetoFinalPok-mon/public/index.php?rota=meu_time" class="btn_pixel">
        <img src="/projetoFinalPok-mon/public/css/img/btn_time.png" alt="Time" style="width: 205px !important; height: auto;">
    </a>

</div>

    <!-- Botão principal para iniciar o fluxo de batalha. -->
    <a href="/projetoFinalPok-mon/public/index.php?rota=mapa" class="btn_pixel btn_jogar">
        <img src="/projetoFinalPok-mon/public/css/img/btn_jogar.png" alt="Jogar" style="width: 400px !important; height: auto;">
    </a>
    <!-- Inclui os controles compartilhados de volume e reprodução da música. -->
    <?php require __DIR__ . '/music_player.php'; ?>
</body>
</html>