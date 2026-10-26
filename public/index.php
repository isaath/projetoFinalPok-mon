<?php

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/PokemonController.php';
require_once __DIR__ . '/../app/Controllers/UsuarioController.php';

$rotas = require __DIR__ . '/../routes/routes.php';
$rota = $_GET['rota'] ?? '';

if (!is_string($rota) || !array_key_exists($rota, $rotas)) {
    http_response_code(404);
    echo 'Página não encontrada.';
    exit;
}

[$controllerClass, $acao] = $rotas[$rota];

if ($controllerClass === UsuarioController::class) {
    $controller = new UsuarioController(getConnection());
} else {
    $controller = new $controllerClass();
}

$controller->$acao();