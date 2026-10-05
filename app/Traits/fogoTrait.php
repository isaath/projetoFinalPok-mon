<?php

//Fogo - forte contra planta e fraco contra água  
namespace App\Traits;

trait FogoTrait
{
    public function getTipo(): string
    {
        return "Fogo";
    }

    public function multiplicadorContra(string $tipoDefensor): float
    {
        return match ($tipoDefensor) {
            "Planta" => 2.0,
            "Água" => 0.5,
            default => 1.0
        };
    }
}