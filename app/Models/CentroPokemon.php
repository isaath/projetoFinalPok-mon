<?php

namespace App\Models;

use PDO;
use RuntimeException;
use Throwable;

/**
 * Na apresentação: consulta HP individual pela View SQL vw_time_pokemons e
 * atualiza saldo/HP em transação; CentroController chama este Model.
 */
class CentroPokemon
{
    // Valor descontado uma vez quando há pelo menos um Pokémon ferido.
    public const CUSTO_VISITA = 40;

    private PDO $db;

    /** Recebe a conexão usada para consultar e atualizar os dados do jogador. */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Lista Pokémon de todos os times do usuário com seu HP individual atual. */
    public function listarPokemonsUsuario(int $idUsuario): array
    {
        $consulta = $this->db->prepare(
            'SELECT id_time_pokemon, nome_time, posicao, nome, hp_atual, hp_maximo
             FROM vw_time_pokemons
             WHERE id_usuario = :id_usuario
             ORDER BY id_time, posicao'
        );
        $consulta->execute(['id_usuario' => $idUsuario]);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Consulta o saldo e informa erro se a conta não existir. */
    public function obterSaldo(int $idUsuario): int
    {
        $consulta = $this->db->prepare(
            'SELECT dinheiro FROM usuarios WHERE id_usuario = :id_usuario'
        );
        $consulta->execute(['id_usuario' => $idUsuario]);
        $saldo = $consulta->fetchColumn();

        if ($saldo === false) {
            throw new RuntimeException('Usuário não encontrado ao consultar o saldo.');
        }

        return (int) $saldo;
    }

    /**
     * Cura Pokémon feridos e cobra uma única visita dentro da mesma transação.
     *
     * Os estados são gravados por membro do time, sem alterar o HP global da espécie.
     */
    public function curarTodosDoUsuario(int $idUsuario): array
    {
        $this->db->beginTransaction();

        try {
            // Bloqueia a conta enquanto saldo e custo da visita são verificados.
            $consultaSaldo = $this->db->prepare(
                'SELECT dinheiro FROM usuarios WHERE id_usuario = :id_usuario FOR UPDATE'
            );
            $consultaSaldo->execute(['id_usuario' => $idUsuario]);
            $saldo = $consultaSaldo->fetchColumn();

            if ($saldo === false) {
                $this->db->commit();
                return ['status' => 'usuario_nao_encontrado'];
            }

            $consultaPokemons = $this->db->prepare(
                'SELECT tp.id_time_pokemon, COALESCE(e.hp_atual, p.hp) AS hp_atual,
                        p.hp_maximo
                 FROM time_pokemons tp
                 INNER JOIN times t ON t.id_time = tp.id_time
                 INNER JOIN pokemons p ON p.id_pokemon = tp.id_pokemon
                 LEFT JOIN time_pokemons_estado e ON e.id_time_pokemon = tp.id_time_pokemon
                 WHERE t.id_usuario = :id_usuario
                 FOR UPDATE'
            );
            $consultaPokemons->execute(['id_usuario' => $idUsuario]);
            $pokemons = $consultaPokemons->fetchAll(PDO::FETCH_ASSOC);

            if ($pokemons === []) {
                $this->db->commit();
                return ['status' => 'sem_pokemons', 'saldo' => (int) $saldo];
            }

            $feridos = array_filter(
                $pokemons,
                static fn (array $pokemon): bool =>
                    (int) $pokemon['hp_atual'] < (int) $pokemon['hp_maximo']
            );

            // Não cobra uma visita quando não há HP para recuperar.
            if ($feridos === []) {
                $this->db->commit();
                return ['status' => 'ja_completo', 'saldo' => (int) $saldo];
            }

            if ((int) $saldo < self::CUSTO_VISITA) {
                $this->db->commit();
                return ['status' => 'saldo_insuficiente', 'saldo' => (int) $saldo];
            }

            // Condição no UPDATE protege o saldo caso ele mude antes da cobrança.
            $cobrar = $this->db->prepare(
                'UPDATE usuarios
                 SET dinheiro = dinheiro - :custo_debito
                 WHERE id_usuario = :id_usuario AND dinheiro >= :custo_verificacao'
            );
            $cobrar->execute([
                'custo_debito' => self::CUSTO_VISITA,
                'id_usuario' => $idUsuario,
                'custo_verificacao' => self::CUSTO_VISITA,
            ]);

            if ($cobrar->rowCount() !== 1) {
                throw new RuntimeException('Não foi possível cobrar a visita ao Pokecenter.');
            }

            $salvarHp = $this->db->prepare(
                'INSERT INTO time_pokemons_estado (id_time_pokemon, hp_atual)
                 VALUES (:id_time_pokemon, :hp_atual)
                 ON DUPLICATE KEY UPDATE hp_atual = VALUES(hp_atual)'
            );

            // Atualiza apenas os Pokémon feridos, inclusive os que estavam com HP zero.
            foreach ($feridos as $pokemon) {
                $salvarHp->execute([
                    'id_time_pokemon' => (int) $pokemon['id_time_pokemon'],
                    'hp_atual' => (int) $pokemon['hp_maximo'],
                ]);
            }

            $this->db->commit();

            return [
                'status' => 'curado',
                'quantidade' => count($feridos),
                'saldo' => (int) $saldo - self::CUSTO_VISITA,
            ];
        } catch (Throwable $exception) {
            // Desfaz a cobrança e qualquer atualização de HP se algo falhar.
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }
}
