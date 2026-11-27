<?php
// Na apresentação: TimeController prepara ativos, reservas e token CSRF a
// partir do Model TimePokemon. Os formulários voltam à rota "meu_time".
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

                            <?php if (!empty($pokemon['nome_evolucao'])): ?>
                                <!-- A descrição aparece para toda espécie com próxima evolução cadastrada. -->
                                <div class="evolucao">
                                    <p>
                                        Próxima evolução:
                                        <strong><?= htmlspecialchars(ucfirst($pokemon['nome_evolucao']), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </p>
                                    <p>Use um Item Evolutivo para alcançar esse próximo estágio.</p>
                                    <?php if ((int) $pokemon['evolucao_ja_possuida'] === 1): ?>
                                        <p>Seu time já possui essa espécie.</p>
                                    <?php elseif ((int) $pokemon['quantidade_item_evolutivo'] > 0): ?>
                                        <!-- O botão só aparece quando há item; o Controller busca os dados e o Model salva a evolução. -->
                                        <form method="post" action="/projetoFinalPok-mon/public/index.php?rota=meu_time"
                                              onsubmit="return confirm('Evoluir este Pokémon? Um Item Evolutivo será consumido e o HP será restaurado ao máximo.');">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="acao" value="evoluir">
                                            <input type="hidden" name="id_time_pokemon"
                                                   value="<?= (int) $pokemon['id_time_pokemon'] ?>">
                                            <button type="submit" class="btn-evoluir"
                                                    aria-label="Evoluir para <?= htmlspecialchars(ucfirst($pokemon['nome_evolucao']), ENT_QUOTES, 'UTF-8') ?>">
                                                <img src="/projetoFinalPok-mon/public/css/img/btn_evoluir.png"
                                                     alt="Evoluir">
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <p>Compre um Item Evolutivo no Bolicho para evoluir.</p>
                                    <?php endif; ?>
                                </div>
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
                <!-- Cada Pokémon da reserva pode substituir qualquer posição do time ativo. -->
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
                            <?php if (!empty($pokemon['nome_evolucao'])): ?>
                                <!-- A mesma descrição e regra de evolução valem para Pokémon na reserva. -->
                                <div class="evolucao">
                                    <p>
                                        Próxima evolução:
                                        <strong><?= htmlspecialchars(ucfirst($pokemon['nome_evolucao']), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </p>
                                    <p>Use um Item Evolutivo para alcançar esse próximo estágio.</p>
                                    <?php if ((int) $pokemon['evolucao_ja_possuida'] === 1): ?>
                                        <p>Seu time já possui essa espécie.</p>
                                    <?php elseif ((int) $pokemon['quantidade_item_evolutivo'] > 0): ?>
                                        <!-- O botão só aparece quando há item; o Controller busca os dados e o Model salva a evolução. -->
                                        <form method="post" action="/projetoFinalPok-mon/public/index.php?rota=meu_time"
                                              onsubmit="return confirm('Evoluir este Pokémon? Um Item Evolutivo será consumido e o HP será restaurado ao máximo.');">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="acao" value="evoluir">
                                            <input type="hidden" name="id_time_pokemon"
                                                   value="<?= (int) $pokemon['id_time_pokemon'] ?>">
                                            <button type="submit" class="btn-evoluir"
                                                    aria-label="Evoluir para <?= htmlspecialchars(ucfirst($pokemon['nome_evolucao']), ENT_QUOTES, 'UTF-8') ?>">
                                                <img src="/projetoFinalPok-mon/public/css/img/btn_evoluir.png"
                                                     alt="Evoluir">
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <p>Compre um Item Evolutivo no Bolicho para evoluir.</p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="botoes-reserva">
                                <?php for ($destino = 1; $destino <= 3; $destino++): ?>
                                    <form method="post" action="/projetoFinalPok-mon/public/index.php?rota=meu_time">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="acao" value="colocar">
                                        <input type="hidden" name="id_time_pokemon"
                                               value="<?= (int) $pokemon['id_time_pokemon'] ?>">
                                        <input type="hidden" name="posicao" value="<?= $destino ?>">
                                        <button type="submit" class="btn-mudar-posicao">
                                            <img
                                                src="/projetoFinalPok-mon/public/css/img/mudar_p_<?= $destino ?>.png"
                                                alt="Mudar para posição <?= $destino ?>"
                                            >
                                        </button>
                                    </form>
                                <?php endfor; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <p class="aviso-reserva">
                    Ao escolher uma posição, o Pokémon que já está nela vai para a reserva.
                </p>
            <?php endif; ?>
        </section>
    </main>
    <!-- Reaproveita o player global sem duplicar sua implementação nesta View. -->
    <?php require __DIR__ . '/../layouts/music_player.php'; ?>
</body>
</html>
