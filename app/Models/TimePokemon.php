<?php

namespace App\Models;

use PDO;
use RuntimeException;
use Throwable;

/** Consulta e reorganiza os Pokémon que pertencem aos times do jogador. */
class TimePokemon
{
    private PDO $db;

    /** Mantém a conexão PDO para leituras e alterações persistentes. */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Busca espécies, posições e HP individual para montar a tela do time. */
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

    /**
     * Move um Pokémon ativo e desloca os demais para preservar a ordem relativa.
     */
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
            // O bloqueio serializa alterações concorrentes ao mesmo time.
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
            // Evita deixar posições parcialmente atualizadas se uma etapa falhar.
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * Substitui o Pokémon da posição 3 por um da reserva e envia o substituído à reserva.
     */
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
            // A posição temporária 0 evita colidir com a chave única durante a troca.
            $this->atualizarPosicao($idTime, $idTimePokemon, 0);
            $this->atualizarPosicao($idTime, $idPokemonTerceiro, $posicaoReserva);
            $this->atualizarPosicao($idTime, $idTimePokemon, 3);

            $this->db->commit();
            return 'colocado';
        } catch (Throwable $exception) {
            // Reverte a troca inteira se qualquer atualização não puder ser concluída.
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    /** Bloqueia a linha do usuário e confirma que ela ainda existe. */
    private function bloquearUsuario(int $idUsuario): bool
    {
        $consulta = $this->db->prepare(
            'SELECT id_usuario FROM usuarios WHERE id_usuario = :id_usuario FOR UPDATE'
        );
        $consulta->execute(['id_usuario' => $idUsuario]);

        return $consulta->fetchColumn() !== false;
    }

    /** Busca um membro somente se ele pertencer a um time do usuário autenticado. */
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

    /** Mapeia cada posição ocupada ao identificador do Pokémon associado. */
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

    /** Confirma que as três posições de batalha estão ocupadas. */
    private function timeAtivoCompleto(array $posicoes): bool
    {
        return isset($posicoes[1], $posicoes[2], $posicoes[3]);
    }

    /** Reordena a escalação sem deixar posições duplicadas durante a atualização. */
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

    /** Atualiza uma posição e lança erro se nenhuma linha for alterada. */
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
