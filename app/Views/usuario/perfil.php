<?php
$foto = $perfil['foto'] ?? null;
$tipoFoto = $perfil['tipo_foto'] ?? '';
$fotoPermitida = in_array($tipoFoto, ['image/jpeg', 'image/png'], true);
$fotoSrc = is_string($foto) && $foto !== '' && $fotoPermitida
    ? 'data:' . $tipoFoto . ';base64,' . base64_encode($foto)
    : null;
$nomePerfil = (string) ($perfil['nome'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PokeBagual | Perfil do usuário</title>
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/centro.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/painel_perfil.css?v=2">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
</head>
<body>
    
    <main class="painel-pixel perfil">
        

        <header class="cabecalho perfil-cabecalho">
            <div>
                <p class="etiqueta">SEU PERFIL</p>
                <h1>Perfil do jogador</h1>
            </div>
        </header>

        <?php if ($erro !== null): ?>
            <p class="mensagem erro" role="alert">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php else: ?>
            <section class="dados-perfil" aria-label="Dados do usuário">
                <div class="foto-perfil">
                    <?php if ($fotoSrc !== null): ?>
                        <img
                            src="<?= htmlspecialchars($fotoSrc, ENT_QUOTES, 'UTF-8') ?>"
                            alt="Foto de perfil de <?= htmlspecialchars($nomePerfil, ENT_QUOTES, 'UTF-8') ?>"
                        >
                    <?php else: ?>
                        <span>Sem foto</span>
                    <?php endif; ?>
                </div>
                <h2 class="nome-perfil">
                    <?= htmlspecialchars($nomePerfil, ENT_QUOTES, 'UTF-8') ?>
                </h2>
            </section>
        <?php endif; ?>
    </main>
    <a href="/projetoFinalPok-mon/public/index.php?rota=jogar" class="btn_pixel">
            <img src="/projetoFinalPok-mon/public/css/img/btn_voltar.png" alt="Voltar" style="width: 200px !important; height: auto;">
        </a>
</body>
</html>
