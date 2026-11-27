<?php

// Configura o cookie da sessão antes de iniciá-la.
ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);

// Inicia a sessão do usuário para permitir armazenamento de dados como login e perfil.
session_start();

// Carrega a configuração da aplicação e os Controllers que serão usados nas rotas.
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../app/Models/autoload.php';
require __DIR__ . '/../app/Services/PokeApiService.php';
require __DIR__ . '/../app/Controllers/PokemonController.php';
require __DIR__ . '/../app/Controllers/UsuarioController.php';
require __DIR__ . '/../app/Controllers/LogoutController.php';
require __DIR__ . '/../app/Controllers/BolichoController.php';
require __DIR__ . '/../app/Controllers/CentroController.php';
require __DIR__ . '/../app/Controllers/TimeController.php';
require __DIR__ . '/../app/Controllers/BatalhaController.php';
require __DIR__ . '/../app/Controllers/MapaController.php';
require __DIR__ . '/../app/Controllers/PaginaController.php';

// Lê as rotas definidas em routes.php.
$rotas = require __DIR__ . '/../routes/routes.php';

// Pega o valor do parâmetro 'rota' da URL; se não existir, considera a rota raiz.
$rota = $_GET['rota'] ?? '';

// Valida se a rota recebida existe no array de rotas.
if (!is_string($rota) || !array_key_exists($rota, $rotas)) {
    // Se não existir, responde com erro HTTP 404.
    http_response_code(404);
    echo 'Página não encontrada.';
    exit;
}

// Extrai o Controller e o método que devem tratar a rota atual.
[$controllerClass, $acao] = $rotas[$rota];

// Controllers que acessam dados recebem a conexão PDO configurada pela aplicação.
if (
    $controllerClass === UsuarioController::class
    || $controllerClass === PokemonController::class
    || $controllerClass === BolichoController::class
    || $controllerClass === CentroController::class
    || $controllerClass === TimeController::class
    || $controllerClass === BatalhaController::class
    || $controllerClass === MapaController::class
) {
    $controller = new $controllerClass(getConnection());
} else {
    // Para os demais Controllers, instancia sem parâmetros extras.
    $controller = new $controllerClass();
}

// Executa a ação do Controller correspondente à rota solicitada.
$controller->$acao();