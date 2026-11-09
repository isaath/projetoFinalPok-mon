<?php

// Carrega o serviço que consulta a API externa dos Pokémon.
require_once __DIR__ . '/../Services/PokeApiService.php';

// Controller responsável por controlar a listagem de Pokémon da aplicação.
class PokemonController
{
    // Método que busca os Pokémon e prepara os dados para a View.
    public function listarPokemons(): void
    {
        // Variáveis que armazenam a lista de Pokémon e uma possível mensagem de erro.
        $pokemons = [];
        $erro = null;

        try {
            // Instancia o serviço da PokeAPI.
            $servico = new PokeApiService();

            // Solicita a listagem dos Pokémon.
            $pokemons = $servico->listarPokemons();
        } catch (RuntimeException | JsonException $exception) {
            // Em caso de falha, registra a mensagem no log do servidor.
            error_log('Erro ao consultar a PokeAPI: ' . $exception->getMessage());

            // Cria uma mensagem amigável para o usuário.
            $erro = 'Não foi possível carregar os Pokémon. Tente novamente mais tarde.';
        }

        // Carrega a View responsável por mostrar a tela de listagem.
        require __DIR__ . '/../Views/pokemon/listar.php';
    }
}
