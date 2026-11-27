<?php

namespace App\Models;

/**
 * Subclasse do tipo Psíquico usada por PokemonTipoFactory.
 * A sobrescrita de getTipo() demonstra polimorfismo no catálogo, não no combate.
 */
class PokemonPsiquico extends Pokemon
{
    public function getTipo(): string
    {
        return 'Psiquico';
    }
}
