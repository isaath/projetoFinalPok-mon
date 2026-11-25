<?php
// Evita renderizar a tela sem os dados já separados pelo Controller.
if (!isset($ativos, $reservas, $token) || !is_array($ativos) || !is_array($reservas)) {
    http_response_code(404);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu time | PokeBagual</title>
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/meu_time.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/music_player.css">
    <script src="/projetoFinalPok-mon/public/js/pokemon_images.js" defer></script>
</head>
<body>
    <main class="pagina-time">
        <a href="/projetoFinalPok-mon/public/index.php?rota=jogar" class="btn_pixel">
            <img src="/projetoFinalPok-mon/public/css/img/btn_voltar.png" alt="Voltar ao painel"
                 style="width: 150px !important; height: auto;">
        </a>

        <header class="cabecalho-time">
            <p class="etiqueta">GERENCIE SUA EQUIPE</p>
            <h1>Meu time</h1>
            <p>O Pokémon na posição 1 começa a batalha. Se ele desmaiar, entra o Pokémon da próxima posição.</p>
        </header>

        <?php if (!empty($mensagem)): ?>
            <p class="mensagem sucesso" role="status">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($erro)): ?>
            <p class="mensagem erro" role="alert">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <section class="secao-time" aria-labelledby="titulo-ativos">
            <div class="titulo-secao">
                <h2 id="titulo-ativos">Time ativo</h2>
                <span>3 posições de batalha</span>
            </div>

            <?php if ($ativos === []): ?>
                <p class="vazio">Você ainda não possui Pokémon ativos.</p>
            <?php else: ?>
                <!-- A ordem destes cards define quem entra primeiro na batalha. -->
                <div class="grade-time">
                    <?php foreach ($ativos as $pokemon): ?>
                        <?php $posicaoAtual = (int) $pokemon['posicao']; ?>
                        <article class="cartao-pokemon ativo">
                            <span class="posicao">Pokémon <?= $posicaoAtual ?></span>
                            <img
                                class="imagem-pokemon"
                                data-nome="<?= htmlspecialchars(strtolower($pokemon['nome']), ENT_QUOTES, 'UTF-8') ?>"
                                alt="Imagem de <?= htmlspecialchars($pokemon['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                loading="lazy"
                            ><h3><?= htmlspecialchars(ucfirst($pokemon['nome']), ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="tipo">
                                <?= htmlspecialchars($pokemon['tipo'] ?? 'Sem tipo', ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p class="vida">
                                HP <?= (int) $pokemon['hp_atual'] ?> / <?= (int) $pokemon['hp_maximo'] ?>
                            </p>

                            <?php if ($posicaoAtual === 1): ?>
                                <p class="papel">Inicia a batalha</p>
                            <?php else: ?>
                                <p class="papel">Entra se o anterior desmaiar</p>
                            <?php endif; ?>

                            <div class="botoes-prioridade">
                                <?php for ($destino = 1; $destino <= 3; $destino++): ?>
                                    <?php if ($destino !== $posicaoAtual): ?>
                                        <!-- Cada formulário solicita ao Controller uma nova posição prioritária. -->
                                        <form method="post" action="/projetoFinalPok-mon/public/index.php?rota=meu_time">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="acao" value="prioridade">
                                            <input type="hidden" name="id_time_pokemon"
                                                   value="<?= (int) $pokemon['id_time_pokemon'] ?>">
                                            <input type="hidden" name="posicao" value="<?= $destino ?>">

                                            <button type="submit" class="btn_pixel" style="background: none; border: none; padding: 0; cursor: pointer;">
    <img src="/projetoFinalPok-mon/public/css/img/mudar_p_<?= $destino ?>.png" 
         alt="Mover para <?= $destino ?>ª opção" 
         style="width: 150px !important; height: auto;">
</button>
                                        </form>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="secao-time reservas" aria-labelledby="titulo-reservas">
            <div class="titulo-secao">
                <h2 id="titulo-reservas">Reservas</h2>
                <span>Pokémon fora do time ativo</span>
            </div>

            <?php if ($reservas === []): ?>
                <p class="vazio">Você ainda não possui Pokémon na reserva.</p>
            <?php else: ?>
                <!-- Um Pokémon reserva pode substituir apenas o membro da posição 3. -->
                <div class="grade-reservas">
                    <?php foreach ($reservas as $pokemon): ?>
                        <article class="cartao-pokemon reserva">
                            <img
    class="imagem-pokemon"
    data-nome="<?= htmlspecialchars(strtolower($pokemon['nome']), ENT_QUOTES, 'UTF-8') ?>"
    alt="Imagem de <?= htmlspecialchars($pokemon['nome'], ENT_QUOTES, 'UTF-8') ?>"
    loading="lazy"
><h3><?= htmlspecialchars(ucfirst($pokemon['nome']), ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="tipo">
                                <?= htmlspecialchars($pokemon['tipo'] ?? 'Sem tipo', ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p class="vida">
                                HP <?= (int) $pokemon['hp_atual'] ?> / <?= (int) $pokemon['hp_maximo'] ?>
                            </p>
                            <form method="post" action="/projetoFinalPok-mon/public/index.php?rota=meu_time">
                                <input type="hidden" name="csrf_token"
                                       value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="acao" value="colocar">
                                <input type="hidden" name="id_time_pokemon"
                                       value="<?= (int) $pokemon['id_time_pokemon'] ?>">
                                <button type="submit" class="btn_pixel">
                                    <img src="/projetoFinalPok-mon/public/css/img/btn_jogar.png"
                                         alt="Colocar no lugar do Pokémon 3"
                                         style="width: 170px !important; height: auto;">
                                </button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
                <p class="aviso-reserva">
                    Ao colocar um Pokémon da reserva, o que estiver na posição 3 vai para a reserva.
                </p>
            <?php endif; ?>
        </section>
    </main>
    <!-- Reaproveita o player global sem duplicar sua implementação nesta View. -->
    <?php require __DIR__ . '/../layouts/music_player.php'; ?>
</body>
</html>
