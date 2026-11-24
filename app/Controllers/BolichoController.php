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

        $idUsuario = filter_var(
            $_SESSION['usuario']['id_usuario'],
            FILTER_VALIDATE_INT
        );
        $saldo = 0;
        if ($idUsuario !== false && $idUsuario >= 1) {
            $consulta = $this->db->prepare(
                'SELECT dinheiro FROM usuarios WHERE id_usuario = :id_usuario'
            );
            $consulta->execute(['id_usuario' => $idUsuario]);
            $saldo = (int) $consulta->fetchColumn();
        }

        require __DIR__ . '/../Views/bolicho/bolicho.php';
    }
}