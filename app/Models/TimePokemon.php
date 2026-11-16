<?php

namespace App\Models;

use PDO;
use RuntimeException;
use Throwable;

class TimePokemon
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function listarDoUsuario(int $idUsuario): array
    {
        $consulta = $this->db->prepare(
            'SELECT tp.id_time_pokemon, tp.posicao, p.nome, tipos.nome AS tipo,
                    COALESCE(e.hp_atual, p.hp) AS hp_atual, p.hp_maximo
             FROM times t
             INNER JOIN time_pokemons tp ON tp.id_time = t.id_time
             INNER JOIN pokemons p ON p.id_pokemon = tp.id_pokemon
             LEFT JOIN tipos ON tipos.id_tipo = p.id_tipo
             LEFT JOIN time_pokemons_estado e ON e.id_time_pokemon = tp.id_time_pokemon
             WHERE t.id_usuario = :id_usuario
             ORDER BY t.id_time, tp.posicao'
        );
        $consulta->execute(['id_usuario' => $idUsuario]);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function moverPrioridade(
        int $idUsuario,
        int $idTimePokemon,
        int $posicaoDestino
    ): string {
        if ($idTimePokemon < 1 || $posicaoDestino < 1 || $posicaoDestino > 3) {
            return 'dados_invalidos';
        }

        $this->db->beginTransaction();

        try {
            if (!$this->bloquearUsuario($idUsuario)) {
                $this->db->commit();
                return 'usuario_nao_encontrado';
            }

            $pokemon = $this->buscarPokemonDoUsuario($idUsuario, $idTimePokemon);

            if ($pokemon === false) {
                $this->db->commit();
                return 'pokemon_nao_encontrado';
            }

            $posicaoAtual = (int) $pokemon['posicao'];
            if ($posicaoAtual < 1 || $posicaoAtual > 3) {
                $this->db->commit();
                return 'nao_esta_ativo';
            }

            if ($posicaoAtual === $posicaoDestino) {
                $this->db->commit();
                return 'mesma_posicao';
            }

            $idTime = (int) $pokemon['id_time'];
            $posicoes = $this->carregarPosicoes($idTime);

            if (!$this->timeAtivoCompleto($posicoes)) {
                $this->db->commit();
                return 'time_incompleto';
            }

            $this->moverComDeslocamento(
                $idTime,
                $idTimePokemon,
                $posicaoAtual,
                $posicaoDestino,
                $posicoes
            );

            $this->db->commit();
            return 'movido';
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function colocarNaPosicaoTres(int $idUsuario, int $idTimePokemon): string
    {
        if ($idTimePokemon < 1) {
            return 'dados_invalidos';
        }

        $this->db->beginTransaction();

        try {
            if (!$this->bloquearUsuario($idUsuario)) {
                $this->db->commit();
                return 'usuario_nao_encontrado';
            }

            $pokemon = $this->buscarPokemonDoUsuario($idUsuario, $idTimePokemon);

            if ($pokemon === false) {
                $this->db->commit();
                return 'pokemon_nao_encontrado';
            }

            $posicaoReserva = (int) $pokemon['posicao'];
            if ($posicaoReserva <= 3) {
                $this->db->commit();
                return 'ja_esta_ativo';
            }

            $idTime = (int) $pokemon['id_time'];
            $posicoes = $this->carregarPosicoes($idTime);

            if (!$this->timeAtivoCompleto($posicoes)) {
                $this->db->commit();
                return 'time_incompleto';
            }

            $idPokemonTerceiro = $posicoes[3];
            $this->atualizarPosicao($idTime, $idTimePokemon, 0);
            $this->atualizarPosicao($idTime, $idPokemonTerceiro, $posicaoReserva);
            $this->atualizarPosicao($idTime, $idTimePokemon, 3);

            $this->db->commit();
            return 'colocado';
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    private function bloquearUsuario(int $idUsuario): bool
    {
        $consulta = $this->db->prepare(
            'SELECT id_usuario FROM usuarios WHERE id_usuario = :id_usuario FOR UPDATE'
        );
        $consulta->execute(['id_usuario' => $idUsuario]);

        return $consulta->fetchColumn() !== false;
    }

    private function buscarPokemonDoUsuario(int $idUsuario, int $idTimePokemon)
    {
        $consulta = $this->db->prepare(
            'SELECT tp.id_time, tp.posicao
             FROM time_pokemons tp
             INNER JOIN times t ON t.id_time = tp.id_time
             WHERE t.id_usuario = :id_usuario
               AND tp.id_time_pokemon = :id_time_pokemon
             FOR UPDATE'
        );
        $consulta->execute([
            'id_usuario' => $idUsuario,
            'id_time_pokemon' => $idTimePokemon,
        ]);

        return $consulta->fetch(PDO::FETCH_ASSOC);
    }

    private function carregarPosicoes(int $idTime): array
    {
        $consulta = $this->db->prepare(
            'SELECT id_time_pokemon, posicao
             FROM time_pokemons
             WHERE id_time = :id_time
             FOR UPDATE'
        );
        $consulta->execute(['id_time' => $idTime]);

        $posicoes = [];
        foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $pokemon) {
            $posicoes[(int) $pokemon['posicao']] = (int) $pokemon['id_time_pokemon'];
        }

        return $posicoes;
    }

    private function timeAtivoCompleto(array $posicoes): bool
    {
        return isset($posicoes[1], $posicoes[2], $posicoes[3]);
    }

    private function moverComDeslocamento(
        int $idTime,
        int $idTimePokemon,
        int $posicaoAtual,
        int $posicaoDestino,
        array $posicoes
    ): void {
        $this->atualizarPosicao($idTime, $idTimePokemon, 0);

        if ($posicaoAtual > $posicaoDestino) {
            for ($posicao = $posicaoAtual - 1; $posicao >= $posicaoDestino; $posicao--) {
                $this->atualizarPosicao($idTime, $posicoes[$posicao], $posicao + 1);
            }
        } else {
            for ($posicao = $posicaoAtual + 1; $posicao <= $posicaoDestino; $posicao++) {
                $this->atualizarPosicao($idTime, $posicoes[$posicao], $posicao - 1);
            }
        }

        $this->atualizarPosicao($idTime, $idTimePokemon, $posicaoDestino);
    }

    private function atualizarPosicao(int $idTime, int $idTimePokemon, int $posicao): void
    {
        $atualizacao = $this->db->prepare(
            'UPDATE time_pokemons
             SET posicao = :posicao
             WHERE id_time = :id_time AND id_time_pokemon = :id_time_pokemon'
        );
        $atualizacao->execute([
            'posicao' => $posicao,
            'id_time' => $idTime,
            'id_time_pokemon' => $idTimePokemon,
        ]);

        if ($atualizacao->rowCount() !== 1) {
            throw new RuntimeException('Não foi possível atualizar a posição do Pokémon.');
        }
    }
}
