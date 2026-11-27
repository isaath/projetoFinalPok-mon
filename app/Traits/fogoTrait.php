<?php

// Trait de identificação do tipo; a efetividade de golpes fica em BatalhaEngine.
namespace App\Traits;

trait FogoTrait
{
    // Informa o tipo principal do Pokémon.
    public function getTipo(): string
    {
        return "Fogo";
    }

}