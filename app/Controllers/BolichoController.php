<?php

// Controller que futuramente controlará as ações de compra e itens da loja.
class BolichoController{
    // Guarda a conexão com o banco usada pela bolicho.
    private PDO $db;

    // Construtor recebe a conexão e armazena para uso posterior.
    public function __construct(PDO $db){
        $this->db = $db;
    }

    public function index(): void
    {
        if (!isset($_SESSION['usuario']['id_usuario'])) {
            header('Location: index.php?rota=login');
            exit;
        }

        require __DIR__ . '/../Views/bolicho/bolicho.php';
    }
}