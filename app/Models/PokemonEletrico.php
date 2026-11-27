<?php

namespace App\Models;

/**
 * Subclasse do tipo Elétrico usada por PokemonTipoFactory.
 * getTipo() demonstra polimorfismo na listagem; BatalhaEngine calcula os tipos.
 */
class PokemonEletrico extends Pokemon
{
    public function getTipo(): string
    {
        return 'Eletrico';
    }
}
