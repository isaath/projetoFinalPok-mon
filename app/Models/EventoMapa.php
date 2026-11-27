<?php

namespace App\Models;

use DomainException;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Na apresentação: chamado por MapaController, valida o desbloqueio, sorteia
 * um resultado e grava efeito e mensagem juntos; a regra de distribuição
 * atualmente codificada ainda aguarda confirmação dos desenvolvedores.
 */
final class EventoMapa
{
    private const QUANTIDADE_PONTOS = 5;

    private const NIVEIS_REQUERIDOS_POR_PONTO = [
        1 => 2,
        2 => 3,
        3 => 4,
        4 => 5,
        5 => 6,
    ];

    private const ITENS_COMUNS = [
        'Potion',
        'Antidote',
        'Burn Heal',
        'Ice Heal',
        'Awakening',
        'Paralyze Heal',
        'Full Heal',
    ];

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Informa qual vitória libera cada ponto, para a View refletir a regra do jogo. */
    public static function niveisRequeridosPorPonto(): array
    {
        return self::NIVEIS_REQUERIDOS_POR_PONTO;
    }

    /** Retorna os pontos já usados para desabilitá-los e exibir seus resultados. */
    public function listarDoUsuario(int $idUsuario): array
    {
        if ($idUsuario < 1) {
            throw new InvalidArgumentException('O usuário do mapa é inválido.');
        }

        $consulta = $this->db->prepare(
            'SELECT id_ponto, tipo_evento, mensagem
             FROM mapa_eventos
             WHERE id_usuario = :id_usuario
             ORDER BY id_ponto'
        );
        $consulta->execute(['id_usuario' => $idUsuario]);

        $eventos = [];
        foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $evento) {
            $idPonto = (int) $evento['id_ponto'];
            $eventos[$idPonto] = [
                'tipo' => (string) $evento['tipo_evento'],
                'mensagem' => (string) $evento['mensagem'],
            ];
        }

        return $eventos;
    }

    /**
     * Aplica um evento uma única vez por ponto e jogador.
     *
     * A trava na conta serializa cliques simultâneos e a transação grava o efeito
     * e o resultado juntos, impedindo recompensas duplicadas após recarregar.
     */
    public function sortear(int $idUsuario, int $idPonto): array
    {
        if (
            $idUsuario < 1
            || $idPonto < 1
            || $idPonto > self::QUANTIDADE_PONTOS
        ) {
            throw new InvalidArgumentException('O ponto de exploração é inválido.');
        }

        $this->db->beginTransaction();

        try {
            $consultarUsuario = $this->db->prepare(
                'SELECT dinheiro
                 FROM usuarios
                 WHERE id_usuario = :id_usuario
                 FOR UPDATE'
            );
            $consultarUsuario->execute(['id_usuario' => $idUsuario]);
            $saldo = $consultarUsuario->fetchColumn();
            if ($saldo === false) {
                throw new RuntimeException('O jogador do evento não foi encontrado.');
            }

            $this->validarDesbloqueio($idUsuario, $idPonto);

            $eventoSalvo = $this->buscarEvento($idUsuario, $idPonto);
            if ($eventoSalvo !== null) {
                $this->db->commit();
                return $eventoSalvo;
            }

            $idTime = $this->buscarTime($idUsuario);
            $sorteio = random_int(1, 100);

            if ($sorteio <= 10) {
                $evento = $this->adicionarPokemon($idUsuario, $idTime);
            } elseif ($sorteio <= 25) {
                $evento = $this->adicionarItem($idUsuario);
            } elseif ($sorteio <= 40) {
                $evento = $this->alterarSaldo($idUsuario, 15, true, (int) $saldo);
            } elseif ($sorteio <= 55) {
                $evento = $this->alterarSaldo($idUsuario, 15, false, (int) $saldo);
            } elseif ($sorteio <= 70) {
                $evento = $this->alterarHpDaEquipe($idUsuario, $idTime, false);
            } elseif ($sorteio <= 85) {
                $evento = $this->alterarHpDaEquipe($idUsuario, $idTime, true);
            } else {
                $evento = [
                    'tipo' => 'vazio',
                    'mensagem' => 'Você explorou o local, mas não encontrou nada.',
                ];
            }

            $registrarEvento = $this->db->prepare(
                'INSERT INTO mapa_eventos
                    (id_usuario, id_ponto, tipo_evento, mensagem)
                 VALUES
                    (:id_usuario, :id_ponto, :tipo_evento, :mensagem)'
            );
            $registrarEvento->execute([
                'id_usuario' => $idUsuario,
                'id_ponto' => $idPonto,
                'tipo_evento' => $evento['tipo'],
                'mensagem' => $evento['mensagem'],
            ]);

            $this->db->commit();
            return $evento;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    private function validarDesbloqueio(int $idUsuario, int $idPonto): void
    {
        $nivelRequerido = self::NIVEIS_REQUERIDOS_POR_PONTO[$idPonto];
        $consulta = $this->db->prepare(
            'SELECT nome_treinador, vitorias
             FROM vw_progresso_ginasios
             WHERE id_usuario = :id_usuario
               AND nivel_jornada = :nivel_jornada'
        );
        $consulta->execute([
            'id_usuario' => $idUsuario,
            'nivel_jornada' => $nivelRequerido,
        ]);
        $progresso = $consulta->fetch(PDO::FETCH_ASSOC);

        if ($progresso === false) {
            throw new RuntimeException('Não foi encontrado o progresso do nível necessário.');
        }

        if ((int) $progresso['vitorias'] < 1) {
            throw new DomainException(
                'Vença ' . $progresso['nome_treinador']
                . ' para liberar este ponto de exploração.'
            );
        }
    }

    private function buscarEvento(int $idUsuario, int $idPonto): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT tipo_evento, mensagem
             FROM mapa_eventos
             WHERE id_usuario = :id_usuario
               AND id_ponto = :id_ponto
             FOR UPDATE'
        );
        $consulta->execute([
            'id_usuario' => $idUsuario,
            'id_ponto' => $idPonto,
        ]);
        $evento = $consulta->fetch(PDO::FETCH_ASSOC);

        if ($evento === false) {
            return null;
        }

        return [
            'tipo' => (string) $evento['tipo_evento'],
            'mensagem' => (string) $evento['mensagem'],
        ];
    }

    private function buscarTime(int $idUsuario): int
    {
        $consulta = $this->db->prepare(
            'SELECT id_time
             FROM times
             WHERE id_usuario = :id_usuario
             ORDER BY id_time
             LIMIT 1
             FOR UPDATE'
        );
        $consulta->execute(['id_usuario' => $idUsuario]);
        $idTime = $consulta->fetchColumn();

        if ($idTime === false) {
            throw new RuntimeException('Não foi encontrado o time do jogador.');
        }

        return (int) $idTime;
    }

    private function adicionarPokemon(int $idUsuario, int $idTime): array
    {
        $consultarDisponiveis = $this->db->prepare(
            'SELECT pokemon.id_pokemon, pokemon.nome, pokemon.hp_maximo
             FROM pokemons AS pokemon
             WHERE pokemon.hp_maximo > 0
               AND NOT EXISTS (
                   SELECT 1
                   FROM time_pokemons AS membro
                   INNER JOIN times AS time_usuario
                      ON time_usuario.id_time = membro.id_time
                   WHERE time_usuario.id_usuario = :id_usuario
                     AND membro.id_pokemon = pokemon.id_pokemon
               )
             ORDER BY pokemon.id_pokemon'
        );
        $consultarDisponiveis->execute(['id_usuario' => $idUsuario]);
        $pokemons = $consultarDisponiveis->fetchAll(PDO::FETCH_ASSOC);

        if ($pokemons === []) {
            return [
                'tipo' => 'pokemon',
                'mensagem' => 'Você já possui todos os Pokémon disponíveis no catálogo.',
            ];
        }

        $pokemon = $pokemons[random_int(0, count($pokemons) - 1)];
        $consultarPosicao = $this->db->prepare(
            'SELECT COALESCE(MAX(posicao), 3)
             FROM time_pokemons
             WHERE id_time = :id_time'
        );
        $consultarPosicao->execute(['id_time' => $idTime]);
        $posicao = max(3, (int) $consultarPosicao->fetchColumn()) + 1;

        if ($posicao > 255) {
            throw new RuntimeException('Não há mais posições disponíveis na reserva.');
        }

        $adicionarMembro = $this->db->prepare(
            'INSERT INTO time_pokemons (posicao, id_time, id_pokemon)
             VALUES (:posicao, :id_time, :id_pokemon)'
        );
        $adicionarMembro->execute([
            'posicao' => $posicao,
            'id_time' => $idTime,
            'id_pokemon' => (int) $pokemon['id_pokemon'],
        ]);
        $idTimePokemon = (int) $this->db->lastInsertId();

        if ($idTimePokemon < 1) {
            throw new RuntimeException('Não foi possível adicionar o Pokémon à reserva.');
        }

        $salvarHp = $this->db->prepare(
            'INSERT INTO time_pokemons_estado (id_time_pokemon, hp_atual)
             VALUES (:id_time_pokemon, :hp_atual)'
        );
        $salvarHp->execute([
            'id_time_pokemon' => $idTimePokemon,
            'hp_atual' => (int) $pokemon['hp_maximo'],
        ]);

        return [
            'tipo' => 'pokemon',
            'mensagem' => $pokemon['nome']
                . ' quis fazer parte da sua equipe! Ele está na reserva; confira em Meu time.',
        ];
    }

    private function adicionarItem(int $idUsuario): array
    {
        $placeholders = implode(
            ', ',
            array_fill(0, count(self::ITENS_COMUNS), '?')
        );
        $consultarItens = $this->db->prepare(
            'SELECT id_item, nome
             FROM itens_loja
             WHERE nome IN (' . $placeholders . ')
             ORDER BY id_item'
        );
        $consultarItens->execute(self::ITENS_COMUNS);
        $itens = $consultarItens->fetchAll(PDO::FETCH_ASSOC);

        if ($itens === []) {
            throw new RuntimeException('Não há itens comuns cadastrados para o evento.');
        }

        $item = $itens[random_int(0, count($itens) - 1)];
        $quantidade = random_int(1, 2);
        $adicionarInventario = $this->db->prepare(
            'INSERT INTO inventario (id_usuario, id_item, quantidade)
             VALUES (:id_usuario, :id_item, :quantidade)
             ON DUPLICATE KEY UPDATE quantidade = quantidade + VALUES(quantidade)'
        );
        $adicionarInventario->execute([
            'id_usuario' => $idUsuario,
            'id_item' => (int) $item['id_item'],
            'quantidade' => $quantidade,
        ]);

        return [
            'tipo' => 'item',
            'mensagem' => 'Você encontrou ' . $quantidade . 'x ' . $item['nome'] . '!',
        ];
    }

    private function alterarSaldo(
        int $idUsuario,
        int $quantidade,
        bool $ganhar,
        int $saldo
    ): array {
        $variacao = $ganhar ? $quantidade : min($quantidade, $saldo);
        $operador = $ganhar ? '+' : '-';
        $alterarSaldo = $this->db->prepare(
            'UPDATE usuarios
             SET dinheiro = dinheiro ' . $operador . ' :variacao
             WHERE id_usuario = :id_usuario'
        );
        $alterarSaldo->execute([
            'variacao' => $variacao,
            'id_usuario' => $idUsuario,
        ]);

        return [
            'tipo' => $ganhar ? 'pila_ganho' : 'pila_perdido',
            'mensagem' => $ganhar
                ? 'Você encontrou ' . $variacao . ' PokePila!'
                : 'Você perdeu ' . $variacao . ' PokePila.',
        ];
    }

    private function alterarHpDaEquipe(
        int $idUsuario,
        int $idTime,
        bool $curar
    ): array {
        $consultarEquipe = $this->db->prepare(
            'SELECT membro.id_time_pokemon,
                    COALESCE(estado.hp_atual, pokemon.hp_maximo) AS hp_atual,
                    pokemon.hp_maximo
             FROM time_pokemons AS membro
             INNER JOIN pokemons AS pokemon
                ON pokemon.id_pokemon = membro.id_pokemon
             INNER JOIN times AS time_usuario
                ON time_usuario.id_time = membro.id_time
             LEFT JOIN time_pokemons_estado AS estado
                ON estado.id_time_pokemon = membro.id_time_pokemon
             WHERE time_usuario.id_usuario = :id_usuario
               AND membro.id_time = :id_time
             ORDER BY membro.posicao
             FOR UPDATE'
        );
        $consultarEquipe->execute([
            'id_usuario' => $idUsuario,
            'id_time' => $idTime,
        ]);
        $equipe = $consultarEquipe->fetchAll(PDO::FETCH_ASSOC);
        if ($equipe === []) {
            throw new RuntimeException('O time do jogador não possui Pokémon cadastrados.');
        }

        $totalAlterado = 0;
        foreach ($equipe as $membro) {
            $hpAtual = (int) $membro['hp_atual'];
            $hpMaximo = (int) $membro['hp_maximo'];
            if ($hpMaximo < 1) {
                throw new RuntimeException('Um Pokémon do time possui HP máximo inválido.');
            }

            if ($hpAtual <= 0 || ($curar && $hpAtual >= $hpMaximo)) {
                continue;
            }

            $percentual = $curar ? 0.15 : 0.10;
            $variacaoHp = max(1, (int) ceil($hpMaximo * $percentual));
            $hpNovo = $curar
                ? min($hpMaximo, $hpAtual + $variacaoHp)
                : max(1, $hpAtual - $variacaoHp);
            $totalAlterado += abs($hpNovo - $hpAtual);

            $salvarHp = $this->db->prepare(
                'INSERT INTO time_pokemons_estado (id_time_pokemon, hp_atual)
                 VALUES (:id_time_pokemon, :hp_atual)
                 ON DUPLICATE KEY UPDATE hp_atual = VALUES(hp_atual)'
            );
            $salvarHp->execute([
                'id_time_pokemon' => (int) $membro['id_time_pokemon'],
                'hp_atual' => $hpNovo,
            ]);
        }

        if ($curar) {
            return [
                'tipo' => 'cura',
                'mensagem' => $totalAlterado > 0
                    ? 'Seus Pokémon recuperaram ' . $totalAlterado . ' HP no total.'
                    : 'Você encontrou ervas, mas seus Pokémon não precisavam de cura.',
            ];
        }

        return [
            'tipo' => 'ataque',
            'mensagem' => $totalAlterado > 0
                ? 'Um Pokémon selvagem assustou sua equipe! Seus Pokémon perderam '
                    . $totalAlterado . ' HP no total, sem desmaiar. Visite o Pokecenter para recuperá-los.'
                : 'Um Pokémon selvagem tentou atacar, mas sua equipe conseguiu se proteger.',
        ];
    }
}
