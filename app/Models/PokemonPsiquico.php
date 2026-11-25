<?php

namespace App\Models;

/** Pokémon cuja classe herda e identifica o tipo Psíquico principal. */
class PokemonPsiquico extends Pokemon
{
    public function getTipo(): string
    {
        return 'Psiquico';
    }
}
