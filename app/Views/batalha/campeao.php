<?php
// Na apresentação: BatalhaController carrega o perfil final e esta View mostra
// a conclusão da jornada; o formulário permite voltar ao painel inicial.
if (!defined('POKEBAGUAL_CAMPEAO_VIEW')) {
    http_response_code(404);
    exit;
}

// Prepara os dados do perfil e da última captura para exibir a conclusão da jornada.
$nomeUsuario = (string) ($perfil['nome'] ?? 'Treinador');
$fotoCaminho = $perfil['foto_caminho'] ?? null;
$fotoCaminhoValido = is_string($fotoCaminho)
    && preg_match(
        '~\Apublic/uploads/perfil/[a-f0-9]{32}\.(?:jpg|png)\z~',
        $fotoCaminho
    ) === 1;
$fotoSrc = $fotoCaminhoValido
    ? '/projetoFinalPok-mon/' . $fotoCaminho
    : null;
$fotoAntiga = $perfil['foto'] ?? null;
$tipoFotoAntiga = $perfil['tipo_foto'] ?? '';
if (
    $fotoSrc === null
    && is_string($fotoAntiga)
    && $fotoAntiga !== ''
    && in_array($tipoFotoAntiga, ['image/jpeg', 'image/png'], true)
) {
    $fotoSrc = 'data:' . $tipoFotoAntiga . ';base64,' . base64_encode($fotoAntiga);
}
$iniciais = function_exists('mb_substr')
    ? mb_substr($nomeUsuario, 0, 1, 'UTF-8')
    : strtoupper(substr($nomeUsuario, 0, 1));
$nomePokemonFinal = is_array($capturaFinal) && is_string($capturaFinal['nome'] ?? null)
    ? $capturaFinal['nome']
    : null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grande Mestre Pokémon | PokeBagual</title>
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/campeao.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/music_player.css">
</head>
<body class="champion-page">
    <main class="champion-shell">
        <p class="champion-eyebrow">JORNADA CONCLUÍDA</p>

        <section class="champion-intro">
            <div class="champion-avatar">
                <?php if ($fotoSrc !== null): ?>
                    <img
                        src="<?= htmlspecialchars($fotoSrc, ENT_QUOTES, 'UTF-8') ?>"
                        alt="Foto de <?= htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8') ?>"
                    >
                <?php else: ?>
                    <span aria-label="Foto de perfil não cadastrada">
                        <?= htmlspecialchars($iniciais, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endif; ?>
            </div>
            <h1>Parabéns, <?= htmlspecialchars($nomeUsuario, ENT_QUOTES, 'UTF-8') ?>!</h1>
            <p class="champion-title">Você se tornou um Grande Mestre Pokémon!</p>
            <p>Venceu todos os oito líderes de Kanto e conquistou todas as insígnias.</p>
        </section>

        <section class="champion-badges" aria-labelledby="titulo-insignias">
            <h2 id="titulo-insignias">Suas insígnias</h2>
            <!-- A lista é montada com o progresso carregado pelo controller. -->
            <ol>
                <?php foreach ($progressoNiveis as $nivel => $dadosNivel): ?>
                    <li>
                        <span class="badge-number"><?= (int) $nivel ?></span>
                        <strong>
                            <?= htmlspecialchars($dadosNivel['insignia'], ENT_QUOTES, 'UTF-8') ?>
                        </strong>
                        <small>
                            <?= htmlspecialchars($dadosNivel['nome'], ENT_QUOTES, 'UTF-8') ?>
                        </small>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>

        <?php if ($nomePokemonFinal !== null): ?>
            <p class="champion-capture-result" role="status">
                Resultado da última captura:
                <strong><?= htmlspecialchars($nomePokemonFinal, ENT_QUOTES, 'UTF-8') ?></strong>
                — <?= ($capturaFinal['resultado'] ?? '') === 'capturado'
                    ? 'capturado'
                    : ((($capturaFinal['resultado'] ?? '') === 'ja_possuia')
                        ? 'já fazia parte da sua equipe'
                        : 'escapou') ?>.
            </p>
        <?php endif; ?>

        <?php if ($erroCampeao !== null): ?>
            <p class="champion-error" role="alert">
                <?= htmlspecialchars($erroCampeao, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <div class="champion-actions">
            <form
                id="form-reiniciar-jornada"
                method="post"
                action="/projetoFinalPok-mon/public/index.php?rota=campeao"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($csrfReiniciarJornada, ENT_QUOTES, 'UTF-8') ?>"
                >
                <input type="hidden" name="acao" value="reiniciar_jornada">
                <button type="submit" class="champion-button champion-reset">
                    Reiniciar jornada
                </button>
            </form>
            <a
                class="champion-button champion-menu"
                href="/projetoFinalPok-mon/public/index.php?rota=jogar"
            >
                Menu
            </a>
        </div>

        <p class="champion-reset-note">
            Reiniciar apaga a equipe, capturas, insígnias, progresso e itens; o saldo volta a 120
            PokePila e você escolherá um novo time. Os históricos de batalhas e compras serão mantidos.
        </p>
    </main>

    <script>
        // Pede confirmação antes de enviar a ação que reinicia a jornada.
        document.getElementById('form-reiniciar-jornada').addEventListener('submit', function (evento) {
            // Impedir o envio conserva o progresso quando o usuário cancela a confirmação.
            if (!window.confirm('Isso apagará sua equipe, capturas, insígnias e itens. Deseja continuar?')) {
                evento.preventDefault();
            }
        });
    </script>
    <?php require __DIR__ . '/../layouts/music_player.php'; ?>
</body>
</html>
