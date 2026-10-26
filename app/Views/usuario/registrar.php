<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Pokebagual</title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="cartao-login">
        <h2 class="titulo-login">CRIAR CONTA</h2>

        <?php if ($erro !== null): ?>
            <p role="alert">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form action="index.php?rota=registrar" method="POST" class="form-login">
            <div class="corpo_cartao">
                <div class="box_inputs">
                    <div class="campo-grupo">
                        <label for="nome">NOME:</label>
                        <input
                            type="text"
                            id="nome"
                            name="nome"
                            value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>"
                            maxlength="255"
                            autocomplete="name"
                            required
                        >
                    </div>

                    <div class="campo-grupo">
                        <label for="email">EMAIL:</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                            maxlength="255"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div class="campo-grupo">
                        <label for="senha">SENHA (MÍNIMO 8 CARACTERES):</label>
                        <input
                            type="password"
                            id="senha"
                            name="senha"
                            minlength="8"
                            maxlength="72"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <div class="campo-grupo">
                        <label for="confirmar_senha">CONFIRME A SENHA:</label>
                        <input
                            type="password"
                            id="confirmar_senha"
                            name="confirmar_senha"
                            minlength="8"
                            maxlength="72"
                            autocomplete="new-password"
                            required
                        >
                    </div>
                </div>
            </div>

            <div class="box-botoes">
                <button type="submit" class="btn-pixel">REGISTRAR</button>
                <a href="index.php?rota=login" class="btn-pixel">VOLTAR AO LOGIN</a>
            </div>
        </form>
    </div>
</body>
</html>
