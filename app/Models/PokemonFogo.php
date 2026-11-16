<?php

namespace App\Models;

use App\Traits\fogoTrait;

// Classe específica para Pokémon do tipo Fogo.
// Ela reutiliza a lógica base da classe Pokemon e adiciona os comportamentos do tipo Fogo.
class PokemonFogo extends Pokemon
{
    use fogoTrait;
}