<?php

// Trait que representa a lógica do tipo Fogo no jogo.
// Fogo é forte contra Planta e fraco contra Água.
namespace App\Traits;

trait FogoTrait
{
    // Retorna o nome do tipo para uso na interface e regras de batalha.
    public function getTipo(): string
    {
        return "Fogo";
    }

    // Define qual será o multiplicador de dano contra outro tipo.
    public function multiplicadorContra(string $tipoDefensor): float
    {
        return match ($tipoDefensor) {
            "Planta" => 2.0,
            "Água" => 0.5,
            default => 1.0
        };
    }
}