<?php

// Trait que representa a lógica do tipo Água no jogo.
// Água é forte contra Fogo e fraco contra Planta.
namespace App\Traits;

trait AguaTrait
{
    // Retorna o nome do tipo para exibição no sistema.
    public function getTipo(): string
    {
        return "Água";
    }

    // Calcula o multiplicador de dano com base no tipo do oponente.
    public function multiplicadorContra(string $tipoDefensor): float
    {
        return match ($tipoDefensor) {
            "Fogo" => 2.0,
            "Planta" => 0.5,
            default => 1.0
        };
    }
}