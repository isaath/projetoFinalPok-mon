<?php

namespace App\Models;

/**
 * Subclasse do tipo Pedra usada por PokemonTipoFactory.
 * A sobrescrita de getTipo() identifica o tipo principal; a batalha consulta
 * os tipos do estado e calcula efetividade em BatalhaEngine.
 */
class PokemonPedra extends Pokemon
{
    public function getTipo(): string
    {
        return 'Pedra';
    }
}
