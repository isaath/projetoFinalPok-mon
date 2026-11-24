<?php
if (!defined('POKEBAGUAL_BATALHA_VIEW')) {
    http_response_code(404);
    exit;
}

$dadosBatalha = $dadosBatalha ?? null;
$treinador = is_array($dadosBatalha['treinador'] ?? null)
    ? $dadosBatalha['treinador']
    : [];
$equipeJogador = is_array($dadosBatalha['jogador']['equipe'] ?? null)
    ? $dadosBatalha['jogador']['equipe']
    : [];
$equipeAdversario = is_array($dadosBatalha['adversario']['equipe'] ?? null)
    ? $dadosBatalha['adversario']['equipe']
    : [];

$nomeTreinador = $treinador['nome'] ?? 'Treinador';
$insignia = $treinador['nome_insignia'] ?? 'Insignia';
$especialidade = $treinador['especialidade'] ?? 'Tipo';
$nivel = (int) ($dadosBatalha['nivel'] ?? $nivel ?? 1);
$finalizada = !empty($dadosBatalha['finalizada']);
$vitoria = ($dadosBatalha['resultado'] ?? null) === 'vitoria';
$mensagens = is_array($dadosBatalha['mensagens'] ?? null)
    ? $dadosBatalha['mensagens']
    : [];

$pokemonJogador = $pokemonJogador ?? null;
$pokemonAdversario = $pokemonAdversario ?? null;
$hpJogador = (int) ($pokemonJogador['hp'] ?? 0);
$hpMaximoJogador = (int) ($pokemonJogador['hp_maximo'] ?? 0);
$hpAdversario = (int) ($pokemonAdversario['hp'] ?? 0);
$hpMaximoAdversario = (int) ($pokemonAdversario['hp_maximo'] ?? 0);
$percentualHpJogador = $hpMaximoJogador > 0
    ? max(0, min(100, (int) round($hpJogador / $hpMaximoJogador * 100)))
    : 0;
$percentualHpAdversario = $hpMaximoAdversario > 0
    ? max(0, min(100, (int) round($hpAdversario / $hpMaximoAdversario * 100)))
    : 0;

$iniciaisTreinador = strtoupper(substr($nomeTreinador, 0, 2));
$arquivoImagemTreinador = strtolower(trim(
    (string) preg_replace('/[^a-z0-9]+/i', '-', $nomeTreinador),
    '-'
));
$imagemTreinadorDisponivel = is_file(
    __DIR__ . '/../../../public/css/img/treinadores/' . $arquivoImagemTreinador . '.png'
);

$nomeStatus = [
    'queimadura' => 'Queimado',
    'veneno' => 'Envenenado',
    'toxico' => 'Envenenamento grave',
    'paralisia' => 'Paralisado',
    'sono' => 'Dormindo',
    'congelamento' => 'Congelado',
    'confusao' => 'Confuso',
];
$rotuloStatus = static function (?string $status) use ($nomeStatus): string {
    return $status === null ? 'Sem condicao' : ($nomeStatus[$status] ?? $status);
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batalha - PokeBagual</title>
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/pixel_theme.css">
    <link rel="stylesheet" href="/projetoFinalPok-mon/public/css/batalha.css?v=ataques-tipo-2">
</head>
<body class="battle-page">
    <div class="textos_cabecalho">
        <p class="eyebrow">JORNADA KANTO · NÍVEL <?= $nivel ?> DE 8</p>
        <h1>Ginásio <?= htmlspecialchars($especialidade, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="battle-lead">
            Batalha contra <?= htmlspecialchars($nomeTreinador, ENT_QUOTES, 'UTF-8') ?>
            · <?= htmlspecialchars($insignia, ENT_QUOTES, 'UTF-8') ?>
        </p>
    </div>

    <div class="battle-score" aria-label="Resumo da batalha">
        <span>SEU TIME <strong><?= count(array_filter(
            $equipeJogador,
            static fn (array $pokemon): bool => (int) ($pokemon['hp'] ?? 0) > 0
        )) ?> Pokémon ativo(s)</strong></span>
        <span class="score-divider" aria-hidden="true">VS</span>
        <span>GINÁSIO <strong><?= count($equipeAdversario) ?> Pokémon</strong></span>
    </div>

    <div class="lado-a-lado">
        <div class="info_direita">
            <aside class="trainer-card" aria-labelledby="trainer-title">
                <p class="eyebrow">LÍDER DO GINÁSIO</p>
                <div class="trainer-avatar">
                    <?php if ($imagemTreinadorDisponivel): ?>
                        <img
                            class="trainer-image"
                            src="/projetoFinalPok-mon/public/css/img/treinadores/<?= rawurlencode($arquivoImagemTreinador) ?>.png"
                            alt="Retrato de <?= htmlspecialchars($nomeTreinador, ENT_QUOTES, 'UTF-8') ?>"
                        >
                    <?php else: ?>
                        <span aria-hidden="true">
                            <?= htmlspecialchars($iniciaisTreinador, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h2 id="trainer-title"><?= htmlspecialchars($nomeTreinador, ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="trainer-specialty">
                    Especialidade: <?= htmlspecialchars($especialidade, ENT_QUOTES, 'UTF-8') ?>
                </p>
                <div class="badge-preview">
                    <span class="badge-stone" aria-hidden="true"></span>
                    <span><?= htmlspecialchars($insignia, ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <h3>Equipe do lider</h3>
                <ol class="trainer-team">
                    <?php foreach ($equipeAdversario as $indice => $pokemon): ?>
                        <li>
                            <span><?= $indice + 1 ?></span>
                            <?= htmlspecialchars($pokemon['nome'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if ((int) $pokemon['hp'] <= 0): ?>
                                <small>Desmaiado</small>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </aside>
        </div>

        <div class="info_meio">
            <main class="battle-shell">
                <?php if ($erroBatalha !== null): ?>
                    <p class="battle-message battle-message-error" role="alert">
                        <?= htmlspecialchars($erroBatalha, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>

                <?php if ($erroImagem !== null): ?>
                    <p class="battle-message" role="status">
                        <?= htmlspecialchars($erroImagem, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>

                <?php if ($dadosBatalha !== null): ?>
                    <article class="combatant combatant-player">
                        <div class="combatant-portrait" id="pokemon-jogador">
                            <?php if (is_string($imagemJogador)): ?>
                                <img
                                    class="combatant-image"
                                    src="<?= htmlspecialchars($imagemJogador, ENT_QUOTES, 'UTF-8') ?>"
                                    alt="Sprite de <?= htmlspecialchars($pokemonJogador['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            <?php else: ?>
                                <div class="combatant-symbol" aria-hidden="true">?</div>
                            <?php endif; ?>
                        </div>
                        <div class="combatant-copy">
                            <p class="combatant-label">SEU POKÉMON ATIVO · POSIÇÃO <?= (int) ($dadosBatalha['jogador']['ativo'] ?? 0) + 1 ?></p>
                            <h2>
                                <?= $pokemonJogador !== null
                                    ? htmlspecialchars($pokemonJogador['nome'], ENT_QUOTES, 'UTF-8')
                                    : 'Sem Pokémon ativo' ?>
                            </h2>
                            <?php if ($pokemonJogador !== null): ?>
                                <p><?= htmlspecialchars(implode(' / ', $pokemonJogador['tipos']), ENT_QUOTES, 'UTF-8') ?></p>
                                <div class="hp-track" aria-label="HP do seu Pokemon">
                                    <span style="width: <?= $percentualHpJogador ?>%;"></span>
                                </div>
                                <small>
                                HP <?= $hpJogador ?> / <?= $hpMaximoJogador ?>
                                · <?= htmlspecialchars($rotuloStatus($pokemonJogador['status']), ENT_QUOTES, 'UTF-8') ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </article>

                    <article class="combatant combatant-opponent">
                        <div class="combatant-portrait" id="pokemon-adversario">
                            <?php if (is_string($imagemAdversario)): ?>
                                <img
                                    class="combatant-image"
                                    src="<?= htmlspecialchars($imagemAdversario, ENT_QUOTES, 'UTF-8') ?>"
                                    alt="Sprite de <?= htmlspecialchars($pokemonAdversario['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            <?php else: ?>
                                <div class="combatant-symbol" aria-hidden="true">?</div>
                            <?php endif; ?>
                        </div>
                        <div class="combatant-copy">
                            <p class="combatant-label">POKÉMON DO LÍDER · POSIÇÃO <?= (int) ($dadosBatalha['adversario']['ativo'] ?? 0) + 1 ?></p>
                            <h2>
                                <?= $pokemonAdversario !== null
                                    ? htmlspecialchars($pokemonAdversario['nome'], ENT_QUOTES, 'UTF-8')
                                    : 'Equipe adversaria derrotada' ?>
                            </h2>
                            <?php if ($pokemonAdversario !== null): ?>
                                <p><?= htmlspecialchars(implode(' / ', $pokemonAdversario['tipos']), ENT_QUOTES, 'UTF-8') ?></p>
                                <div class="hp-track" aria-label="HP do Pokemon adversario">
                                    <span style="width: <?= $percentualHpAdversario ?>%;"></span>
                                </div>
                                <small>
                                    HP <?= $hpAdversario ?> / <?= $hpMaximoAdversario ?>
                                    · <?= htmlspecialchars($rotuloStatus($pokemonAdversario['status']), ENT_QUOTES, 'UTF-8') ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endif; ?>

                <section class="battle-log" aria-labelledby="battle-log-title" aria-live="polite">
                    <h2 id="battle-log-title">Registro da batalha</h2>
                    <?php if ($mensagens !== []): ?>
                        <ol>
                            <?php foreach ($mensagens as $mensagem): ?>
                                <li><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ol>
                    <?php else: ?>
                        <p>A batalha comecara quando voce escolher um movimento.</p>
                    <?php endif; ?>
                </section>

                <?php if (!$finalizada && $pokemonJogador !== null): ?>
                    <form class="battle-actions" method="post" action="">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($csrfBatalha, ENT_QUOTES, 'UTF-8') ?>"
                        >
                        <div class="battle-moves">
                            <button type="button" id="abrir-ataques" class="btn-atacar-principal" aria-expanded="false" aria-controls="opcoes-ataques">
                                <img
                                    src="/projetoFinalPok-mon/public/css/img/btn_atacar.png"
                                    alt="Atacar"
                                >
                            </button>

                            <div class="opcoes-ataques" id="opcoes-ataques" hidden>
                                <?php foreach ($pokemonJogador['movimentos'] as $movimento): ?>
                                    <?php
                                    $tipoAtaque = strtolower(
                                        (string) ($movimento['tipo'] ?? 'normal')
                                    );
                                    $tipoClasse = preg_replace(
                                        '/[^a-z0-9_-]/',
                                        '-',
                                        $tipoAtaque
                                    );
                                    ?>
                                    <button
                                        type="submit"
                                        name="id_movimento"
                                        value="<?= (int) $movimento['id_movimento'] ?>"
                                        class="opcao-ataque tipo-<?= htmlspecialchars($tipoClasse, ENT_QUOTES, 'UTF-8') ?>"
                                        title="<?= htmlspecialchars($movimento['nome'] . ' · ' . $movimento['tipo'], ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        <strong><?= htmlspecialchars($movimento['nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <small><?= htmlspecialchars($movimento['tipo'], ENT_QUOTES, 'UTF-8') ?></small>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="battle-options">
                            <?php
                            $pokemonsReservaTroca = [];
                            foreach ($equipeJogador as $indice => $pokemonReserva) {
                                if (
                                    $indice !== (int) ($dadosBatalha['jogador']['ativo'] ?? -1)
                                    && (int) ($pokemonReserva['hp'] ?? 0) > 0
                                ) {
                                    $pokemonsReservaTroca[$indice] = $pokemonReserva;
                                }
                            }
                            ?>
                            <?php if ($pokemonsReservaTroca !== []): ?>
                            <div class="battle-option-control">
                                <select name="indice_troca" aria-label="Pokémon para trocar">
                                    <?php foreach ($pokemonsReservaTroca as $indice => $pokemonReserva): ?>
                                        <option value="<?= $indice ?>">
                                            <?= htmlspecialchars($pokemonReserva['nome'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="acao" value="trocar_pokemon">
                                <img src="/projetoFinalPok-mon/public/css/img/btn_trocar_pokemon.png" alt="Trocar Pokémon">
                                </button>
                            </div>
                            <?php endif; ?>

                            <?php if ($inventario !== []): ?>
                            <div class="battle-option-control">
                                <select name="id_item" aria-label="Item para usar">
                                    <?php foreach ($inventario as $item): ?>
                                        <option value="<?= (int) $item['id_item'] ?>">
                                            <?= htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8') ?>
                                            (<?= (int) $item['quantidade'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="acao" value="usar_item">
                                <img src="/projetoFinalPok-mon/public/css/img/btn_usar_item.png" alt="Usar item">
                                </button>
                            </div>
                            <?php endif; ?>

                            <button type="submit" name="acao" value="fugir">
                                <img src="/projetoFinalPok-mon/public/css/img/btn_fugir.png" alt="Fugir">
                            </button>
                        </div>
                    </form>
                <?php elseif ($finalizada): ?>
                    <section class="battle-finish" aria-live="polite">
                        <?php if ($vitoria): ?>
                            <h2>Voce venceu!</h2>
                            <p>Recompensa: <strong><?= (int) $dadosBatalha['recompensa'] ?> Pokedollars</strong></p>
                            <a class="battle-submit" href="/projetoFinalPok-mon/public/index.php?rota=captura">Ver captura</a>
                        <?php else: ?>
                            <h2>Voce perdeu a batalha</h2>
                            <p>Visite o Pokecenter e tente novamente.</p>
                            <form method="post" action="">
                                <input type="hidden" name="csrf_token"
                                    value="<?= htmlspecialchars($csrfBatalha, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="acao" value="reiniciar">
                                <button class="battle-submit" type="submit">Reiniciar batalha</button>
                            </form>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <a href="/projetoFinalPok-mon/public/index.php?rota=mapa" class="btn_pixel">
        <img src="/projetoFinalPok-mon/public/css/img/btn_voltar.png" alt="Voltar"
             style="width: 200px !important; height: auto;">
    </a>
    <script>
        const botaoAtacar = document.getElementById('abrir-ataques');
        const opcoesAtaques = document.getElementById('opcoes-ataques');

        if (botaoAtacar && opcoesAtaques) {
            botaoAtacar.addEventListener('click', function () {
                const aberto = !opcoesAtaques.hidden;
                opcoesAtaques.hidden = aberto;
                botaoAtacar.setAttribute('aria-expanded', aberto ? 'false' : 'true');
            });
        }
    </script>
</body>
</html>
