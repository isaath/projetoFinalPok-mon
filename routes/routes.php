<?php

// Este arquivo define as rotas da aplicação.
// Cada chave é o nome da rota acessada pela URL, e cada valor aponta para o
// Controller e para o método que deve responder à requisição.
return [
    // Rota principal: ao abrir a home, exibe a listagem de Pokémon.
    '' => [PokemonController::class, 'listarPokemons'],

    // Lista de Pokémon também pode ser acessada pela URL /pokemons.
    'pokemons' => [PokemonController::class, 'listarPokemons'],

    // Rota de autenticação do usuário.
    'login' => [UsuarioController::class, 'login'],

    // Rota para cadastro de usuário.
    'registrar' => [UsuarioController::class, 'registrar'],

    // Rota para o painel de jogo após o usuário entrar.
    'jogar' => [UsuarioController::class, 'painel_jogar'],

    'bolicho' => [BolichoController::class, 'index'],
];