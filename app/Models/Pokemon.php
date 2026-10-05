<?php

namespace App\Models;

class Pokemon
{
    private string $nome;
    private int $hp;
    private int $ataque;
    private int $defesa;
    private int $velocidade;
    private int $prioridade;

    public function __construct(
        string $nome,
        int $hp,
        int $ataque,
        int $defesa,
        int $velocidade,
        int $prioridade
    ) {
        $this->nome = $nome;
        $this->hp = $hp;
        $this->ataque = $ataque;
        $this->defesa = $defesa;
        $this->velocidade = $velocidade;
        $this->prioridade = $prioridade;
    }
}