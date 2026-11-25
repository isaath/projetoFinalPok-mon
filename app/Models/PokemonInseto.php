<?php

namespace App\Models;

/** Pokémon cuja classe herda e identifica o tipo Inseto principal. */
class PokemonInseto extends Pokemon
{
    public function getTipo(): string
    {
        return 'Inseto';
    }
}
