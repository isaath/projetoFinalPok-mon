<?php

require_once __DIR__ . '/../app/Controllers/PokemonController.php';

$rotas = require __DIR__ . '/../routes/routes.php';
$rota = $_GET['rota'] ?? '';

if (!is_string($rota) || !array_key_exists($rota, $rotas)) {
    http_response_code(404);
    echo 'Página não encontrada.';
    exit;
}

[$controllerClass, $acao] = $rotas[$rota];
$controller = new $controllerClass();
$controller->$acao();