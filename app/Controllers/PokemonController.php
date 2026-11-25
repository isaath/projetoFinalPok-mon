<?php

use App\Models\Pokemon;

/** Coordena a consulta dos Pokémon cadastrados e a exibição da listagem. */
class PokemonController
{
    // O Model usa esta conexão para consultar os dados persistidos.
    private PDO $db;

    /** Recebe a conexão PDO fornecida pelo front controller. */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Carrega os Pokémon para a View e apresenta uma mensagem se a consulta falhar. */
    public function listarPokemons(): void
    {
        $pokemons = [];
        $erro = null;

        try {
            $pokemons = Pokemon::listarTodos($this->db);
        } catch (PDOException | UnexpectedValueException $exception) {
            // Registra detalhes técnicos no servidor e evita expor SQL ao usuário.
            error_log('Erro ao listar Pokémon do banco de dados: ' . $exception->getMessage());
            http_response_code(500);
            $erro = 'Não foi possível carregar os Pokémon cadastrados agora.';
        }

        // A View apenas apresenta os dados e o possível aviso preparados aqui.
        require __DIR__ . '/../Views/pokemon/listar.php';
    }
}
