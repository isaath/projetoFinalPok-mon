<?php
// Na apresentação: UsuarioController valida o POST e usa Usuario para gravar a
// conta; em caso de erro, devolve estes valores para não perder o preenchimento.
$erro = $erro ?? null;
$nome = $nome ?? '';
$email = $email ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- Metadados da página e arquivos de fonte e estilo. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - PokeBagual</title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/login.css?v=auth-buttons-3">
</head>
<body>
    <!-- Cartão principal que contém o formulário de criação da conta. -->
    <div class="cartao-login">
        <h2 class="titulo-login">CRIAR CONTA</h2>

        <!-- Mostra erros de validação devolvidos pelo Controller. -->
        <?php if ($erro !== null): ?>
            <p role="alert">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>
            
        <!-- Envia os dados do formulário ao Controller de cadastro. -->
        <form action="index.php?rota=registrar" method="POST" class="form-login" enctype="multipart/form-data">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrfAutenticacao, ENT_QUOTES, 'UTF-8') ?>"
            >
            <div class="corpo_cartao">
                <div class="box_inputs">
                    <!-- Campo visível para selecionar foto por mouse ou teclado. -->
                    <div class="campo-grupo">
                        <label for="foto">FOTO DE PERFIL (OPCIONAL):</label>
                        <input
                            type="file"
                            id="foto"
                            name="foto"
                            accept="image/jpeg,image/png"
                        >
                        <small>JPG ou PNG, com até 2 MB.</small>
                    </div>

                    <!-- Nome público da nova conta. -->
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

                    <!-- E-mail usado para identificar a conta no login. -->
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

                    <!-- Senha que será validada e armazenada com hash no servidor. -->
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

                    <!-- Confirmação comparada com a senha pelo Controller. -->
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

            <!-- Botão de envio do cadastro e link para retornar ao login. -->
            <div class="box-botoes">
                <button type="submit" class="btn_pixel auth-action">
                    <img src="/projetoFinalPok-mon/public/css/img/btn_registrar.png" alt="Registrar">
                </button>
                <a href="/projetoFinalPok-mon/public/index.php?rota=login" class="btn_pixel auth-action">
                    <img src="/projetoFinalPok-mon/public/css/img/btn_voltar.png" alt="Voltar ao login">
                </a>
            </div>
        </form>
    </div>
</body>
</html>
