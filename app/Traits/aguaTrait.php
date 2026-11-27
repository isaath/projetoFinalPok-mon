<?php

// Trait de identificação do tipo; a efetividade de golpes fica em BatalhaEngine.
namespace App\Traits;

trait AguaTrait
{
    // Informa o tipo principal do Pokémon.
    public function getTipo(): string
    {
        return "Água";
    }

}