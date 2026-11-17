class CarregadorImagensPokemon {


    //encontra as imagens q tem a classe imagem-pokemon e o atributo data-nome
    constructor() {
        this.imagens = document.querySelectorAll('.imagem-pokemon[data-nome]');
    }

    async carregar() {
        const tarefas = Array.from(this.imagens).map((imagem) => {
            return this.carregarImagem(imagem);
        });

        await Promise.all(tarefas);
    }

    async carregarImagem(elementoImagem) {
        const nome = elementoImagem.dataset.nome
            .toLowerCase()
            .replace(/['’]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
        const url = `https://pokeapi.co/api/v2/pokemon/${encodeURIComponent(nome)}`;
        const placeholder = elementoImagem.nextElementSibling;

        try {
            const resposta = await fetch(url);  //fethc(url) -> consulta a API e a pág não precisa ficar parada, pq é assíncrono

            if (!resposta.ok) {
                throw new Error(`A PokeAPI respondeu com HTTP ${resposta.status}.`);
            }

            const pokemon = await resposta.json();  //resposta.json() -> transforma a resposta JSON em um obj do JS
            const sprites = pokemon.sprites;  // contém as imagens 

            const imagem =
                sprites.other?.['official-artwork']?.front_default
                || sprites.front_default;

            if (!imagem) {
                throw new Error(`A API não encontrou uma imagem para ${nome}.`);
            }

            elementoImagem.src = imagem;
            if (placeholder instanceof HTMLElement) {
                placeholder.hidden = true;
            }
        } catch (erro) {
            console.error(`Não foi possível carregar a imagem de ${nome}:`, erro);
            elementoImagem.hidden = true;
            if (placeholder instanceof HTMLElement) {
                placeholder.hidden = false;
            }
        }
    }
}

const carregador = new CarregadorImagensPokemon();
carregador.carregar();



