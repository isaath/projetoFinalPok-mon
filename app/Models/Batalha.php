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

    /**
     * Carrega as vitórias do jogador e indica quais níveis estão acessíveis.
     *
     * Os níveis vencidos continuam liberados para repetição. O primeiro nível
     * ainda não vencido também fica liberado para permitir a próxima batalha.
     */
    public function carregarProgressoNiveis(int $idUsuario): array
    {
        // Busca os oito líderes e junta as vitórias somente deste jogador.
        $consulta = $this->db->prepare(
            'SELECT treinador.nivel_jornada,
                    treinador.nome AS nome_treinador,
                    treinador.nome_insignia,
                    tipo.nome AS especialidade,
                    COALESCE(progresso.vitorias, 0) AS vitorias
             FROM treinadores AS treinador
             INNER JOIN tipos AS tipo
                ON tipo.id_tipo = treinador.id_tipo_especialidade
             LEFT JOIN treinadores_progresso AS progresso
                ON progresso.id_treinador = treinador.id_treinador
               AND progresso.id_usuario = :id_usuario
             WHERE treinador.nivel_jornada BETWEEN 1 AND 8
             ORDER BY treinador.nivel_jornada'
        );
        $consulta->execute(['id_usuario' => $idUsuario]);
        $linhas = $consulta->fetchAll(PDO::FETCH_ASSOC);

        // A jornada precisa ter os oito líderes cadastrados para montar o mapa.
        if (count($linhas) !== 8) {
            throw new RuntimeException('O banco não possui os oito níveis da jornada.');
        }

        // O nível 1 começa liberado; as próximas fases dependem da vitória anterior.
        $proximoNivel = 1;
        $progresso = [];

        foreach ($linhas as $linha) {
            $nivel = (int) $linha['nivel_jornada'];
            $vitorias = (int) $linha['vitorias'];

            // Níveis anteriores e o próximo nível podem ser acessados.
            $liberado = $nivel <= $proximoNivel;

            // Guarda os dados que a tela precisa para mostrar cada treinador.
            $progresso[$nivel] = [
                'nome' => (string) $linha['nome_treinador'],
                'insignia' => (string) $linha['nome_insignia'],
                'especialidade' => (string) $linha['especialidade'],
                'vitorias' => $vitorias,
                'liberado' => $liberado,
            ];

            // Só avança para a próxima fase quando o nível atual já foi vencido.
            if ($nivel === $proximoNivel && $vitorias > 0) {
                $proximoNivel++;
            }
        }

        return $progresso;
    }

    /**
     * Confere se o jogador pode abrir o nível informado.
     */
    public function nivelEstaLiberado(int $idUsuario, int $nivel): bool
    {
        // Números fora da jornada nunca correspondem a uma fase válida.
        if ($nivel < 1 || $nivel > 8) {
            return false;
        }

        // Usa a mesma regra de progressão exibida no mapa.
        $progresso = $this->carregarProgressoNiveis($idUsuario);

        return $progresso[$nivel]['liberado'] ?? false;
    }

    public function carregarPrevia(int $idUsuario, int $nivel): array
    {
        if ($nivel < 1 || $nivel > 8) {
            throw new RuntimeException('O nível informado não pertence à jornada de Kanto.');
        }

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

        $quantidadeEsperada = $nivel >= 5 ? 4 : 3;
        if (count($equipeTreinador) !== $quantidadeEsperada) {
            throw new RuntimeException(
                'A equipe do treinador do nível ' . $nivel
                . ' não possui os ' . $quantidadeEsperada . ' Pokémon esperados.'
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
