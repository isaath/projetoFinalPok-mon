// Na apresentação: encontra os formulários data-compra-item de bolicho.php,
// envia JSON à rota atendida por BolichoController e atualiza saldo/estoque.
// O servidor e o Model Loja continuam responsáveis por validar e efetivar a compra.
class CompradorBolicho {
    constructor() {
        this.saldo = document.querySelector('[data-saldo-pokepila]');
        this.mensagem = document.querySelector('#mensagem-compra-async');
        this.forms = Array.from(document.querySelectorAll('form[data-compra-item]'));
        this.compraEmAndamento = false;

        this.forms.forEach((formulario) => {
            formulario.addEventListener('submit', (evento) => {
                this.comprar(evento, formulario);
            });
        });
    }

    // Impede o envio tradicional para que o Controller devolva JSON à página.
    async comprar(evento, formulario) {
        evento.preventDefault();

        if (this.compraEmAndamento) {
            return;
        }

        this.compraEmAndamento = true;
        this.alterarBotoes(true);
        this.mensagem.hidden = true;

        try {
            const dados = Object.fromEntries(new FormData(formulario).entries());
            const resposta = await fetch(formulario.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify(dados),
            });
            const resultado = await resposta.json();

            if (!resposta.ok || resultado.success !== true) {
                throw new Error(resultado.message || 'Não foi possível concluir a compra.');
            }

            const saldoAtual = Number(resultado.saldo);
            const quantidadeAtual = Number(resultado.quantidade);
            if (!Number.isFinite(saldoAtual) || !Number.isFinite(quantidadeAtual)) {
                throw new Error('O servidor retornou dados inválidos para atualizar a loja.');
            }

            this.saldo.dataset.saldoPokepila = String(saldoAtual);
            this.saldo.textContent = saldoAtual.toLocaleString('pt-BR');

            const quantidade = formulario
                .closest('.card-item')
                .querySelector('[data-quantidade-item]');
            quantidade.textContent = String(quantidadeAtual);

            this.mostrarMensagem(resultado.message, false);
        } catch (erro) {
            console.error('Falha ao comprar item no Bolicho:', erro);
            this.mostrarMensagem(erro.message, true);
        } finally {
            this.compraEmAndamento = false;
            this.alterarBotoes(false);
        }
    }

    // Evita duas compras simultâneas e respeita o saldo atualizado pelo servidor.
    alterarBotoes(bloquear) {
        const saldoAtual = Number(this.saldo.dataset.saldoPokepila);

        this.forms.forEach((formulario) => {
            const botao = formulario.querySelector('button[type="submit"]');
            botao.disabled = bloquear || saldoAtual < Number(formulario.dataset.preco);
        });
    }

    // Exibe o retorno ao usuário e diferencia mensagens de sucesso e erro.
    mostrarMensagem(texto, ocorreuErro) {
        this.mensagem.textContent = texto;
        this.mensagem.className = ocorreuErro ? 'mensagem-erro' : 'mensagem';
        this.mensagem.setAttribute('role', ocorreuErro ? 'alert' : 'status');
        this.mensagem.hidden = false;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('[data-saldo-pokepila]')) {
        new CompradorBolicho();
    }
});
