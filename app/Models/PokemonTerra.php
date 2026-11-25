<?php

namespace App\Models;

/** Pokémon cuja classe herda e identifica o tipo Terra principal. */
class PokemonTerra extends Pokemon
{
    public function getTipo(): string
    {
        return 'Terra';
    }
}
