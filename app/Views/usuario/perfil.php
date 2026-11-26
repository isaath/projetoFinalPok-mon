<?php
// Prepara a foto somente se o conteúdo salvo e o tipo MIME forem permitidos.
$foto = $perfil['foto'] ?? null;
$tipoFoto = $perfil['tipo_foto'] ?? '';
$fotoPermitida = in_array($tipoFoto, ['image/jpeg', 'image/png'], true);
$fotoSrc = is_string($foto) && $foto !== '' && $fotoPermitida
    ? 'data:' . $tipoFoto . ';base64,' . base64_encode($foto)
    : null;
$nomePerfil = (string) ($perfil['nome'] ?? '');
$quantidadeInsignias = count($insignias);
$mestrePokemon = $quantidadeInsignias === 8;
$coresInsignias = [
    1 => ['#9da9b6', '#dce4ef'],
    2 => ['#397bbd', '#a8d7f0'],
    3 => ['#d3a91f', '#fff0a8'],
    4 => ['#39864a', '#b9e3aa'],
    5 => ['#7951a1', '#d7c2ef'],
    6 => ['#c45683', '#f4c2d5'],
    7 => ['#d25032', '#ffc08b'],
    8 => ['#876234', '#e3c88a'],
];
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
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/music_player.css">
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
            <!-- Mostra a imagem cadastrada ou um texto alternativo quando não há foto. -->
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

            <!-- As insígnias vêm do progresso persistido para este usuário. -->
            <section class="colecao-insignias" aria-labelledby="titulo-insignias">
                <?php if ($mestrePokemon): ?>
                    <h2 id="titulo-insignias" class="titulo-mestre">Mestre Pokémon</h2>
                    <p>Você conquistou todas as oito insígnias de Kanto!</p>
                <?php else: ?>
                    <h2 id="titulo-insignias">Insígnias conquistadas</h2>
                    <p class="progresso-insignias">
                        <?= $quantidadeInsignias ?> de 8 insígnias
                    </p>
                <?php endif; ?>

                <?php if ($quantidadeInsignias > 0): ?>
                    <ul class="lista-insignias">
                        <?php foreach ($insignias as $insignia): ?>
                            <?php
                            $nivelInsignia = (int) $insignia['nivel_jornada'];
                            $cores = $coresInsignias[$nivelInsignia] ?? ['#69768a', '#dce4ef'];
                            $nomeInsignia = (string) $insignia['nome_insignia'];
                            $nomeTreinador = (string) $insignia['nome_treinador'];
                            ?>
                            <li class="item-insignia">
                                <svg
                                    class="icone-insignia"
                                    viewBox="0 0 120 110"
                                    role="img"
                                    aria-label="<?= htmlspecialchars($nomeInsignia, ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <path d="M60 4 108 34 92 94 60 106 28 94 12 34z" fill="<?= $cores[0] ?>" stroke="#17243b" stroke-width="7" stroke-linejoin="round"/>
                                    <path d="m60 19 32 20-11 42-21 9-21-9-11-42z" fill="<?= $cores[1] ?>" stroke="#17243b" stroke-width="5" stroke-linejoin="round"/>
                                    <path d="m60 32 18 20-18 25-18-25z" fill="<?= $cores[0] ?>" stroke="#17243b" stroke-width="4" stroke-linejoin="round"/>
                                </svg>
                                <strong><?= htmlspecialchars($nomeInsignia, ENT_QUOTES, 'UTF-8') ?></strong>
                                <span><?= htmlspecialchars($nomeTreinador, ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="sem-insignias">Vença os líderes de ginásio para conquistar suas insígnias.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
    <a href="/projetoFinalPok-mon/public/index.php?rota=jogar" class="btn_pixel">
            <img src="/projetoFinalPok-mon/public/css/img/btn_voltar.png" alt="Voltar" style="width: 200px !important; height: auto;">
        </a>
    <?php require __DIR__ . '/../layouts/music_player.php'; ?>
</body>
</html>
