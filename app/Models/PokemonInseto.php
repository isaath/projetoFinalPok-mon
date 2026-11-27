<?php

namespace App\Models;

/**
 * Subclasse do tipo Inseto usada por PokemonTipoFactory.
 * A sobrescrita de getTipo() permite que a listagem trate subclasses pela
 * mesma interface; o gráfico de efetividade está em BatalhaEngine.
 */
class PokemonInseto extends Pokemon
{
    public function getTipo(): string
    {
        return 'Inseto';
    }
}
