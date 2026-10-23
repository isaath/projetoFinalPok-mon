<?php

require_once __DIR__ . '/../Services/PokeApiService.php';

class PokemonController
{
    public function listarPokemons(): void
    {
        $pokemons = [];
        $erro = null;

        try {
            $servico = new PokeApiService();
            $pokemons = $servico->listarPokemons();
        } catch (RuntimeException | JsonException $exception) {
            error_log('Erro ao consultar a PokeAPI: ' . $exception->getMessage());
            $erro = 'Não foi possível carregar os Pokémon. Tente novamente mais tarde.';
        }

        require __DIR__ . '/../Views/pokemon/listar.php';
    }
}
