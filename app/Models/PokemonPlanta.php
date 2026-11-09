<?php

namespace App\Models;

use App\Traits\plantaTrait;

// Classe específica para Pokémon do tipo Planta.
// Ela herda os atributos gerais do Pokemon e usa a trait de Planta para definir vantagens e fraquezas.
class PokemonPlanta extends Pokemon
{
    use plantaTrait;
}