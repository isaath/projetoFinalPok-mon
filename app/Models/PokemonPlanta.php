<?php

namespace App\Models;

use App\Traits\plantaTrait;

/**
 * Subclasse do tipo Planta usada por PokemonTipoFactory.
 * PlantaTrait fornece getTipo(); vantagens e fraquezas são calculadas em
 * BatalhaEngine, não nesta classe.
 */
class PokemonPlanta extends Pokemon
{
    use plantaTrait;
}