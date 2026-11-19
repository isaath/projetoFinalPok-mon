class CarregadorImagensPocoes {
    constructor() {
        this.imagens = document.querySelectorAll('.imagem-item[data-nome]');
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
        const url = `https://pokeapi.co/api/v2/item/${encodeURIComponent(nome)}`;

        try {
            const resposta = await fetch(url);

            if (!resposta.ok) {
                throw new Error(`A PokeAPI respondeu com HTTP ${resposta.status}.`);
            }

            const item = await resposta.json();
            const imagem = item.sprites?.default;

            if (!imagem) {
                throw new Error(`A API não encontrou uma imagem para ${nome}.`);
            }

            elementoImagem.src = imagem;
            elementoImagem.hidden = false;
        } catch (erro) {
            console.error(`Não foi possível carregar a imagem de ${nome}:`, erro);
            elementoImagem.hidden = true;
        }
    }
}

const carregador = new CarregadorImagensPocoes();
carregador.carregar();



