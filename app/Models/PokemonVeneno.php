<?php

namespace App\Models;

/**
 * Subclasse do tipo Veneno usada por PokemonTipoFactory.
 * getTipo() identifica o tipo principal; efeitos de veneno são resolvidos
 * pela BatalhaEngine, que não depende desta subclasse.
 */
class PokemonVeneno extends Pokemon
{
    public function getTipo(): string
    {
        return 'Veneno';
    }
}
