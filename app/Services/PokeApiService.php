<?php

// Serviço responsável por consumir a PokeAPI e transformar os dados em um formato
// que a aplicação pode usar para exibir Pokémon e suas evoluções.
class PokeApiService
{
    // Base da API pública da PokeAPI.
    private const BASE_URL = 'https://pokeapi.co/api/v2';

    // Lista de Pokémon iniciais que a aplicação deseja consultar.
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

    // Busca os Pokémon iniciais e retorna um array pronto para a View exibir.
    public function listarPokemons(): array
    {
        // Array que guarda os resultados finais.
        $pokemons = [];

        // Cache para evitar repetir a mesma cadeia evolutiva em vários Pokémon.
        $cadeiasEvolutivas = [];

        // Percorre cada nome da lista de iniciais.
        foreach (self::POKEMONS_INICIAIS as $nome) {
            // Busca os dados do Pokémon na PokeAPI pelo nome.
            $detalhes = $this->get(self::BASE_URL . '/pokemon/' . $nome);

            // Pega a URL da espécie associada ao Pokémon.
            $urlEspecie = $detalhes['species']['url'] ?? null;

            // Garante que a API retornou um campo válido antes de continuar.
            if (!is_string($urlEspecie)) {
                throw new UnexpectedValueException('A PokeAPI não retornou a espécie de ' . $nome . '.');
            }

            // Consulta os detalhes da espécie para obter a cadeia evolutiva.
            $especie = $this->get($urlEspecie);
            $urlCadeia = $especie['evolution_chain']['url'] ?? null;

            // Valida se a URL da cadeia evolutiva existe.
            if (!is_string($urlCadeia)) {
                throw new UnexpectedValueException('A PokeAPI não retornou a cadeia evolutiva de ' . $nome . '.');
            }

            // Se essa cadeia evolutiva ainda não foi carregada, consulta e guarda em cache.
            if (!isset($cadeiasEvolutivas[$urlCadeia])) {
                $cadeia = $this->get($urlCadeia);
                $cadeiasEvolutivas[$urlCadeia] = $this->obterCaminhosEvolutivos(
                    $cadeia['chain'] ?? []
                );
            }

            // Organiza as estatísticas do Pokémon em um array indexado pelo nome do atributo.
            $estatisticas = [];

            foreach ($detalhes['stats'] ?? [] as $estatistica) {
                if (isset($estatistica['stat']['name'], $estatistica['base_stat'])) {
                    $estatisticas[$estatistica['stat']['name']] = $estatistica['base_stat'];
                }
            }

            // Monta o resultado final para essa espécie.
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

        // Retorna a lista completa de Pokémon pronta para ser usada por um Controller/View.
        return $pokemons;
    }

    /**
     * Consulta a PokeAPI e retorna a URL HTTPS do sprite de uma espécie.
     */
    public function obterImagem(string $nome): string
    {
        // Normaliza nomes com espaços ou pontuação para o formato de endereço da API.
        $slug = strtolower(trim($nome));
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $slug), '-');

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

    // Recebe a cadeia evolutiva da PokeAPI e transforma em caminhos de evolução legíveis.
    private function obterCaminhosEvolutivos(array $especie, array $caminhoAtual = []): array
    {
        // Recupera o nome da espécie atual.
        $nome = $especie['species']['name'] ?? null;

        // Se o nome não vier corretamente, a API respondeu de forma inesperada.
        if (!is_string($nome)) {
            throw new UnexpectedValueException('A cadeia evolutiva retornada pela PokeAPI está inválida.');
        }

        // Adiciona o nome atual ao caminho da evolução.
        $caminhoAtual[] = $nome;
        $proximasEvolucoes = $especie['evolves_to'] ?? [];

        // Se não houver próxima evolução, retorna o caminho atual final.
        if ($proximasEvolucoes === []) {
            return [$caminhoAtual];
        }

        // Aqui guarda todos os caminhos possíveis até a evolução final.
        $caminhos = [];

        // Explora cada ramo da árvore evolutiva.
        foreach ($proximasEvolucoes as $proximaEspecie) {
            if (!is_array($proximaEspecie)) {
                throw new UnexpectedValueException('Uma etapa da cadeia evolutiva está inválida.');
            }

            // Junta os caminhos recursivos vindos de cada ramo.
            $caminhos = array_merge(
                $caminhos,
                $this->obterCaminhosEvolutivos($proximaEspecie, $caminhoAtual)
            );
        }

        // Retorna todos os caminhos completos.
        return $caminhos;
    }

    // Realiza uma requisição HTTP genérica para uma URL da PokeAPI.
    private function get(string $url): array
    {
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

        // Converte resposta JSON em array associativo.
        $dados = json_decode($resposta, true, 512, JSON_THROW_ON_ERROR);

        // Verifica se o retorno realmente é um array.
        if (!is_array($dados)) {
            throw new UnexpectedValueException('A resposta da PokeAPI não tem o formato esperado.');
        }

        // Retorna os dados decodificados.
        return $dados;
    }
}