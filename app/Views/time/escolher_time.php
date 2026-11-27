<?php
// Na apresentação: TimeController consulta TimeInicial e entrega as opções;
// o formulário envia as três escolhas de volta à rota "escolher_time".
// A View só é renderizada quando recebe as opções e o token preparados pelo Controller.
if (!isset($pokemons, $token) || !is_array($pokemons)) {
    http_response_code(404);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolha seu time | PokeBagual</title>
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/escolher_time.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/music_player.css">
    <script src="/projetoFinalPok-mon/public/js/pokemon_images.js" defer></script>
</head>
<body>
    <main class="selecao">
        <header class="cabecalho">
            <p class="etiqueta">SUA JORNADA COMEÇA AQUI</p>
            <h1>Escolha seu time</h1>
            <p>Selecione três dos cinco Pokémon iniciais. A ordem dos cards define as posições do time.</p>
        </header>

        <?php if ($erro !== null): ?>
            <p class="mensagem-erro" role="alert">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <?php if (count($pokemons) === 5): ?>
            <!-- O servidor valida novamente a seleção; a limitação visual em JavaScript não é segurança. -->
            <form method="post" action="/projetoFinalPok-mon/public/index.php?rota=escolher_time" id="form-time">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                <section class="grade-pokemons" aria-label="Pokémon iniciais disponíveis">
                    <?php foreach ($pokemons as $pokemon): ?>
                        <label class="cartao-pokemon">
                            <input
                                type="checkbox"
                                name="pokemon_ids[]"
                                value="<?= (int) $pokemon['id_pokemon'] ?>"
                            >
                            <span class="marca-selecao" aria-hidden="true"></span>
                            <img
    class="imagem-pokemon"
    data-nome="<?= htmlspecialchars(strtolower($pokemon['nome']), ENT_QUOTES, 'UTF-8') ?>"
    alt="Imagem de <?= htmlspecialchars($pokemon['nome'], ENT_QUOTES, 'UTF-8') ?>"
    loading="lazy"
>
                            <span class="nome-pokemon">
                                <?= htmlspecialchars(ucfirst($pokemon['nome']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span class="tipo-pokemon">
                                <?= htmlspecialchars($pokemon['tipo'] ?? 'Sem tipo', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span class="atributos">
                                <span>HP <strong><?= (int) $pokemon['hp_maximo'] ?></strong></span>
                                <span>Ataque <strong><?= (int) $pokemon['forca_ataque'] ?></strong></span>
                                <span>Defesa <strong><?= (int) $pokemon['defesa'] ?></strong></span>
                                <span>Velocidade <strong><?= (int) $pokemon['velocidade'] ?></strong></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </section>

                <div class="acoes">
                    <p id="contador-selecao" aria-live="polite">Selecionados: 0 de 3</p>
                    <button type="submit" class="btn_pixel">
                        <img src="/projetoFinalPok-mon/public/css/img/btn_jogar.png"
                             alt="Confirmar meu time"
                             style="width: 220px !important; height: auto;">
                    </button>
                </div>
            </form>

            <script>
                // Mantém a contagem atualizada e impede selecionar mais de três cards pela interface.
                const formularioTime = document.getElementById('form-time');
                const contadorSelecao = document.getElementById('contador-selecao');
                const caixasPokemon = formularioTime.querySelectorAll('input[name="pokemon_ids[]"]');

                caixasPokemon.forEach((caixa) => {
                    caixa.addEventListener('change', () => {
                        const selecionados = formularioTime.querySelectorAll(
                            'input[name="pokemon_ids[]"]:checked'
                        );

                        if (selecionados.length > 3) {
                            caixa.checked = false;
                        }

                        const totalSelecionado = formularioTime.querySelectorAll(
                            'input[name="pokemon_ids[]"]:checked'
                        ).length;
                        contadorSelecao.textContent = `Selecionados: ${totalSelecionado} de 3`;
                    });
                });

                formularioTime.addEventListener('submit', (evento) => {
                    const totalSelecionado = formularioTime.querySelectorAll(
                        'input[name="pokemon_ids[]"]:checked'
                    ).length;

                    if (totalSelecionado !== 3) {
                        evento.preventDefault();
                        contadorSelecao.textContent = 'Escolha exatamente três Pokémon para continuar.';
                    }
                });
            </script>
        <?php endif; ?>
    </main>
    <!-- Usa o mesmo componente de música compartilhado pelas demais telas autenticadas. -->
    <?php require __DIR__ . '/../layouts/music_player.php'; ?>
</body>
</html>
