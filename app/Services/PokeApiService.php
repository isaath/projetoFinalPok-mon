<?php

// Na apresentação: serviço usado por PokemonController para imagens e por
// TimePokemon no fluxo de evolução. A PokeAPI fornece dados externos; a
// persistência local continua sendo responsabilidade dos Models/PDO.
class PokeApiService
{
    // Base da API pública da PokeAPI.
    private const BASE_URL = 'https://pokeapi.co/api/v2';

    // Evita consultar novamente a mesma URL durante esta requisição PHP.
    private static array $cache = [];

    /**
     * Consulta a PokeAPI e retorna a URL HTTPS do sprite de uma espécie.
     */
    public function obterImagem(string $nome): string
    {
        // Normaliza nomes com espaços ou pontuação para o formato de endereço da API.
        $slug = $this->normalizarSlug($nome);

        if ($slug === '') {
            throw new InvalidArgumentException('O nome do Pokémon não é válido para consulta.');
        }

        $detalhes = $this->get(self::BASE_URL . '/pokemon/' . rawurlencode($slug));
        // Prefere o sprite frontal padrão e usa a arte oficial como alternativa.
        $imagem = $detalhes['sprites']['front_default']
            ?? $detalhes['sprites']['other']['official-artwork']['front_default']
            ?? null;

        if (!is_string($imagem) || substr($imagem, 0, 8) !== 'https://') {
            throw new UnexpectedValueException(
                'A PokeAPI não retornou uma imagem válida para ' . $nome . '.'
            );
        }

        return $imagem;
    }

    /**
     * Consulta na PokeAPI os atributos base e os tipos da espécie evoluída.
     *
     * @return array{
     *     nome:string,
     *     hp:int,
     *     forca_ataque:int,
     *     defesa:int,
     *     ataque_especial:int,
     *     defesa_especial:int,
     *     velocidade:int,
     *     tipos:string[]
     * }
     */
    public function obterAtributosBase(string $nome): array
    {
        $slug = $this->normalizarSlug($nome);

        if ($slug === '') {
            throw new InvalidArgumentException('O nome do Pokémon não é válido para consulta.');
        }

        $detalhes = $this->get(self::BASE_URL . '/pokemon/' . rawurlencode($slug));
        $estatisticas = [];
        foreach ($detalhes['stats'] ?? [] as $estatistica) {
            if (
                isset($estatistica['stat']['name'])
                && is_string($estatistica['stat']['name'])
                && isset($estatistica['base_stat'])
                && is_int($estatistica['base_stat'])
            ) {
                $estatisticas[$estatistica['stat']['name']] = $estatistica['base_stat'];
            }
        }

        $campos = [
            'hp' => 'hp',
            'forca_ataque' => 'attack',
            'defesa' => 'defense',
            'ataque_especial' => 'special-attack',
            'defesa_especial' => 'special-defense',
            'velocidade' => 'speed',
        ];
        $atributos = [];
        foreach ($campos as $campoLocal => $campoApi) {
            if (!isset($estatisticas[$campoApi]) || $estatisticas[$campoApi] < 1) {
                throw new UnexpectedValueException(
                    'A PokeAPI não retornou o atributo ' . $campoApi . ' para ' . $slug . '.'
                );
            }

            $atributos[$campoLocal] = $estatisticas[$campoApi];
        }

        $tipos = [];
        foreach ($detalhes['types'] ?? [] as $tipo) {
            $nomeTipo = $tipo['type']['name'] ?? null;
            if (!is_string($nomeTipo) || $nomeTipo === '') {
                throw new UnexpectedValueException(
                    'A PokeAPI retornou um tipo inválido para ' . $slug . '.'
                );
            }

            $tipos[] = $nomeTipo;
        }

        $nomeApi = $detalhes['name'] ?? null;
        if (!is_string($nomeApi) || $tipos === []) {
            throw new UnexpectedValueException(
                'A PokeAPI não retornou os dados completos de ' . $slug . '.'
            );
        }

        return array_merge(
            ['nome' => $nomeApi],
            $atributos,
            ['tipos' => $tipos]
        );
    }

    /** Converte nomes recebidos para o formato de slug usado nos endereços da API. */
    private function normalizarSlug(string $nome): string
    {
        return trim(
            (string) preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($nome))),
            '-'
        );
    }

    // Realiza uma requisição HTTP genérica para uma URL da PokeAPI.
    private function get(string $url): array
    {
        if (array_key_exists($url, self::$cache)) {
            return self::$cache[$url];
        }

        // Inicia uma conexão cURL para a URL informada.
        $curl = curl_init($url);

        // Caso a inicialização falhe, lança exceção.
        if ($curl === false) {
            throw new RuntimeException('Não foi possível iniciar a requisição à PokeAPI.');
        }

        // Define opções da requisição: retorno em string, timeout e tipo de conteúdo.
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);

        // Executa a requisição e recebe o retorno em texto.
        $resposta = curl_exec($curl);

        // Se curl falhar, gera erro detalhado.
        if ($resposta === false) {
            $erro = curl_error($curl);
            curl_close($curl);

            throw new RuntimeException('Erro ao consultar a PokeAPI: ' . $erro);
        }

        // Captura o código HTTP da resposta para validar se a requisição foi bem-sucedida.
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        // Se a resposta não for 2xx, lança exceção.
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException(
                'A PokeAPI respondeu com o status HTTP ' . $status . '.'
            );
        }

        // Converte resposta JSON em array associativo e informa erros de formato claramente.
        try {
            $dados = json_decode($resposta, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedValueException(
                'A PokeAPI retornou um JSON inválido.',
                0,
                $exception
            );
        }

        // Verifica se o retorno realmente é um array.
        if (!is_array($dados)) {
            throw new UnexpectedValueException('A resposta da PokeAPI não tem o formato esperado.');
        }

        // Guarda somente respostas válidas e reutiliza em consultas repetidas.
        self::$cache[$url] = $dados;

        return $dados;
    }
}