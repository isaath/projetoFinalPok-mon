<?php

namespace App\Models;

/** Pokémon cuja classe herda e identifica o tipo Elétrico principal. */
class PokemonEletrico extends Pokemon
{
    public function getTipo(): string
    {
        return 'Eletrico';
    }
}
