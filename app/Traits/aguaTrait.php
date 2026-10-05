<?php

//Água - forte contra fogo e fraco contra planta

namespace App\Traits;

trait AguaTrait
{
    public function getTipo(): string
    {
        return "Água";
    }

    public function multiplicadorContra(string $tipoDefensor): float
    {
        return match ($tipoDefensor) {
            "Fogo" => 2.0,
            "Planta" => 0.5,
            default => 1.0
        };
    }
}