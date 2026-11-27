<?php

namespace App\Models;

use App\Traits\aguaTrait;

/**
 * Subclasse do tipo Água usada por PokemonTipoFactory.
 * Na apresentação, explique que AguaTrait sobrescreve getTipo(); a efetividade
 * dos golpes não fica aqui, mas centralizada em BatalhaEngine.
 */
class PokemonAgua extends Pokemon
{
    use aguaTrait;
}