<?php

// Classe utilitária para ajudar a View a receber dados e renderizar templates.
class View
{
    // Armazena os valores que serão enviados para o template da página.
    private array $dados = [];

    // Construtor da classe: inicializa o objeto, mas neste caso não faz nada extra.
    public function __construct()
    {
    }

    // Guarda um nome de variável e seu valor para uso posterior na View.
    public function atribuirValor($var, $valor)
    {
        $this->dados[$var] = $valor;
    }

    // Carrega um arquivo de template e faz os dados ficarem acessíveis como variáveis.
    public function mostrarNaTela($template)
    {
        // Monta o caminho completo do arquivo PHP da View.
        $caminho = __DIR__ . '/' . $template . '.php';

        // Se o arquivo não existir, lança uma exceção para avisar o problema.
        if (!file_exists($caminho)) {
            throw new Exception("View não encontrada: " . $template);
        }

        // Transforma cada item do array em uma variável local disponível no template.
        extract($this->dados);

        // Inclui o arquivo da View para renderizar a página.
        require $caminho;
    }
}