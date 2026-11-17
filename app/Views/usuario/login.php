<?php
// Recebe os dados vindos do Controller para exibir mensagens e manter o e-mail digitado.
$erro = $erro ?? null;
$sucesso = $sucesso ?? false;
$email = $email ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- Metadados para caracteres, adaptação a dispositivos e título da aba. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pokebagual</title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/login.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
</head>
<body>
    <!-- Cartão visual que agrupa o título, mensagens e formulário de login. -->
    <div class="cartao-login">
        <h2 class="titulo-login">LOGIN DO USUÁRIO</h2>

        <!-- Exibe uma mensagem de erro enviada pelo Controller, se houver. -->
        <?php if ($erro !== null): ?>
            <p role="alert">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <!-- Confirma que o cadastro foi concluído quando esse aviso veio na URL. -->
        <?php if ($sucesso): ?>
            <p role="status">Cadastro realizado! Agora você pode entrar.</p>
        <?php endif; ?>

        <!-- Envia e-mail e senha ao Controller usando o método POST. -->
        <form action="/projetoFinalPok-mon/public/index.php?rota=login" method="POST" class="form-login">
            <div class="corpo_cartao">
                <div class="box_inputs">
                    <!-- Campo de e-mail associado ao identificador usado pelo label. -->
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

                    <!-- Campo de senha; o navegador oculta os caracteres digitados. -->
                    <div class="campo-grupo">
                        <label for="senha">SENHA:</label>
                        <input type="password" id="senha" name="senha" required autocomplete="current-password">
                    </div>
                </div>
            </div>

            <!-- Ações: entrar, abrir cadastro ou voltar à listagem. -->
            <div class="box-botoes">
                <button type="submit" class="btn_pixel">
                    <img src="/projetoFinalPok-mon/public/css/img/btn_login.png" alt="Login"
                         style="width: 150px !important; height: auto;">
                </button>
                <a href="/projetoFinalPok-mon/public/index.php?rota=registrar" class="btn_pixel">
                    <img src="/projetoFinalPok-mon/public/css/img/btn_registrar.png" alt="Registrar"
                         style="width: 125px !important; height: auto;">
                </a>
                <a href="/projetoFinalPok-mon/app/Views/layouts/painel_inicial.php" class="btn_pixel">
    <img src="/projetoFinalPok-mon/public/css/img/btn_voltar.png" alt="Voltar"
         style="width: 125px !important; height: auto;">
</a>
            </div>
        </form>
    </div>

</body>
</html>
