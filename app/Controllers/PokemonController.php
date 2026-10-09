<?php

require_once "../models/pokemon.php";

class PokemonController{
    private $db;

    public function __construct($db){
        $this->db = $db;
    }

    //ação para listar todos os pokemons
    public function listarPokemons(){
        $model = new Pokemon($this->db);
        $pokemons = $model->buscarTodos();

        require_once "../views/listar_pokemons.php";
    }
}