// Carrega os ícones dos itens da PokeAPI para os cards do Bolicho.
class CarregadorImagensPocoes {
    // Localiza imagens identificadas com o nome do item no atributo data-nome.
    constructor() {
        this.imagens = document.querySelectorAll('.imagem-item[data-nome]');
    }

    // Inicia os pedidos em paralelo para que os cards carreguem sem espera sequencial.
    async carregar() {
        const tarefas = Array.from(this.imagens).map((imagem) => {
            return this.carregarImagem(imagem);
        });

        await Promise.all(tarefas);
    }

    // Consulta a API para um item e atualiza seu elemento de imagem.
    async carregarImagem(elementoImagem) {
        // Normaliza o nome para o padrão usado no endereço da API.
        const nome = elementoImagem.dataset.nome
            .toLowerCase()
            .replace(/['’]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
        const url = `https://pokeapi.co/api/v2/item/${encodeURIComponent(nome)}`;

        try {
            // fetch retorna uma Promise enquanto o navegador continua responsivo.
            const resposta = await fetch(url);

            if (!resposta.ok) {
                throw new Error(`A PokeAPI respondeu com HTTP ${resposta.status}.`);
            }

            // Converte JSON em objeto e extrai o sprite padrão do item.
            const item = await resposta.json();
            const imagem = item.sprites?.default;

            if (!imagem) {
                throw new Error(`A API não encontrou uma imagem para ${nome}.`);
            }

            elementoImagem.src = imagem;
            elementoImagem.hidden = false;
        } catch (erro) {
            // A imagem é opcional; uma falha não impede a loja de funcionar.
            console.error(`Não foi possível carregar a imagem de ${nome}:`, erro);
            elementoImagem.hidden = true;
        }
    }
}

// Cria o carregador e começa a buscar as imagens desta página.
const carregador = new CarregadorImagensPocoes();
carregador.carregar();



