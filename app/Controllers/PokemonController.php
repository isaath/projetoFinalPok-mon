<?php

use App\Models\Pokemon;

class PokemonController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function listarPokemons(): void
    {
        $pokemons = [];
        $erro = null;

        try {
            $pokemons = Pokemon::listarTodos($this->db);
        } catch (PDOException $exception) {
            error_log('Erro ao listar Pokémon do banco de dados: ' . $exception->getMessage());
            http_response_code(500);
            $erro = 'Não foi possível carregar os Pokémon cadastrados agora.';
        }

        require __DIR__ . '/../Views/pokemon/listar.php';
    }
}
