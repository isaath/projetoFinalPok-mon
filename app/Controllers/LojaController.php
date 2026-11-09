<?php

// Controller que futuramente controlará as ações de compra e itens da loja.
class LojaController{
    // Guarda a conexão com o banco usada pela loja.
    private PDO $db;

    // Construtor recebe a conexão e armazena para uso posterior.
    public function __construct(PDO $db){
        $this->db = $db;
    }
}