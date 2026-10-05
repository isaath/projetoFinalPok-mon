<?php

//Planta - forte contra água e fraco contra fogo

namespace App\Traits;

trait PlantaTrait
{
    public function getTipo(): string
    {
        return "Planta";
    }

    public function multiplicadorContra(string $tipoDefensor): float
    {
        return match ($tipoDefensor) {
            "Água" => 2.0,
            "Fogo" => 0.5,
            default => 1.0
        };
    }
}