<?php

require_once "../models/loja.php";

class LojaController{
    private $db;

    public function __construct($db){
        $this->db = $db;
    }
}