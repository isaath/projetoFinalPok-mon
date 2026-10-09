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

    
    //MÉTODOS DE CRUD
    
    //método para salvar um pokemon no banco de dados
    public function salvar() {
        $conn = getConnection();
        $stmt = $conn->prepare("INSERT INTO pokemons (nome, hp, hp_maximo, forca_ataque, defesa, ataque_especial, defesa_especial, velocidade, experiencia) VALUES (:nome, :hp, :hp_maximo, :forca_ataque, :defesa, :ataque_especial, :defesa_especial, :velocidade, :experiencia)");
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":hp", $this->hp);
        $stmt->bindParam(":hp_maximo", $this->hp_maximo);
        $stmt->bindParam(":forca_ataque", $this->forca_ataque);
        $stmt->bindParam(":defesa", $this->defesa);
        $stmt->bindParam(":ataque_especial", $this->ataque_especial);
        $stmt->bindParam(":defesa_especial", $this->defesa_especial);
        $stmt->bindParam(":velocidade", $this->velocidade);
        $stmt->bindParam(":experiencia", $this->experiencia);
        return $stmt->execute();
    }


    // Método para buscar um funcionario pelo ID
    public static function buscarPorId(int $id_pokemon) {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM pokemons WHERE id_pokemon = :id_pokemon");
        $stmt->bindParam(":id_pokemon", $id_pokemon);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    // Método para listar todos os funcionarios
    public static function listarTodos() {
        $conn = getConnection();
        $stmt = $conn->query("SELECT * FROM funcionarios");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Método para atualizar um funcionario
    public function atualizar(int $id) {
        $conn = getConnection();
        $stmt = $conn->prepare("UPDATE funcionarios SET nome = :nome, cpf = :cpf, telefone = :telefone, curriculo = :curriculo WHERE id = :id");
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":cpf", $this->cpf);
        $stmt->bindParam(":telefone", $this->telefone);
        $stmt->bindParam(":curriculo", $this->curriculo);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }


    // Método para excluir um funcionario
    public static function excluir($id) {
        $conn = getConnection();
        $stmt = $conn->prepare("DELETE FROM funcionarios WHERE id = :id");
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
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