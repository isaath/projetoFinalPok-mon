<?php
// Página de apresentação do jogo, com descrição da proposta e retorno ao painel principal.
?><!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- Metadados usados pelo navegador para exibir a página corretamente. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre o jogo</title>
    <!-- Carrega o estilo externo específico desta tela. -->
    <link rel="stylesheet" href="../../../public/css/sobre.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
</head>
<body>
    
    <main class="historia_sobre painel-pixel">
        

        <header>
            <p class="etiqueta">CONHEÇA O POKEBAGUAL</p>
        </header>

        <p>
            Pokébagual é um jogo de batalhas Pokémon em que cada jogador escolhe seus Pokémon favoritos
            para formar um time e enfrentar adversários em combates cheios de estratégia. A cada vitória,
            é possível ganhar recompensas, evoluir seus Pokémon e se preparar para desafios ainda maiores.
            Mais do que um jogo de batalhas, Pokébagual é uma homenagem ao Rio Grande do Sul. Inspirado
            nas belezas do estado, o jogo apresenta diversas paisagens e pontos turísticos que servem de
            cenário para cada aventura. Ao longo da jornada, o jogador conhece um pouco da cultura e das
            paisagens gaúchas enquanto se diverte.
        </p>

        <p class="destaque">
            Monte seu time, explore o cenário e prove que você é um verdadeiro mestre Pokémon!
        </p>
    </main>
    <a href="/projetoFinalPok-mon/app/Views/layouts/painel_jogar.php" class="btn_pixel">
            <img src="/projetoFinalPok-mon/public/css/img/btn_voltar.png" alt="Voltar" style="width: 200px !important; height: auto;">
        </a>
</body>
</html>