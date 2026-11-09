<?php

// Controller que futuramente cuidará do fluxo de batalhas do jogo.
class BatalhaController{
    // A propriedade armazena a conexão com o banco de dados.
    private PDO $db;

    // O construtor recebe a conexão e salva em uma propriedade da classe.
    public function __construct(PDO $db){
        $this->db = $db;
    }
}