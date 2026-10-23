<?php

require_once "../models/usuario.php";

class UsuarioController{
    private $db;

    public function __construct($db){
        $this->db = $db;
    }
}