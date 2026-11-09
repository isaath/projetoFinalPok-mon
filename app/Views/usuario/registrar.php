<?php
// Mantém os valores já digitados pelo usuário em caso de erro de validação.
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
    <title>Cadastro - Pokebagual</title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
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
            <div class="corpo_cartao">
                <div class="box_foto_usr">
    <label for="foto">
        <img src="/projetoFinalPok-mon/public/css/img/foto_perfil.png" alt="Escolher foto de perfil" class="img-avatar" id="preview-foto">
    </label>
    <input type="file" id="foto" name="foto" accept="image/jpeg,image/png" hidden>
</div>
                <div class="box_inputs">
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
                <button type="submit" class="btn-pixel">REGISTRAR</button>
                <a href="index.php?rota=login" class="btn-pixel">VOLTAR AO LOGIN</a>
            </div>
        </form>
    </div>
</body>
</html>
