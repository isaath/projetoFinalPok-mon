<?php

class PokeApiService
{
    private const BASE_URL = 'https://pokeapi.co/api/v2';
    private const POKEMONS_INICIAIS = [
        'bulbasaur',
        'oddish',
        'bellsprout',
        'charmander',
        'vulpix',
        'growlithe',
        'ponyta',
        'squirtle',
        'psyduck',
    ];

    public function listarPokemons(): array
    {
        $pokemons = [];
        $cadeiasEvolutivas = [];

        foreach (self::POKEMONS_INICIAIS as $nome) {
            $detalhes = $this->get(self::BASE_URL . '/pokemon/' . $nome);
            $urlEspecie = $detalhes['species']['url'] ?? null;

            if (!is_string($urlEspecie)) {
                throw new UnexpectedValueException('A PokeAPI não retornou a espécie de ' . $nome . '.');
            }

            $especie = $this->get($urlEspecie);
            $urlCadeia = $especie['evolution_chain']['url'] ?? null;

            if (!is_string($urlCadeia)) {
                throw new UnexpectedValueException('A PokeAPI não retornou a cadeia evolutiva de ' . $nome . '.');
            }

            if (!isset($cadeiasEvolutivas[$urlCadeia])) {
                $cadeia = $this->get($urlCadeia);
                $cadeiasEvolutivas[$urlCadeia] = $this->obterCaminhosEvolutivos(
                    $cadeia['chain'] ?? []
                );
            }

            $estatisticas = [];

            foreach ($detalhes['stats'] ?? [] as $estatistica) {
                if (isset($estatistica['stat']['name'], $estatistica['base_stat'])) {
                    $estatisticas[$estatistica['stat']['name']] = $estatistica['base_stat'];
                }
            }

            $pokemons[] = [
                'nome' => $detalhes['name'] ?? $nome,
                'imagem' => $detalhes['sprites']['other']['official-artwork']['front_default']
                    ?? $detalhes['sprites']['front_default']
                    ?? null,
                'tipos' => array_map(
                    static fn (array $tipo): string => $tipo['type']['name'] ?? '',
                    $detalhes['types'] ?? []
                ),
                'hp' => $estatisticas['hp'] ?? 0,
                'ataque' => $estatisticas['attack'] ?? 0,
                'defesa' => $estatisticas['defense'] ?? 0,
                'velocidade' => $estatisticas['speed'] ?? 0,
                'caminhos_evolutivos' => $cadeiasEvolutivas[$urlCadeia],
            ];
        }

        return $pokemons;
    }

    private function obterCaminhosEvolutivos(array $especie, array $caminhoAtual = []): array
    {
        $nome = $especie['species']['name'] ?? null;

        if (!is_string($nome)) {
            throw new UnexpectedValueException('A cadeia evolutiva retornada pela PokeAPI está inválida.');
        }

        $caminhoAtual[] = $nome;
        $proximasEvolucoes = $especie['evolves_to'] ?? [];

        if ($proximasEvolucoes === []) {
            return [$caminhoAtual];
        }

        $caminhos = [];

        foreach ($proximasEvolucoes as $proximaEspecie) {
            if (!is_array($proximaEspecie)) {
                throw new UnexpectedValueException('Uma etapa da cadeia evolutiva está inválida.');
            }

            $caminhos = array_merge(
                $caminhos,
                $this->obterCaminhosEvolutivos($proximaEspecie, $caminhoAtual)
            );
        }

        return $caminhos;
    }

    private function get(string $url): array
    {
        $curl = curl_init($url);

        if ($curl === false) {
            throw new RuntimeException('Não foi possível iniciar a requisição à PokeAPI.');
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);

        $resposta = curl_exec($curl);

        if ($resposta === false) {
            $erro = curl_error($curl);
            curl_close($curl);

            throw new RuntimeException('Erro ao consultar a PokeAPI: ' . $erro);
        }

        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException(
                'A PokeAPI respondeu com o status HTTP ' . $status . '.'
            );
        }

        $dados = json_decode($resposta, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($dados)) {
            throw new UnexpectedValueException('A resposta da PokeAPI não tem o formato esperado.');
        }

        return $dados;
    }
}