// Na apresentação: Views PHP marcam as imagens com data-nome; este script
// consulta a PokeAPI e atualiza somente a apresentação, sem gravar no banco.
class CarregadorImagensPokemon {
    // Guarda os elementos com nome definido em data-nome para processá-los depois.
    constructor() {
        this.imagens = document.querySelectorAll('.imagem-pokemon[data-nome]');
    }

    // Inicia os carregamentos simultaneamente para não esperar uma imagem por vez.
    async carregar() {
        const tarefas = Array.from(this.imagens).map((imagem) => {
            return this.carregarImagem(imagem);
        });

        await Promise.all(tarefas);
    }

    // Busca o sprite de um elemento e atualiza a tela, com fallback se algo falhar.
    async carregarImagem(elementoImagem) {
        // Converte o nome exibido em um slug compatível com a URL da PokeAPI.
        const nome = elementoImagem.dataset.nome
            .toLowerCase()
            .replace(/['’]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
        const url = `https://pokeapi.co/api/v2/pokemon/${encodeURIComponent(nome)}`;
        const placeholder = elementoImagem.nextElementSibling;

        try {
            // fetch é assíncrono: a página continua utilizável enquanto a API responde.
            const resposta = await fetch(url);

            if (!resposta.ok) {
                throw new Error(`A PokeAPI respondeu com HTTP ${resposta.status}.`);
            }

            // Converte o JSON e acessa os sprites enviados pela API.
            const pokemon = await resposta.json();
            const sprites = pokemon.sprites;

            // Usa primeiro a arte oficial e, se estiver ausente, o sprite frontal padrão.
            const imagem =
                sprites.other?.['official-artwork']?.front_default
                || sprites.front_default;

            if (!imagem) {
                throw new Error(`A API não encontrou uma imagem para ${nome}.`);
            }

            elementoImagem.src = imagem;
            // Esconde o marcador visual quando uma imagem válida foi carregada.
            if (placeholder instanceof HTMLElement) {
                placeholder.hidden = true;
            }
        } catch (erro) {
            // Uma falha de imagem não impede o restante da tela de funcionar.
            console.error(`Não foi possível carregar a imagem de ${nome}:`, erro);
            elementoImagem.hidden = true;
            if (placeholder instanceof HTMLElement) {
                placeholder.hidden = false;
            }
        }
    }
}

// Cria o carregador e inicia a busca depois que este script é executado.
const carregador = new CarregadorImagensPokemon();
carregador.carregar();



