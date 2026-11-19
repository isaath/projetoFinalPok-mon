<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PokeBagual | Bolicho</title>
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/bolicho.css?v=3">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
    <script src="/projetoFinalPok-mon/public/js/pocoes_images.js" defer></script>
</head>
<body>
    <main class="painel-pixel bolicho">
    <a href="/projetoFinalPok-mon/public/index.php?rota=jogar" class="btn_pixel">
        Voltar ao jogo
    </a>

    <header class="bolicho-cabecalho">
        <div>
            <h1>Bolicho</h1>
            <p>Escolha os itens para ajudar seu time</p>
        </div>

        <p>Saldo: <strong>0</strong> Pokédollars</p>
    </header>

    <section class="grade-itens" aria-label="Itens disponíveis">
        <article class="card-item">
            <h2>Potion</h2>
            <img
                class="imagem-item"
                data-nome="potion"
                alt="Potion"
                loading="lazy"
            >
            <p>Recupera 20 HP.</p>
            <p>Preço: 25 Pokédollars</p>
            <button type="button">Comprar</button>
        </article>

        <article class="card-item">
            <h2>Super Potion</h2>
            <img
                class="imagem-item"
                data-nome="super-potion"
                alt="Super Potion"
                loading="lazy"
            >
            <p>Recupera 50 HP.</p>
            <p>Preço: 50 Pokédollars</p>
            <button type="button">Comprar</button>
        </article>

        <article class="card-item">
            <h2>Hyper Potion</h2>
            <img
                class="imagem-item"
                data-nome="hyper-potion"
                alt="Hyper Potion"
                loading="lazy"
            >
            <p>Recupera 200 HP.</p>
            <p>Preço: 100 Pokédollars</p>
            <button type="button">Comprar</button>
        </article>

        <article class="card-item">
            <h2>Max Potion</h2>
            <img
                class="imagem-item"
                data-nome="max-potion"
                alt="Max Potion"
                loading="lazy"
            >
            <p>Recupera todo os HP.</p>
            <p>Preço: 180 Pokédollars</p>
            <button type="button">Comprar</button>
        </article>
        
        <article class="card-item">
            <h2>Full Restore</h2>
            <img
                class="imagem-item"
                data-nome="full-restore"
                alt="Full Restore"
                loading="lazy"
            >
            <p>Recupera todo os HP +  cura todas as condições de status</p>
            <p>Preço: 220 Pokédollars</p>
            <button type="button">Comprar</button>
        </article>

        <article class="card-item">
            <h2>Antidote</h2>
            <img
                class="imagem-item"
                data-nome="antidote"
                alt="Antidote"
                loading="lazy"
            >
            <p>Cura o status de envenenamento</p>
            <p>Preço: 15 Pokédollars</p>
            <button type="button">Comprar</button>
        </article>

        <article class="card-item">
            <h2>Burn Heal</h2>
            <img
                class="imagem-item"
                data-nome="burn-heal"
                alt="Burn Heal"
                loading="lazy"
            >
            <p>Remove a condição de status queimado</p>
            <p>Preço: 15 Pokédollars</p>
            <button type="button">Comprar</button>
        </article>

        <article class="card-item">
            <h2>Ice Heal</h2>
            <img
                class="imagem-item"
                data-nome="ice-heal"
                alt="Ice Heal"
                loading="lazy"
            >
            <p>Remove a condição de status congelado</p>
            <p>Preço: 15 Pokédollars</p>
            <button type="button">Comprar</button>
        </article>

        <article class="card-item">
            <h2>Awakening</h2>
            <img
                class="imagem-item"
                data-nome="awakening"
                alt="Awakening"
                loading="lazy"
            >
            <p>Acorda o Pokémon adormecido</p>
            <p>Preço: 15 Pokédollars</p>
            <button type="button">Comprar</button>
        </article>

        <article class="card-item">
            <h2>Paralyze Heal</h2>
            <img
                class="imagem-item"
                data-nome="paralyze-heal"
                alt="Paralyze Heal"
                loading="lazy"
            >
            <p>Cura o status de paralisia</p>
            <p>Preço: 15 Pokédollars</p>
            <button type="button">Comprar</button>
        </article>

        <article class="card-item">
            <h2>Full Heal</h2>
            <img
                class="imagem-item"
                data-nome="full-heal"
                alt="Full Heal"
                loading="lazy"
            >
            <p>Remove todas as condições de status negativas</p>
            <p>Preço: 30 Pokédollars</p>
            <button type="button">Comprar</button>
        </article>

        <article class="card-item">
            <h2>Revive</h2>
            <img
                class="imagem-item"
                data-nome="revive"
                alt="Revive"
                loading="lazy"
            >
            <p>Restaura a vida de um Pokémon desmaiado</p>
            <p>Preço: 70 Pokédollars</p>
            <button type="button">Comprar</button>
        </article>


        
    </section>
</main>
</body>
</html>