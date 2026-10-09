<?php

namespace App\Models;

//crud completo de pokemon.php

class Pokemon
{
    private string $nome;
    private int $id_pokemon;
    private int $hp;
    private int $hpMaximo;
    private int $forca_ataque;
    private int $defesa;
    private int $ataque_especial;
    private int $defesa_especial;
    private int $velocidade;
    private int $experiencia;


    //gets e sets

    public function getId(){
        return $this->id_pokemon;
    }
    public function setId(int $id){
        $this->id_pokemon = $id;
    }
    public function getNome(): string
    {
        return $this->nome;
    }
    public function setNome(string $nome): void
    {
        $this->nome = $nome;
    }
    public function getHp(): int
    {
        return $this->hp;
    }
    public function setHp(int $hp): void
    {
        $this->hp = $hp;
    }
    public function getHpMaximo(): int
    {
        return $this->hpMaximo;
    }
    public function setHpMaximo(int $hpMaximo): void
    {
        $this->hpMaximo = $hpMaximo;
    }
    public function getForcaAtaque(): int
    {
        return $this->forca_ataque;
    }
    public function setForcaAtaque(int $forca_ataque): void
    {
        $this->forca_ataque = $forca_ataque;
    }
    public function getDefesa(): int
    {
        return $this->defesa;
    }
    public function setDefesa(int $defesa): void
    {
        $this->defesa = $defesa;
    }
    public function getAtaqueEspecial(): int
    {
        return $this->ataque_especial;
    }
    public function setAtaqueEspecial(int $ataque_especial): void
    {
        $this->ataque_especial = $ataque_especial;
    }
    public function getDefesaEspecial(): int
    {
        return $this->defesa_especial;
    }
    public function setDefesaEspecial(int $defesa_especial): void
    {
        $this->defesa_especial = $defesa_especial;
    }
    public function getVelocidade(): int
    {
        return $this->velocidade;
    }
    public function setVelocidade(int $velocidade): void
    {
        $this->velocidade = $velocidade;
    }
    public function getExperiencia(): int
    {
        return $this->experiencia;
    }
    public function setExperiencia(int $experiencia): void
    {
        $this->experiencia = $experiencia;
    }


    //metodos de batalha do pokefish

    public function receberDano(int $dano): void{ //definindo um metodo publico sem retorno
        
        $this->hp -= $dano;  //subtrair o dano do hp atual do pokemon

        if ($this->hp < 0) {  //verificar se o hp ficou negativo
            $this->hp = 0; //se sim vai ajustar pra 0
            //this --> referencia o objeto atual, nesse caso o pokemon que recebeu o dano
        }
    }

    public function atacar(Pokemon $pokemonAlvo): void{ //definindo um metodo publico sem retorno
        $dano = $this->forca_ataque - $pokemonAlvo->getDefesa(); //calcular o dano subtraindo a defesa do alvo da força de ataque do atacante

        if ($dano < 0) { //verificar se o dano ficou negativo
            $dano = 0; //se sim vai ajustar pra 0
        }

        $pokemonAlvo->receberDano($dano); //chamar o metodo receberDano do pokemon alvo passando o dano calculado
    }

    public function curar(): void{ //definindo um metodo publico sem retorno
        $this->hp = $this->hpMaximo; //ajustar o hp atual do pokemon para o hp maximo
    }

    public function desmaiar(): bool{ //definindo um metodo publico com retorno booleano
        return $this->hp <= 0; //retornar true se o hp atual do pokemon for menor ou igual a 0, caso contrario retornar false
    }






}