<?php


return [
    '' => [PokemonController::class, 'listarPokemons'],
    'pokemons' => [PokemonController::class, 'listarPokemons'],
    'login' => [UsuarioController::class, 'login'],
    'registrar' => [UsuarioController::class, 'registrar'],
    'jogar' => [UsuarioController::class, 'painel_jogar'],
];