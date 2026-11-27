<?php

// Teste manual simples da fórmula de dano: executa casos conhecidos e lança
// RuntimeException quando o resultado diverge. Rode com: php tests/BatalhaEngineTest.php
use App\Services\BatalhaEngine;

require __DIR__ . '/../app/Services/BatalhaEngine.php';

function verificarDano(int $esperado, int $obtido, string $caso): void
{
    if ($esperado !== $obtido) {
        throw new RuntimeException(
            $caso . ': esperado ' . $esperado . ', recebido ' . $obtido . '.'
        );
    }

    echo 'OK: ' . $caso . PHP_EOL;
}

$atacante = [
    'tipos' => ['normal'],
    'forca_ataque' => 1,
    'estagios' => ['ataque' => 0],
    'status' => null,
];
$alvo = [
    'tipos' => ['normal'],
    'defesa' => 100,
    'estagios' => ['defesa' => 0],
    'hp_maximo' => 100,
];
$movimento = [
    'tipo' => 'normal',
    'categoria' => 'fisico',
    'potencia' => 1,
];

// Um golpe fraco ainda causa 1 de dano quando não há imunidade.
verificarDano(1, BatalhaEngine::calcularDano($atacante, $alvo, $movimento), 'dano mínimo');

// Mesmo um ataque muito forte não pode retirar mais da metade do HP máximo.
$atacanteForte = $atacante;
$atacanteForte['forca_ataque'] = 1000;
$alvoResistente = $alvo;
$alvoResistente['defesa'] = 1;
$movimentoForte = $movimento;
$movimentoForte['potencia'] = 200;
verificarDano(
    50,
    BatalhaEngine::calcularDano($atacanteForte, $alvoResistente, $movimentoForte),
    'limite de metade do HP máximo'
);

// A imunidade é verificada antes do dano mínimo e continua causando zero.
$alvoImune = $alvo;
$alvoImune['tipos'] = ['fantasma'];
verificarDano(
    0,
    BatalhaEngine::calcularDano($atacanteForte, $alvoImune, $movimentoForte),
    'imunidade'
);
