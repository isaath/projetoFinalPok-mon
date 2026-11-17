<?php

namespace App\Models;

use PDO;
use RuntimeException;

class Batalha
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function carregarPrevia(int $idUsuario, int $nivel): array
    {
        $pokemonsDoUsuario = (new TimePokemon($this->db))->listarDoUsuario($idUsuario);
        $pokemonInicial = null;

        foreach ($pokemonsDoUsuario as $pokemon) {
            if ((int) $pokemon['posicao'] === 1) {
                $pokemonInicial = $pokemon;
                break;
            }
        }

        $consulta = $this->db->prepare(
            'SELECT treinador.id_treinador,
                    treinador.nivel_jornada,
                    treinador.nome AS nome_treinador,
                    treinador.nome_insignia,
                    especialidade.nome AS especialidade,
                    pokemon.id_pokemon,
                    pokemon.nome AS nome_pokemon,
                    pokemon.hp AS hp_atual,
                    pokemon.hp_maximo,
                    COALESCE(
                        (
                            SELECT GROUP_CONCAT(tipo.nome ORDER BY pokemon_tipo.ordem SEPARATOR \' / \')
                            FROM pokemon_tipos AS pokemon_tipo
                            INNER JOIN tipos AS tipo ON tipo.id_tipo = pokemon_tipo.id_tipo
                            WHERE pokemon_tipo.id_pokemon = pokemon.id_pokemon
                        ),
                        tipo_principal.nome
                    ) AS tipos,
                    treinador_pokemon.ordem_batalha,
                    treinador_pokemon.ordem_captura
             FROM treinadores AS treinador
             INNER JOIN tipos AS especialidade
                ON especialidade.id_tipo = treinador.id_tipo_especialidade
             INNER JOIN treinador_pokemons AS treinador_pokemon
                ON treinador_pokemon.id_treinador = treinador.id_treinador
             INNER JOIN pokemons AS pokemon
                ON pokemon.id_pokemon = treinador_pokemon.id_pokemon
             LEFT JOIN tipos AS tipo_principal
                ON tipo_principal.id_tipo = pokemon.id_tipo
             WHERE treinador.nivel_jornada = :nivel
             ORDER BY treinador_pokemon.ordem_batalha'
        );
        $consulta->execute(['nivel' => $nivel]);
        $equipeTreinador = $consulta->fetchAll(PDO::FETCH_ASSOC);

        if (count($equipeTreinador) !== 3) {
            throw new RuntimeException(
                'A equipe do treinador do nível ' . $nivel . ' não possui três Pokémon cadastrados.'
            );
        }

        $primeiro = $equipeTreinador[0];

        return [
            'treinador' => [
                'id_treinador' => (int) $primeiro['id_treinador'],
                'nivel_jornada' => (int) $primeiro['nivel_jornada'],
                'nome' => $primeiro['nome_treinador'],
                'nome_insignia' => $primeiro['nome_insignia'],
                'especialidade' => $primeiro['especialidade'],
            ],
            'pokemon_jogador' => $pokemonInicial,
            'pokemon_adversario' => $primeiro,
            'equipe_treinador' => $equipeTreinador,
        ];
    }
}
