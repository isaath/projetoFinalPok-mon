<?php

require_once __DIR__ . '/config/config.php';

try {
    $db = getConnection();
    echo "Conexão com o banco realizada com sucesso!";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}