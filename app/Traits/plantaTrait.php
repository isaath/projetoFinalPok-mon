<?php

// Trait que representa a lógica do tipo Planta no jogo.
// Planta é forte contra Água e fraco contra Fogo.
namespace App\Traits;

trait PlantaTrait
{
    // Retorna o nome do tipo para uso na lógica e na visualização.
    public function getTipo(): string
    {
        return "Planta";
    }

    // Calcula o fator de dano em combate conforme o tipo do inimigo.
    public function multiplicadorContra(string $tipoDefensor): float
    {
        return match ($tipoDefensor) {
            "Água" => 2.0,
            "Fogo" => 0.5,
            default => 1.0
        };
    }
}