<?php

// Carrega automaticamente classes que pertencem ao namespace App.
spl_autoload_register(static function (string $classe): void {
    $prefixo = 'App\\';

    // Ignora classes que não fazem parte do namespace da aplicação.
    if (strncmp($classe, $prefixo, strlen($prefixo)) !== 0) {
        return;
    }

    // Converte o restante do namespace para o caminho dentro da pasta app.
    $caminhoRelativo = substr($classe, strlen($prefixo));
    $arquivo = dirname(__DIR__)
        . DIRECTORY_SEPARATOR
        . str_replace('\\', DIRECTORY_SEPARATOR, $caminhoRelativo)
        . '.php';

    // Inclui o arquivo apenas quando ele existir.
    if (is_file($arquivo)) {
        require_once $arquivo;
    }
});