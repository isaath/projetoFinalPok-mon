<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PokeBagual | Bolicho</title>
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/bolicho.css?v=4">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/music_player.css">
    <script src="/projetoFinalPok-mon/public/js/pocoes_images.js" defer></script>
</head>
<body>
    <main class="painel-pixel bolicho">
    

    <header class="bolicho-cabecalho">
        <div>
            <h1>Bolicho</h1>
        </div>

        <p>
            <!-- O saldo é carregado pelo Controller e a imagem é apenas decorativa. -->
            Saldo: <strong><?= number_format($saldo, 0, ',', '.') ?></strong> PokePila
            <img class="icone-moeda-saldo" src="/projetoFinalPok-mon/public/css/img/moeda.png" alt="" aria-hidden="true">
        </p>
    </header>

    <?php if ($mensagem !== null): ?>
        <!-- Exibe o retorno de uma compra somente após o redirecionamento do formulário. -->
        <?php if (is_array($mensagem) && ($mensagem['tipo'] ?? null) === 'compra'): ?>
            <p class="mensagem" role="status">
                <?= htmlspecialchars((string) ($mensagem['item'] ?? 'Item'), ENT_QUOTES, 'UTF-8') ?>
                comprado com sucesso. Saldo restante:
                <strong><?= number_format((int) ($mensagem['saldo'] ?? 0), 0, ',', '.') ?></strong>
                PokePila
                <img class="icone-moeda-saldo" src="/projetoFinalPok-mon/public/css/img/moeda.png" alt="" aria-hidden="true">
            </p>
        <?php elseif (is_string($mensagem)): ?>
            <p class="mensagem" role="status"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($erro !== null): ?>
        <p class="mensagem-erro" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <!-- Cada card usa preço e estoque atuais vindos do banco de dados. -->
    <section class="grade-itens" aria-label="Itens disponíveis">
        <?php foreach ($itens as $item): ?>
            <?php
            $nomeItem = (string) $item['nome'];
            $slugItem = strtolower(trim(
                (string) preg_replace('/[^a-z0-9]+/i', '-', $nomeItem),
                '-'
            ));
            ?>
            <article class="card-item">
                <h2><?= htmlspecialchars($nomeItem, ENT_QUOTES, 'UTF-8') ?></h2>
                <!-- O script consulta a imagem pelo nome; o envio da compra usa o ID persistido. -->
                <img
                    class="imagem-item"
                    data-nome="<?= htmlspecialchars($slugItem, ENT_QUOTES, 'UTF-8') ?>"
                    alt="<?= htmlspecialchars($nomeItem, ENT_QUOTES, 'UTF-8') ?>"
                    loading="lazy"
                >
                <p><?= htmlspecialchars((string) ($item['descricao'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                <p>Preço: <?= (int) $item['preco'] ?> PokePila</p>
                <p>No inventário: <?= (int) $item['quantidade'] ?></p>
                <form method="post" action="/projetoFinalPok-mon/public/index.php?rota=bolicho">
                    <!-- O token protege o POST; o servidor ainda valida saldo e preço no Model. -->
                    <input type="hidden" name="csrf_token"
                        value="<?= htmlspecialchars($csrfBolicho, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id_item" value="<?= (int) $item['id_item'] ?>">
                    <!-- O bloqueio melhora a interface, mas não substitui a validação no servidor. -->
                    <button type="submit" <?= $saldo < (int) $item['preco'] ? 'disabled' : '' ?>>
                        Comprar
                    </button>
                </form>
            </article>
        <?php endforeach; ?>
    </section>

    <a href="/projetoFinalPok-mon/public/index.php?rota=jogar" class="btn_pixel">
        <img src="/projetoFinalPok-mon/public/css/img/btn_voltar.png" alt="Voltar" style="width: 200px !important; height: auto;">
    </a>
</main>
<?php require __DIR__ . '/../layouts/music_player.php'; ?>
</body>
</html>
