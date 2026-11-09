<?php
// Menu principal do jogo após o login, com links para a área de jogo, perfil e sobre.
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- Define codificação, responsividade, título e CSS do painel. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pokebagual - Jogar</title>
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/painel_jogar.css">
</head>

<body>
    <!-- Áudio de fundo em repetição; o navegador pode exigir interação para iniciar. -->
    <audio id="musica" src="/projetoFinalPok-mon/public/audio/musica_jogar.mp3" loop autoplay></audio>

    <script>
        // Navegadores podem bloquear áudio automático até que o usuário interaja.
        // Busca o elemento de áudio pelo ID definido acima.
        const musica = document.getElementById('musica');
        // Define o volume em uma escala de zero a um.
        musica.volume = 0.4;

        // Se a reprodução automática falhar, tenta novamente no primeiro clique.
        musica.play().catch(() => {
            document.addEventListener('click', () => musica.play(), { once: true });
        });
    </script>

    <!-- Menu de navegação lateral para as telas auxiliares. -->
    <div class="menu_lateral">
        <a href="/projetoFinalPok-mon/app/Views/layouts/sobre.php" class="btn_pixel">
        <img src="/projetoFinalPok-mon/public/css/img/btn_sobre.png" alt="Sobre" style="width: 160px !important; height: auto;">
    </a>
    <a href="/projetoFinalPok-mon/app/Views/layouts/painel_inicial.php" class="btn_pixel">
        <img src="/projetoFinalPok-mon/public/css/img/btn_home.png" alt="Home" style="width: 150px !important; height: auto;">
    </a>
    <a href="/projetoFinalPok-mon/app/Views/usuario/perfil.php" class="btn_pixel">
        <img src="/projetoFinalPok-mon/public/css/img/btn_perfil.png" alt="Perfil" style="width: 150px !important; height: auto;">
    </a>
    <a href="/projetoFinalPok-mon/app/Views/bolicho/bolicho.php" class="btn_pixel">
        <img src="/projetoFinalPok-mon/public/css/img/btn_bolicho.png" alt="Bolicho" style="width: 190px !important; height: auto;">
    </a>
    <a href="/projetoFinalPok-mon/app/Views/time/escolher_time.php" class="btn_pixel">
        <img src="/projetoFinalPok-mon/public/css/img/btn_time.png" alt="Time" style="width: 150px !important; height: auto;">
    </a>

</div>

    <!-- Botão principal para iniciar o fluxo de batalha. -->
    <a href="/projetoFinalPok-mon/app/Views/batalha/escolher_pokemon.php" class="btn_pixel btn_jogar">
        <img src="/projetoFinalPok-mon/public/css/img/btn_jogar.png" alt="Jogar" style="width: 400px !important; height: auto;">
    </a>
</body>
</html>