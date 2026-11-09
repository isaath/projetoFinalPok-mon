<?php

namespace App\Models;

use App\Traits\aguaTrait;

// Classe específica para Pokémon do tipo Água.
// Ela herda as características gerais da classe Pokemon e incorpora a trait de Água.
class PokemonAgua extends Pokemon
{
    use aguaTrait;
}