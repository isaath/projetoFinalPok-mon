<?php

namespace App\Models;

use PDO;

/**
 * Representa um Pokémon e reúne seus dados, operações no banco e ações de batalha.
 */
class Pokemon
{
    // Informações privadas: são acessadas ou alteradas pelos métodos da classe.
    private string $nome = '';
    private int $id_pokemon = 0;
    private int $hp = 0;
    private int $hp_maximo = 0;
    private int $forca_ataque = 0;
    private int $defesa = 0;
    private int $ataque_especial = 0;
    private int $defesa_especial = 0;
    private int $velocidade = 0;
    private int $experiencia = 0;

    // Retorna o identificador do Pokémon.
    public function getId(): int
    {
        return $this->id_pokemon;
    }

    // Define o identificador do Pokémon.
    public function setId(int $id): void
    {
        $this->id_pokemon = $id;
    }

    // Retorna o nome do Pokémon.
    public function getNome(): string
    {
        return $this->nome;
    }

    // Atualiza o nome do Pokémon.
    public function setNome(string $nome): void
    {
        $this->nome = $nome;
    }

    // Retorna os pontos de vida atuais.
    public function getHp(): int
    {
        return $this->hp;
    }

    // Define o HP atual; max() impede que ele fique negativo.
    public function setHp(int $hp): void
    {
        $this->hp = max(0, $hp);
    }

    // Retorna o limite máximo de pontos de vida.
    public function getHpMaximo(): int
    {
        return $this->hp_maximo;
    }

    // Define o HP máximo, sem permitir valor negativo.
    public function setHpMaximo(int $hp_maximo): void
    {
        $this->hp_maximo = max(0, $hp_maximo);
    }

    // Retorna o valor de ataque físico.
    public function getForcaAtaque(): int
    {
        return $this->forca_ataque;
    }

    // Define a força de ataque físico.
    public function setForcaAtaque(int $forca_ataque): void
    {
        $this->forca_ataque = $forca_ataque;
    }

    // Retorna o valor de defesa física.
    public function getDefesa(): int
    {
        return $this->defesa;
    }

    // Define a defesa física.
    public function setDefesa(int $defesa): void
    {
        $this->defesa = $defesa;
    }

    // Retorna o valor de ataque especial.
    public function getAtaqueEspecial(): int
    {
        return $this->ataque_especial;
    }

    // Define o ataque especial.
    public function setAtaqueEspecial(int $ataque_especial): void
    {
        $this->ataque_especial = $ataque_especial;
    }

    // Retorna o valor de defesa especial.
    public function getDefesaEspecial(): int
    {
        return $this->defesa_especial;
    }

    // Define a defesa especial.
    public function setDefesaEspecial(int $defesa_especial): void
    {
        $this->defesa_especial = $defesa_especial;
    }

    // Retorna a velocidade usada para comparar a ordem de ações.
    public function getVelocidade(): int
    {
        return $this->velocidade;
    }

    // Define a velocidade do Pokémon.
    public function setVelocidade(int $velocidade): void
    {
        $this->velocidade = $velocidade;
    }

    // Retorna os pontos de experiência acumulados.
    public function getExperiencia(): int
    {
        return $this->experiencia;
    }

    // Define os pontos de experiência.
    public function setExperiencia(int $experiencia): void
    {
        $this->experiencia = $experiencia;
    }

    // Insere no banco um registro com os atributos atuais deste objeto.
    public function salvar(): bool
    {
        // Obtém a conexão compartilhada configurada pela aplicação.
        $conn = \getConnection();
        // Prepara a instrução para manter os valores separados do comando SQL.
        $stmt = $conn->prepare(
            'INSERT INTO pokemons
                (nome, hp, hp_maximo, forca_ataque, defesa, ataque_especial, defesa_especial, velocidade, experiencia)
             VALUES
                (:nome, :hp, :hp_maximo, :forca_ataque, :defesa, :ataque_especial, :defesa_especial, :velocidade, :experiencia)'
        );
        // Associa cada coluna ao atributo correspondente e executa a inserção.
        return $stmt->execute([
            'nome' => $this->nome,
            'hp' => $this->hp,
            'hp_maximo' => $this->hp_maximo,
            'forca_ataque' => $this->forca_ataque,
            'defesa' => $this->defesa,
            'ataque_especial' => $this->ataque_especial,
            'defesa_especial' => $this->defesa_especial,
            'velocidade' => $this->velocidade,
            'experiencia' => $this->experiencia,
        ]);
    }

    // Procura um registro pelo ID; retorna null se não existir.
    public static function buscarPorId(int $id_pokemon): ?array
    {
        $conn = \getConnection();
        // Consulta preparada: o ID é enviado como parâmetro, não concatenado no SQL.
        $stmt = $conn->prepare('SELECT * FROM pokemons WHERE id_pokemon = :id_pokemon');
        $stmt->execute(['id_pokemon' => $id_pokemon]);
        $pokemon = $stmt->fetch(PDO::FETCH_ASSOC);

        // PDO retorna false quando não encontra linha; convertemos para null.
        return $pokemon === false ? null : $pokemon;
    }

    /**
     * Retorna as espécies cadastradas e seus tipos em arrays associativos.
     *
     * Pode receber uma conexão PDO existente; sem ela, usa a conexão padrão da aplicação.
     */
    public static function listarTodos(?PDO $db = null): array
    {
        $conn = $db ?? \getConnection();
        // Agrega os tipos relacionados e mantém o tipo principal como alternativa compatível.
        $stmt = $conn->query(
            'SELECT pokemon.id_pokemon,
                    pokemon.nome,
                    pokemon.hp,
                    pokemon.hp_maximo,
                    pokemon.forca_ataque,
                    pokemon.defesa,
                    pokemon.ataque_especial,
                    pokemon.defesa_especial,
                    pokemon.velocidade,
                    COALESCE(
                        (
                            SELECT GROUP_CONCAT(tipo.nome ORDER BY relacao.ordem SEPARATOR \'|\')
                            FROM pokemon_tipos AS relacao
                            INNER JOIN tipos AS tipo ON tipo.id_tipo = relacao.id_tipo
                            WHERE relacao.id_pokemon = pokemon.id_pokemon
                        ),
                        tipo_principal.nome
                    ) AS tipos
             FROM pokemons AS pokemon
             LEFT JOIN tipos AS tipo_principal
                ON tipo_principal.id_tipo = pokemon.id_tipo
             ORDER BY pokemon.nome'
        );

        $pokemons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // A consulta entrega tipos como texto separado por |; a View recebe uma lista.
        foreach ($pokemons as $indice => $pokemon) {
            $pokemons[$indice]['tipos'] = is_string($pokemon['tipos'])
                && $pokemon['tipos'] !== ''
                ? explode('|', $pokemon['tipos'])
                : [];
        }

        return $pokemons;
    }

    // Atualiza no banco os campos do objeto identificado pelo ID recebido.
    public function atualizar(int $id): bool
    {
        $conn = \getConnection();
        // A consulta altera os atributos, mas localiza a linha pela chave primária.
        $stmt = $conn->prepare(
            'UPDATE pokemons
             SET nome = :nome,
                 hp = :hp,
                 hp_maximo = :hp_maximo,
                 forca_ataque = :forca_ataque,
                 defesa = :defesa,
                 ataque_especial = :ataque_especial,
                 defesa_especial = :defesa_especial,
                 velocidade = :velocidade,
                 experiencia = :experiencia
             WHERE id_pokemon = :id_pokemon'
        );
        // Envia os valores e o ID à instrução preparada e retorna o resultado.
        return $stmt->execute([
            'nome' => $this->nome,
            'hp' => $this->hp,
            'hp_maximo' => $this->hp_maximo,
            'forca_ataque' => $this->forca_ataque,
            'defesa' => $this->defesa,
            'ataque_especial' => $this->ataque_especial,
            'defesa_especial' => $this->defesa_especial,
            'velocidade' => $this->velocidade,
            'experiencia' => $this->experiencia,
            'id_pokemon' => $id,
        ]);
    }

    // Exclui do banco o Pokémon identificado pelo ID.
    public static function excluir(int $id_pokemon): bool
    {
        $conn = \getConnection();
        $stmt = $conn->prepare('DELETE FROM pokemons WHERE id_pokemon = :id_pokemon');

        // Executa a exclusão passando o ID separadamente do SQL.
        return $stmt->execute(['id_pokemon' => $id_pokemon]);
    }

    // Subtrai o dano recebido e limita o HP mínimo a zero.
    public function receberDano(int $dano): void
    {
        $this->hp = max(0, $this->hp - max(0, $dano));
    }

    // Calcula o dano físico como ataque menos defesa e aplica ao alvo.
    public function atacar(Pokemon $pokemonAlvo): void
    {
        $dano = max(0, $this->forca_ataque - $pokemonAlvo->getDefesa());
        $pokemonAlvo->receberDano($dano);
    }

    // Restaura os pontos de vida atuais ao valor máximo.
    public function curar(): void
    {
        $this->hp = $this->hp_maximo;
    }

    // Retorna true quando o HP chegou a zero e o Pokémon foi derrotado.
    public function desmaiar(): bool
    {
        return $this->hp <= 0;
    }
}
