<?php

namespace App\Models;

use PDO;
use RuntimeException;
use Throwable;

class TimeInicial
{
    private const NOMES_POKEMONS = [
        'vulpix',
        'bellsprout',
        'charmander',
        'squirtle',
        'bulbasaur',
    ];

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function listarDisponiveis(): array
    {
        $consulta = $this->db->prepare(
            'SELECT p.id_pokemon, p.nome, p.hp_maximo, p.forca_ataque, p.defesa,
                    p.velocidade, t.nome AS tipo
             FROM pokemons p
             LEFT JOIN tipos t ON t.id_tipo = p.id_tipo
             WHERE LOWER(p.nome) IN (' . $this->placeholdersNomes() . ')
             ORDER BY p.nome'
        );
        $consulta->execute(self::NOMES_POKEMONS);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function jornadaIniciada(int $idUsuario): bool
    {
        $consulta = $this->db->prepare(
            'SELECT COUNT(*)
             FROM (
                 SELECT t.id_time
                 FROM times t
                 INNER JOIN time_pokemons tp ON tp.id_time = t.id_time
                 WHERE t.id_usuario = :id_usuario
                 GROUP BY t.id_time
                 HAVING COUNT(tp.id_time_pokemon) >= 3
             ) AS times_completos'
        );
        $consulta->execute(['id_usuario' => $idUsuario]);

        return (int) $consulta->fetchColumn() > 0;
    }

    public function salvarSelecaoInicial(int $idUsuario, array $idsPokemons): string
    {
        if (count($idsPokemons) !== 3) {
            return 'selecao_invalida';
        }

        foreach ($idsPokemons as $idPokemon) {
            if (!is_int($idPokemon) || $idPokemon < 1) {
                return 'selecao_invalida';
            }
        }

        if (count(array_unique($idsPokemons)) !== 3) {
            return 'selecao_invalida';
        }

        $this->db->beginTransaction();

        try {
            $usuario = $this->db->prepare(
                'SELECT id_usuario FROM usuarios WHERE id_usuario = :id_usuario FOR UPDATE'
            );
            $usuario->execute(['id_usuario' => $idUsuario]);

            if ($usuario->fetchColumn() === false) {
                $this->db->commit();
                return 'usuario_nao_encontrado';
            }

            if ($this->jornadaIniciada($idUsuario)) {
                $this->db->commit();
                return 'jornada_iniciada';
            }

            $consultaPokemons = $this->db->prepare(
                'SELECT id_pokemon, hp_maximo
                 FROM pokemons
                 WHERE id_pokemon IN (?, ?, ?)
                   AND LOWER(nome) IN (' . $this->placeholdersNomes() . ')
                 FOR UPDATE'
            );
            $consultaPokemons->execute(array_merge($idsPokemons, self::NOMES_POKEMONS));
            $pokemons = $consultaPokemons->fetchAll(PDO::FETCH_ASSOC);

            if (count($pokemons) !== 3) {
                $this->db->commit();
                return 'selecao_invalida';
            }

            $porId = [];
            foreach ($pokemons as $pokemon) {
                $porId[(int) $pokemon['id_pokemon']] = $pokemon;
            }

            $criarTime = $this->db->prepare(
                'INSERT INTO times (nome, id_usuario) VALUES (:nome, :id_usuario)'
            );
            $criarTime->execute([
                'nome' => 'Time inicial',
                'id_usuario' => $idUsuario,
            ]);
            $idTime = (int) $this->db->lastInsertId();

            if ($idTime < 1) {
                throw new RuntimeException('Não foi possível criar o time inicial.');
            }

            $adicionarPokemon = $this->db->prepare(
                'INSERT INTO time_pokemons (posicao, id_time, id_pokemon)
                 VALUES (:posicao, :id_time, :id_pokemon)'
            );
            $salvarHp = $this->db->prepare(
                'INSERT INTO time_pokemons_estado (id_time_pokemon, hp_atual)
                 VALUES (:id_time_pokemon, :hp_atual)'
            );

            foreach ($idsPokemons as $indice => $idPokemon) {
                $adicionarPokemon->execute([
                    'posicao' => $indice + 1,
                    'id_time' => $idTime,
                    'id_pokemon' => $idPokemon,
                ]);
                $idTimePokemon = (int) $this->db->lastInsertId();

                $salvarHp->execute([
                    'id_time_pokemon' => $idTimePokemon,
                    'hp_atual' => (int) $porId[$idPokemon]['hp_maximo'],
                ]);
            }

            $this->db->commit();

            return 'salvo';
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    private function placeholdersNomes(): string
    {
        return implode(', ', array_fill(0, count(self::NOMES_POKEMONS), '?'));
    }
}
