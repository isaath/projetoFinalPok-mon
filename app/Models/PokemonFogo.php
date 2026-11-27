<?php

namespace App\Models;

use App\Traits\fogoTrait;

/**
 * Subclasse do tipo Fogo usada por PokemonTipoFactory.
 * Na apresentação, diferencie herança de Pokemon e reutilização de getTipo()
 * pela trait; os multiplicadores de batalha são calculados em BatalhaEngine.
 */
class PokemonFogo extends Pokemon
{
    use fogoTrait;
}