<?php

namespace App\Models;

/** Pokémon cuja classe herda e identifica o tipo Pedra principal. */
class PokemonPedra extends Pokemon
{
    public function getTipo(): string
    {
        return 'Pedra';
    }
}
