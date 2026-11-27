<?php

namespace App\Models;

/**
 * Subclasse do tipo Terra usada por PokemonTipoFactory.
 * getTipo() identifica o tipo principal; o cálculo de dano usa BatalhaEngine.
 */
class PokemonTerra extends Pokemon
{
    public function getTipo(): string
    {
        return 'Terra';
    }
}
