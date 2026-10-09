<?php

require_once "../models/batalha.php";

class BatalhaController{
    private $db;

    public function __construct($db){
        $this->db = $db;
    }
}