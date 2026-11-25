<?php

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

/**
 * Aplica as regras de combate sem acessar o banco de dados ou a sessão PHP.
 *
 * Recebe o estado da batalha, altera esse estado e devolve o resultado para
 * o controller persistir ou exibir.
 */
final class BatalhaEngine
{
    /** Dano mínimo de um golpe que não seja imune ou exclusivamente de status. */
    private const DANO_MINIMO_GOLPE = 8;

    /**
     * Para cada tipo defensor, lista os tipos de ataque que causam dano dobrado.
     * As chaves e valores usam os nomes de tipos cadastrados no projeto.
     */
    private const FRAQUEZAS = [
        'normal' => ['lutador'],
        'fogo' => ['agua', 'terra', 'pedra'],
        'agua' => ['eletrico', 'planta'],
        'eletrico' => ['terra'],
        'planta' => ['fogo', 'gelo', 'veneno', 'voador', 'inseto'],
        'gelo' => ['fogo', 'lutador', 'pedra', 'aco'],
        'lutador' => ['voador', 'psiquico'],
        'veneno' => ['terra', 'psiquico'],
        'terra' => ['agua', 'planta', 'gelo'],
        'voador' => ['eletrico', 'gelo', 'pedra'],
        'psiquico' => ['inseto', 'fantasma', 'sombrio'],
        'inseto' => ['fogo', 'voador', 'pedra'],
        'pedra' => ['agua', 'planta', 'lutador', 'terra', 'aco'],
        'fantasma' => ['fantasma', 'sombrio'],
        'dragao' => ['gelo', 'dragao'],
        'sombrio' => ['lutador', 'inseto'],
        'aco' => ['fogo', 'lutador', 'terra'],
    ];

    /**
     * Lista os tipos de ataque que causam metade do dano a cada tipo defensor.
     */
    private const RESISTENCIAS = [
        'normal' => [],
        'fogo' => ['fogo', 'planta', 'gelo', 'inseto', 'aco'],
        'agua' => ['fogo', 'agua', 'gelo', 'aco'],
        'eletrico' => ['eletrico', 'voador', 'aco'],
        'planta' => ['agua', 'eletrico', 'planta', 'terra'],
        'gelo' => ['gelo'],
        'lutador' => ['inseto', 'pedra', 'sombrio'],
        'veneno' => ['lutador', 'veneno', 'inseto', 'planta'],
        'terra' => ['veneno', 'pedra'],
        'voador' => ['planta', 'lutador', 'inseto'],
        'psiquico' => ['lutador', 'psiquico'],
        'inseto' => ['planta', 'lutador', 'terra'],
        'pedra' => ['normal', 'fogo', 'veneno', 'voador'],
        'fantasma' => ['veneno', 'inseto'],
        'dragao' => ['fogo', 'agua', 'eletrico', 'planta'],
        'sombrio' => ['fantasma', 'sombrio'],
        'aco' => [
            'normal', 'planta', 'gelo', 'voador', 'psiquico', 'inseto',
            'pedra', 'fantasma', 'dragao', 'sombrio', 'aco',
        ],
    ];

    /**
     * Lista os ataques que não causam dano ao tipo defensor correspondente.
     */
    private const IMUNIDADES = [
        'normal' => ['fantasma'],
        'eletrico' => [],
        'voador' => ['terra'],
        'terra' => ['eletrico'],
        'fantasma' => ['normal', 'lutador'],
        'sombrio' => ['psiquico'],
        'aco' => ['veneno'],
    ];

    /**
     * Resolve um turno completo: ataque do jogador, resposta do líder e efeitos residuais.
     *
     * A prioridade do movimento decide primeiro; velocidade desempata e, se
     * também empatar, a ordem é sorteada.
     */
    public static function resolverTurno(array $batalha, int $idMovimentoJogador): array
    {
        if (!empty($batalha['finalizada'])) {
            throw new RuntimeException('Esta batalha já foi encerrada.');
        }

        $jogador = self::pokemonAtivo($batalha, 'jogador');
        $adversario = self::pokemonAtivo($batalha, 'adversario');
        if ($jogador === null || $adversario === null) {
            throw new RuntimeException('Não há Pokémon ativo para continuar a batalha.');
        }

        $movimentoJogador = self::buscarMovimento($jogador, $idMovimentoJogador);
        $movimentoAdversario = self::escolherMovimentoComputador($adversario, $jogador);
        $indiceJogador = (int) $batalha['jogador']['ativo'];
        $indiceAdversario = (int) $batalha['adversario']['ativo'];

        foreach (['jogador', 'adversario'] as $lado) {
            $indice = (int) $batalha[$lado]['ativo'];
            $batalha[$lado]['equipe'][$indice]['protegido'] = false;
            $batalha[$lado]['equipe'][$indice]['recuo'] = false;
        }

        $acoes = [
            [
                'lado' => 'jogador',
                'indice' => $indiceJogador,
                'movimento' => $movimentoJogador,
                'velocidade' => self::velocidadeEfetiva($jogador),
            ],
            [
                'lado' => 'adversario',
                'indice' => $indiceAdversario,
                'movimento' => $movimentoAdversario,
                'velocidade' => self::velocidadeEfetiva($adversario),
            ],
        ];

        // Organiza as duas ações conforme prioridade e velocidade.
        $primeiraAcao = $acoes[0];
        $segundaAcao = $acoes[1];
        if (
            $segundaAcao['movimento']['prioridade'] > $primeiraAcao['movimento']['prioridade']
            || (
                $segundaAcao['movimento']['prioridade'] === $primeiraAcao['movimento']['prioridade']
                && $segundaAcao['velocidade'] > $primeiraAcao['velocidade']
            )
            || (
                $segundaAcao['movimento']['prioridade'] === $primeiraAcao['movimento']['prioridade']
                && $segundaAcao['velocidade'] === $primeiraAcao['velocidade']
                && random_int(0, 1) === 1
            )
        ) {
            $acoes = [$segundaAcao, $primeiraAcao];
        }

        $mensagens = ['Turno ' . ((int) $batalha['turnos'] + 1) . '.'];
        foreach ($acoes as $acao) {
            if (
                (int) $batalha[$acao['lado']]['ativo'] !== $acao['indice']
                || !empty($batalha['finalizada'])
            ) {
                continue;
            }

            self::executarAcao($batalha, $acao['lado'], $acao['movimento'], $mensagens);
            self::avancarPokemonsDesmaiados($batalha, $mensagens);
            self::definirResultadoSeAcabou($batalha, $mensagens);
        }

        // Condições como veneno e queimadura causam dano depois das ações do turno.
        if (empty($batalha['finalizada'])) {
            foreach (['jogador', 'adversario'] as $lado) {
                self::aplicarDanoResidual($batalha, $lado, $mensagens);
                self::avancarPokemonsDesmaiados($batalha, $mensagens);
                self::definirResultadoSeAcabou($batalha, $mensagens);
                if (!empty($batalha['finalizada'])) {
                    break;
                }
            }
        }

        $batalha['turnos'] = (int) $batalha['turnos'] + 1;
        $batalha['mensagens'] = array_merge(
            $batalha['mensagens'] ?? [],
            $mensagens
        );

        return $batalha;
    }

    /**
     * Troca o Pokémon ativo e consome o turno, dando ao adversário uma ação.
     */
    public static function resolverTroca(array $batalha, int $indiceTroca): array
    {
        if (!empty($batalha['finalizada'])) {
            throw new RuntimeException('Esta batalha já foi encerrada.');
        }
        if (!isset($batalha['jogador']['equipe'][$indiceTroca])) {
            throw new InvalidArgumentException('O Pokémon escolhido não existe no time.');
        }
        $pokemonTroca = $batalha['jogador']['equipe'][$indiceTroca];
        if ((int) $pokemonTroca['hp'] <= 0) {
            throw new InvalidArgumentException('Não é possível trocar para um Pokémon desmaiado.');
        }

        $indiceAtual = (int) $batalha['jogador']['ativo'];
        if ($indiceAtual === $indiceTroca) {
            throw new InvalidArgumentException('Esse Pokémon já está ativo.');
        }

        $batalha['jogador']['ativo'] = $indiceTroca;
        $mensagens = [
            'Turno ' . ((int) $batalha['turnos'] + 1) . '.',
            $pokemonTroca['nome'] . ' entrou no lugar de '
                . $batalha['jogador']['equipe'][$indiceAtual]['nome'] . '.',
        ];
        self::executarAcaoComputador($batalha, $mensagens);
        $batalha['turnos'] = (int) $batalha['turnos'] + 1;
        $batalha['mensagens'] = array_merge($batalha['mensagens'] ?? [], $mensagens);
        return $batalha;
    }

    /**
     * Aplica um item permitido durante a batalha e, se possível, executa a resposta do líder.
     *
     * O índice do alvo é opcional; sem ele, o item é usado no Pokémon ativo.
     */
    public static function resolverUsoItem(
        array $batalha,
        array $item,
        ?int $indiceAlvo = null
    ): array
    {
        if (!empty($batalha['finalizada'])) {
            throw new RuntimeException('Esta batalha já foi encerrada.');
        }
        $indice = $indiceAlvo ?? (int) $batalha['jogador']['ativo'];
        if (!isset($batalha['jogador']['equipe'][$indice])) {
            throw new InvalidArgumentException('O Pokémon escolhido para o item não existe.');
        }
        $pokemon =& $batalha['jogador']['equipe'][$indice];
        $tipo = (string) $item['tipo'];

        if ($tipo === 'revive') {
            if ((int) $pokemon['hp'] > 0) {
                throw new InvalidArgumentException('Revive só pode ser usado em um Pokémon desmaiado.');
            }
            $pokemon['hp'] = (int) round((int) $pokemon['hp_maximo'] * 0.5);
            $mensagens = [
                'Turno ' . ((int) $batalha['turnos'] + 1) . '.',
                $pokemon['nome'] . ' foi revivido com metade do HP.',
            ];
        } elseif (str_starts_with($tipo, 'cura_') || $tipo === 'cura_status') {
            if ((int) $pokemon['hp'] <= 0) {
                throw new InvalidArgumentException('Use Revive antes de curar este Pokémon.');
            }

            if ($tipo === 'cura_status') {
                $nomeItem = strtolower((string) $item['nome']);
                $statusPermitidos = [
                    'antidote' => ['veneno', 'toxico'],
                    'burn heal' => ['queimadura'],
                    'ice heal' => ['congelamento'],
                    'awakening' => ['sono'],
                    'paralyze heal' => ['paralisia'],
                ];
                $statusDesejado = $statusPermitidos[$nomeItem] ?? null;
                if (
                    $statusDesejado !== null
                    && !in_array($pokemon['status'], $statusDesejado, true)
                ) {
                    throw new InvalidArgumentException(
                        $item['nome'] . ' não cura a condição atual de ' . $pokemon['nome'] . '.'
                    );
                }
                $pokemon['status'] = null;
                $pokemon['status_turnos'] = 0;
                $pokemon['toxico_contagem'] = 0;
                $mensagens = [
                    'Turno ' . ((int) $batalha['turnos'] + 1) . '.',
                    $item['nome'] . ' removeu a condição de ' . $pokemon['nome'] . '.',
                ];
            } else {
                $hpAnterior = (int) $pokemon['hp'];
                $valor = (int) $item['valor_efeito'];
                $nomeItem = strtolower((string) $item['nome']);
                if ($tipo === 'cura_hp_status' || $nomeItem === 'max potion') {
                    $pokemon['hp'] = (int) $pokemon['hp_maximo'];
                } else {
                    $pokemon['hp'] = min((int) $pokemon['hp_maximo'], $hpAnterior + $valor);
                }
                if ($tipo === 'cura_hp_status') {
                    $pokemon['status'] = null;
                    $pokemon['status_turnos'] = 0;
                    $pokemon['toxico_contagem'] = 0;
                }
                $mensagens = [
                    'Turno ' . ((int) $batalha['turnos'] + 1) . '.',
                    $item['nome'] . ' recuperou '
                        . ((int) $pokemon['hp'] - $hpAnterior) . ' HP de '
                        . $pokemon['nome'] . '.',
                ];
            }
        } else {
            throw new InvalidArgumentException('Este item não pode ser usado durante a batalha.');
        }

        self::executarAcaoComputador($batalha, $mensagens);
        $batalha['turnos'] = (int) $batalha['turnos'] + 1;
        $batalha['mensagens'] = array_merge($batalha['mensagens'] ?? [], $mensagens);
        return $batalha;
    }

    /**
     * Marca a batalha como encerrada por fuga; o controller trata o salvamento do HP.
     */
    public static function resolverFuga(array $batalha): array
    {
        if (!empty($batalha['finalizada'])) {
            throw new RuntimeException('Esta batalha já foi encerrada.');
        }
        $batalha['finalizada'] = true;
        $batalha['resultado'] = 'derrota';
        $batalha['fugiu'] = true;
        $batalha['mensagens'] = array_merge(
            $batalha['mensagens'] ?? [],
            ['Você fugiu da batalha.']
        );
        return $batalha;
    }

    /** Escolhe e executa imediatamente a ação automática do líder. */
    private static function executarAcaoComputador(array &$batalha, array &$mensagens): void
    {
        $jogador = self::pokemonAtivo($batalha, 'jogador');
        $adversario = self::pokemonAtivo($batalha, 'adversario');
        if ($jogador === null || $adversario === null) {
            return;
        }
        $movimento = self::escolherMovimentoComputador($adversario, $jogador);
        self::executarAcao($batalha, 'adversario', $movimento, $mensagens);
        self::avancarPokemonsDesmaiados($batalha, $mensagens);
        self::definirResultadoSeAcabou($batalha, $mensagens);
    }

    /**
     * Calcula o multiplicador de tipo; em Pokémon com dois tipos, os fatores se multiplicam.
     */
    public static function calcularEfetividade(string $tipoAtaque, array $tiposDefensor): float
    {
        $ataque = strtolower($tipoAtaque);
        $multiplicador = 1.0;

        foreach ($tiposDefensor as $tipoDefensor) {
            $defesa = strtolower((string) $tipoDefensor);
            if (in_array($ataque, self::IMUNIDADES[$defesa] ?? [], true)) {
                return 0.0;
            }
            if (in_array($ataque, self::FRAQUEZAS[$defesa] ?? [], true)) {
                $multiplicador *= 2.0;
            } elseif (in_array($ataque, self::RESISTENCIAS[$defesa] ?? [], true)) {
                $multiplicador *= 0.5;
            }
        }

        return $multiplicador;
    }

    /**
     * Calcula o dano físico ou especial, aplicando estágios, queimadura, STAB e tipo.
     *
     * O resultado respeita o mínimo de dano do projeto e usa como teto metade
     * do HP máximo do alvo (com o mesmo limite mínimo de 8 HP).
     */
    public static function calcularDano(array $atacante, array $alvo, array $movimento): int
    {
        $efetividade = self::calcularEfetividade(
            (string) $movimento['tipo'],
            $alvo['tipos']
        );
        if ($efetividade === 0.0 || (int) $movimento['potencia'] <= 0) {
            return 0;
        }

        $fisico = $movimento['categoria'] === 'fisico';
        $nomeAtaque = $fisico ? 'forca_ataque' : 'ataque_especial';
        $nomeDefesa = $fisico ? 'defesa' : 'defesa_especial';
        $estagioAtaque = $fisico ? 'ataque' : 'ataque_especial';
        $estagioDefesa = $fisico ? 'defesa' : 'defesa_especial';

        $ataque = max(
            1.0,
            (float) $atacante[$nomeAtaque]
                * self::multiplicadorEstagio((int) $atacante['estagios'][$estagioAtaque])
        );
        if ($fisico && $atacante['status'] === 'queimadura') {
            $ataque *= 0.5;
        }
        $defesa = max(
            1.0,
            (float) $alvo[$nomeDefesa]
                * self::multiplicadorEstagio((int) $alvo['estagios'][$estagioDefesa])
        );
        $stab = in_array(
            strtolower((string) $movimento['tipo']),
            array_map('strtolower', $atacante['tipos']),
            true
        ) ? 1.5 : 1.0;

        // Fórmula adaptada do projeto: potência × ataque ÷ (4 × defesa) × STAB × efetividade.
        $dano = (int) round(
            (int) $movimento['potencia']
            * $ataque
            / (4 * $defesa)
            * $stab
            * $efetividade
        );
        $limite = max(
            self::DANO_MINIMO_GOLPE,
            (int) floor((int) $alvo['hp_maximo'] / 2)
        );

        return min($limite, max(self::DANO_MINIMO_GOLPE, $dano));
    }

    /** Valida e aplica um movimento, incluindo acerto, dano e seus efeitos. */
    private static function executarAcao(
        array &$batalha,
        string $lado,
        array $movimento,
        array &$mensagens
    ): void {
        $outroLado = $lado === 'jogador' ? 'adversario' : 'jogador';
        $indiceAtivo = (int) $batalha[$lado]['ativo'];
        $indiceAlvo = (int) $batalha[$outroLado]['ativo'];
        $ator =& $batalha[$lado]['equipe'][$indiceAtivo];
        $alvo =& $batalha[$outroLado]['equipe'][$indiceAlvo];

        if ((int) $ator['hp'] <= 0 || (int) $alvo['hp'] <= 0) {
            return;
        }

        if (!self::podeAgir($ator, $mensagens)) {
            return;
        }

        $nomeMovimento = (string) $movimento['nome'];
        $mensagens[] = $ator['nome'] . ' usou ' . $nomeMovimento . '.';

        if (!self::acertou($ator, $movimento)) {
            $mensagens[] = $nomeMovimento . ' errou o alvo.';
            return;
        }

        if ($movimento['efeito'] === 'proteger') {
            if ($ator['protecao_usada']) {
                $mensagens[] = $ator['nome'] . ' já usou sua proteção nesta batalha.';
                return;
            }
            $ator['protegido'] = true;
            $ator['protecao_usada'] = true;
            $mensagens[] = $ator['nome'] . ' se protegeu neste turno.';
            return;
        }

        if ($alvo['protegido']) {
            $mensagens[] = $alvo['nome'] . ' bloqueou o movimento.';
            return;
        }

        if ((int) $movimento['potencia'] > 0) {
            $dano = self::calcularDano($ator, $alvo, $movimento);
            if ($dano === 0) {
                $mensagens[] = $alvo['nome'] . ' é imune ao tipo ' . $movimento['tipo'] . '.';
                return;
            }

            $efetividade = self::calcularEfetividade(
                (string) $movimento['tipo'],
                $alvo['tipos']
            );
            $alvo['hp'] = max(0, (int) $alvo['hp'] - $dano);
            $mensagens[] = $alvo['nome'] . ' recebeu ' . $dano . ' de dano.';
            if ($efetividade > 1.0) {
                $mensagens[] = 'Foi super efetivo!';
            } elseif ($efetividade < 1.0) {
                $mensagens[] = 'Não foi muito efetivo.';
            }

            if ($movimento['efeito'] === 'cura_dano') {
                $cura = max(1, (int) floor($dano / 2));
                $hpAnterior = (int) $ator['hp'];
                $ator['hp'] = min(
                    (int) $ator['hp_maximo'],
                    $hpAnterior + $cura
                );
                $mensagens[] = $ator['nome'] . ' recuperou '
                    . ((int) $ator['hp'] - $hpAnterior) . ' HP.';
            }

            if ((int) $alvo['hp'] > 0) {
                self::aplicarEfeito($ator, $alvo, $movimento, $mensagens);
            }
            return;
        }

        self::aplicarEfeito($ator, $alvo, $movimento, $mensagens);
    }

    /**
     * Aplica efeitos secundários de movimentos, como status, cura e alterações de atributos.
     */
    private static function aplicarEfeito(
        array &$ator,
        array &$alvo,
        array $movimento,
        array &$mensagens
    ): void {
        $efeito = (string) $movimento['efeito'];
        if ($efeito === 'nenhum' || $efeito === 'cura_dano' || $efeito === 'proteger') {
            return;
        }

        if (
            (int) $movimento['chance_efeito'] < 100
            && random_int(1, 100) > (int) $movimento['chance_efeito']
        ) {
            return;
        }

        $efeitosDeStatus = [
            'queimadura' => 'queimadura',
            'veneno' => 'veneno',
            'toxico' => 'toxico',
            'paralisia' => 'paralisia',
            'sono' => 'sono',
            'congelamento' => 'congelamento',
            'confusao' => 'confusao',
        ];
        if (isset($efeitosDeStatus[$efeito])) {
            self::aplicarStatus($alvo, $efeitosDeStatus[$efeito], $mensagens);
            return;
        }

        if ($efeito === 'flinch') {
            $alvo['recuo'] = true;
            $mensagens[] = $alvo['nome'] . ' hesitou e pode perder sua ação.';
            return;
        }

        if ($efeito === 'curar') {
            if ($ator['cura_usada']) {
                $mensagens[] = $ator['nome'] . ' já usou sua recuperação nesta batalha.';
                return;
            }
            $cura = max(1, (int) ceil((int) $ator['hp_maximo'] / 2));
            $hpAnterior = (int) $ator['hp'];
            $ator['hp'] = min((int) $ator['hp_maximo'], $hpAnterior + $cura);
            if ((int) $ator['hp'] > $hpAnterior) {
                $ator['cura_usada'] = true;
                $mensagens[] = $ator['nome'] . ' recuperou '
                    . ((int) $ator['hp'] - $hpAnterior) . ' HP.';
            } else {
                $mensagens[] = $ator['nome'] . ' já está com HP completo.';
            }
            return;
        }

        if ($efeito === 'ataque_up') {
            self::alterarEstagio($ator, 'ataque', 1, $mensagens);
        } elseif ($efeito === 'defesa_up') {
            self::alterarEstagio($ator, 'defesa', 1, $mensagens);
        } elseif ($efeito === 'defesa_especial_up') {
            self::alterarEstagio($ator, 'defesa_especial', 1, $mensagens);
        } elseif ($efeito === 'velocidade_up') {
            self::alterarEstagio($ator, 'velocidade', 1, $mensagens);
        } elseif ($efeito === 'defesa_down') {
            self::alterarEstagio($alvo, 'defesa', -1, $mensagens);
        } elseif ($efeito === 'defesa_especial_down') {
            self::alterarEstagio($alvo, 'defesa_especial', -1, $mensagens);
        } elseif ($efeito === 'precisao_down') {
            self::alterarEstagio($alvo, 'precisao', -1, $mensagens);
        } elseif ($efeito === 'velocidade_down') {
            self::alterarEstagio($alvo, 'velocidade', -1, $mensagens);
        }
    }

    /**
     * Aplica uma condição temporária, respeitando imunidades e o limite de um status por vez.
     */
    private static function aplicarStatus(array &$alvo, string $status, array &$mensagens): void
    {
        if ($alvo['status'] !== null) {
            $mensagens[] = $alvo['nome'] . ' já está sob uma condição de status.';
            return;
        }

        if (
            ($status === 'queimadura' && in_array('fogo', array_map('strtolower', $alvo['tipos']), true))
            || (
                in_array($status, ['veneno', 'toxico'], true)
                && (
                    in_array('veneno', array_map('strtolower', $alvo['tipos']), true)
                    || in_array('aco', array_map('strtolower', $alvo['tipos']), true)
                )
            )
            || (
                $status === 'congelamento'
                && in_array('gelo', array_map('strtolower', $alvo['tipos']), true)
            )
        ) {
            $mensagens[] = $alvo['nome'] . ' não pode receber essa condição.';
            return;
        }

        $alvo['status'] = $status;
        $alvo['status_turnos'] = $status === 'sono'
            ? random_int(1, 2)
            : ($status === 'confusao' ? 2 : 0);
        $alvo['toxico_contagem'] = $status === 'toxico' ? 1 : 0;
        $mensagens[] = $alvo['nome'] . ' recebeu a condição: '
            . self::nomeStatus($status) . '.';
    }

    /**
     * Decide se o Pokémon consegue agir após verificar sono, paralisia, congelamento e confusão.
     */
    private static function podeAgir(array &$pokemon, array &$mensagens): bool
    {
        if ($pokemon['recuo']) {
            $pokemon['recuo'] = false;
            $mensagens[] = $pokemon['nome'] . ' perdeu a ação por hesitar.';
            return false;
        }

        if ($pokemon['status'] === 'sono') {
            $pokemon['status_turnos']--;
            $mensagens[] = $pokemon['nome'] . ' está dormindo e não agiu.';
            if ($pokemon['status_turnos'] <= 0) {
                $pokemon['status'] = null;
                $mensagens[] = $pokemon['nome'] . ' acordou.';
            }
            return false;
        }

        if ($pokemon['status'] === 'congelamento') {
            if (random_int(1, 100) <= 20) {
                $pokemon['status'] = null;
                $mensagens[] = $pokemon['nome'] . ' descongelou.';
            } else {
                $mensagens[] = $pokemon['nome'] . ' continua congelado.';
                return false;
            }
        }

        if (
            $pokemon['status'] === 'paralisia'
            && random_int(1, 100) <= 25
        ) {
            $mensagens[] = $pokemon['nome'] . ' está paralisado e perdeu a ação.';
            return false;
        }

        if ($pokemon['status'] === 'confusao') {
            $pokemon['status_turnos']--;
            $perdeuAcao = random_int(1, 100) <= 50;
            if ($perdeuAcao) {
                $dano = max(1, (int) ceil((int) $pokemon['hp_maximo'] / 8));
                $pokemon['hp'] = max(0, (int) $pokemon['hp'] - $dano);
                $mensagens[] = $pokemon['nome'] . ' se machucou na confusão e perdeu a ação.';
            } else {
                $mensagens[] = $pokemon['nome'] . ' conseguiu agir apesar da confusão.';
            }
            if ($pokemon['status_turnos'] <= 0) {
                $pokemon['status'] = null;
                $mensagens[] = $pokemon['nome'] . ' saiu da confusão.';
            }
            return (int) $pokemon['hp'] > 0 && !$perdeuAcao;
        }

        return true;
    }

    /** Sorteia o acerto usando a precisão do movimento e o estágio de precisão do atacante. */
    private static function acertou(array $atacante, array $movimento): bool
    {
        $precisao = (int) $movimento['precisao']
            * self::multiplicadorEstagio((int) $atacante['estagios']['precisao']);
        $precisao = max(1, min(100, (int) round($precisao)));

        return random_int(1, 100) <= $precisao;
    }

    /**
     * Seleciona o movimento do líder pela pontuação de dano, cura ou efeito útil.
     */
    private static function escolherMovimentoComputador(array $atacante, array $alvo): array
    {
        if ($atacante['movimentos'] === []) {
            throw new RuntimeException('O Pokémon adversário não possui movimentos cadastrados.');
        }

        $melhorMovimento = $atacante['movimentos'][0];
        $melhorPontuacao = -1;
        foreach ($atacante['movimentos'] as $movimento) {
            if ((int) $movimento['potencia'] > 0) {
                $pontuacao = self::calcularDano($atacante, $alvo, $movimento);
            } elseif (
                $movimento['efeito'] === 'curar'
                && empty($atacante['cura_usada'])
                && (int) $atacante['hp'] <= (int) $atacante['hp_maximo'] / 2
            ) {
                $pontuacao = 30;
            } elseif (
                in_array(
                    $movimento['efeito'],
                    ['sono', 'paralisia', 'confusao', 'toxico'],
                    true
                )
                && $alvo['status'] === null
            ) {
                $pontuacao = 8 + ((int) $movimento['chance_efeito'] / 100);
            } elseif (
                in_array(
                    $movimento['efeito'],
                    ['ataque_up', 'defesa_up', 'defesa_especial_up', 'velocidade_up'],
                    true
                )
                && (int) $atacante['estagios'][
                    [
                        'ataque_up' => 'ataque',
                        'defesa_up' => 'defesa',
                        'defesa_especial_up' => 'defesa_especial',
                        'velocidade_up' => 'velocidade',
                    ][$movimento['efeito']]
                ] < 2
            ) {
                $pontuacao = 5;
            } else {
                $pontuacao = 0;
            }

            if ($pontuacao > $melhorPontuacao) {
                $melhorMovimento = $movimento;
                $melhorPontuacao = $pontuacao;
            }
        }

        return $melhorMovimento;
    }

    /** Confirma que o movimento enviado pertence ao Pokémon ativo. */
    private static function buscarMovimento(array $pokemon, int $idMovimento): array
    {
        foreach ($pokemon['movimentos'] as $movimento) {
            if ((int) $movimento['id_movimento'] === $idMovimento) {
                return $movimento;
            }
        }

        throw new InvalidArgumentException('O movimento escolhido não pertence ao Pokémon ativo.');
    }

    /**
     * Aplica o dano de fim de turno; o contador de Toxic aumenta a cada aplicação.
     */
    private static function aplicarDanoResidual(
        array &$batalha,
        string $lado,
        array &$mensagens
    ): void {
        $pokemon =& $batalha[$lado]['equipe'][(int) $batalha[$lado]['ativo']];
        if (!in_array($pokemon['status'], ['queimadura', 'veneno', 'toxico'], true)) {
            return;
        }

        if ($pokemon['status'] === 'toxico') {
            $contador = max(1, (int) $pokemon['toxico_contagem']);
            $dano = max(
                1,
                (int) ceil((int) $pokemon['hp_maximo'] * $contador / 8)
            );
            $pokemon['toxico_contagem'] = $contador + 1;
        } else {
            $dano = max(1, (int) ceil((int) $pokemon['hp_maximo'] / 8));
        }

        $pokemon['hp'] = max(0, (int) $pokemon['hp'] - $dano);
        $mensagens[] = $pokemon['nome'] . ' perdeu ' . $dano
            . ' HP pela condição de ' . self::nomeStatus($pokemon['status']) . '.';
    }

    /** Altera um estágio de atributo sem permitir valores abaixo de -2 ou acima de +2. */
    private static function alterarEstagio(
        array &$pokemon,
        string $atributo,
        int $alteracao,
        array &$mensagens
    ): void {
        $antes = (int) $pokemon['estagios'][$atributo];
        $pokemon['estagios'][$atributo] = max(-2, min(2, $antes + $alteracao));
        if ($pokemon['estagios'][$atributo] === $antes) {
            $mensagens[] = 'O atributo de ' . $pokemon['nome'] . ' não pode mudar mais.';
            return;
        }

        $mensagens[] = 'O atributo ' . self::nomeEstagio($atributo)
            . ' de ' . $pokemon['nome']
            . ($alteracao > 0 ? ' aumentou.' : ' diminuiu.');
    }

    /** Calcula a velocidade após estágios e a redução causada por paralisia. */
    private static function velocidadeEfetiva(array $pokemon): float
    {
        $multiplicador = self::multiplicadorEstagio(
            (int) $pokemon['estagios']['velocidade']
        );
        if ($pokemon['status'] === 'paralisia') {
            $multiplicador *= 0.5;
        }

        return (int) $pokemon['velocidade'] * $multiplicador;
    }

    /** Converte um estágio entre -2 e +2 no multiplicador usado nos cálculos do combate. */
    private static function multiplicadorEstagio(int $estagio): float
    {
        if ($estagio >= 0) {
            return 1.0 + (0.25 * $estagio);
        }

        return 1.0 / (1.0 + (0.25 * abs($estagio)));
    }

    /** Retorna o Pokémon apontado como ativo ou null se a equipe acabou. */
    private static function pokemonAtivo(array $batalha, string $lado): ?array
    {
        $indice = (int) ($batalha[$lado]['ativo'] ?? -1);
        return $batalha[$lado]['equipe'][$indice] ?? null;
    }

    /** Avança automaticamente para o próximo Pokémon com HP em cada equipe. */
    private static function avancarPokemonsDesmaiados(array &$batalha, array &$mensagens): void
    {
        foreach (['jogador', 'adversario'] as $lado) {
            $indice = (int) $batalha[$lado]['ativo'];
            $equipe = $batalha[$lado]['equipe'];
            if (isset($equipe[$indice]) && (int) $equipe[$indice]['hp'] <= 0) {
                $mensagens[] = $equipe[$indice]['nome'] . ' desmaiou.';
                $indice++;
                while (
                    isset($equipe[$indice])
                    && (int) $equipe[$indice]['hp'] <= 0
                ) {
                    $indice++;
                }
                $batalha[$lado]['ativo'] = $indice;

                if (isset($equipe[$indice])) {
                    $mensagens[] = $equipe[$indice]['nome'] . ' entrou na batalha.';
                }
            }
        }
    }

    /** Encerra a batalha quando o índice ativo ultrapassa uma das equipes. */
    private static function definirResultadoSeAcabou(array &$batalha, array &$mensagens): void
    {
        $jogadorAcabou = (int) $batalha['jogador']['ativo']
            >= count($batalha['jogador']['equipe']);
        $adversarioAcabou = (int) $batalha['adversario']['ativo']
            >= count($batalha['adversario']['equipe']);

        if ($jogadorAcabou || $adversarioAcabou) {
            $batalha['finalizada'] = true;
            $batalha['resultado'] = $jogadorAcabou ? 'derrota' : 'vitoria';
            $mensagens[] = $jogadorAcabou
                ? 'Todos os seus Pokémon desmaiaram. Você perdeu a batalha.'
                : 'A equipe do líder foi derrotada. Você venceu a batalha!';
        }
    }

    /** Traduz as chaves internas de status para os nomes usados no registro. */
    private static function nomeStatus(string $status): string
    {
        $nomes = [
            'queimadura' => 'queimadura',
            'veneno' => 'envenenamento',
            'toxico' => 'envenenamento grave',
            'paralisia' => 'paralisia',
            'sono' => 'sono',
            'congelamento' => 'congelamento',
            'confusao' => 'confusão',
        ];

        return $nomes[$status] ?? $status;
    }

    /** Traduz as chaves internas de atributos para os nomes exibidos ao jogador. */
    private static function nomeEstagio(string $atributo): string
    {
        $nomes = [
            'ataque' => 'Ataque',
            'defesa' => 'Defesa',
            'ataque_especial' => 'Ataque Especial',
            'defesa_especial' => 'Defesa Especial',
            'velocidade' => 'Velocidade',
            'precisao' => 'Precisão',
        ];

        return $nomes[$atributo] ?? $atributo;
    }
}
