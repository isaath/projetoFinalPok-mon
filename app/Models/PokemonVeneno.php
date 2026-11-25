<?php

namespace App\Models;

/** Pokémon cuja classe herda e identifica o tipo Veneno principal. */
class PokemonVeneno extends Pokemon
{
    public function getTipo(): string
    {
        return 'Veneno';
    }
}
