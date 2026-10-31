<?php
$erro = $erro ?? null;
$sucesso = $sucesso ?? false;
$email = $email ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pokebagual</title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/login.css">
</head>
<body>

    <div class="cartao-login">
        <h2 class="titulo-login">LOGIN DO USUÁRIO</h2>

        <?php if ($erro !== null): ?>
            <p role="alert">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <p role="status">Cadastro realizado! Agora você pode entrar.</p>
        <?php endif; ?>

        <form action="/projetoFinalPok-mon/public/index.php?rota=login" method="POST" class="form-login">
            <div class="corpo_cartao">
                <div class="box_inputs">
                    <div class="campo-grupo">
                        <label for="email">EMAIL:</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                            required
                            autocomplete="email"
                        >
                    </div>

                    <div class="campo-grupo">
                        <label for="senha">SENHA:</label>
                        <input type="password" id="senha" name="senha" required autocomplete="current-password">
                    </div>
                </div>
            </div>

            <div class="box-botoes">
                <button type="submit" class="btn-pixel">LOGIN</button>
                <a href="/projetoFinalPok-mon/public/index.php?rota=registrar" class="btn-pixel">REGISTRAR</a>
                <a href="/projetoFinalPok-mon/public/index.php?rota=pokemons" class="btn-pixel">VOLTAR</a>
            </div>
        </form>
    </div>

</body>
</html>
