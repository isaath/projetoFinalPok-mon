<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pokebagual</title>
    <!-- Fonte Pixel Art -->
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../public/css/login.css">
</head>
<body>

    <div class="cartao-login">
        <h2 class="titulo-login">LOGIN DO USUÁRIO</h2>

        <form action="/login" method="POST" class="form-login">
            <div class="corpo_cartao">
                <!--foto do usr -->
                <div class="box_foto_usr">
                    <!--slides do sor!! -->
                    <form action='upload.php' method='POST' enctype='multipart/form-data'>
                        <input type='file' name='foto_usr' required>
                    </form>
                </div>

                <!-- Campos de Texto da Direita -->
                <div class="box_inputs">
                    <div class="campo-grupo">
                        <label for="email">EMAIL:</label>
                        <input type="email" id="email" name="email" required autocomplete="off">
                    </div>


                    <div class="campo-grupo">
                        <label for="nome">NOME DE USUÁRIO:</label>
                        <input type="text" id="nome" name="nome" required autocomplete="off">
                    </div>

                    <div class="campo-grupo">
                        <label for="senha">SENHA:</label>
                        <input type="password" id="senha" name="senha" required>
                    </div>
                </div>
            </div>

            <!-- Botões Inferiores -->
            <div class="box-botoes">
                <button type="submit" class="btn-pixel">LOGIN</button>
                <a href="../layouts/painel_jogar.php" class="btn-pixel">REGISTRAR</a>
                <a href="../layouts/painel_inicial.php" class="btn-pixel">VOLTAR</a>
            </div>
        </form>
    </div>

</body>
</html>