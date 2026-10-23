<?php

class View
{
    private array $dados = [];

    public function __construct()  //prepara a classe quanod ela é criada
    {
    }

    public function atribuirValor($var, $valor)  //guarda as informacoes q serao usadas na tela
    {
        $this->dados[$var] = $valor;
    }

    public function mostrarNaTela($template)  //carrega o arq correspondente a tela
    {
        $caminho = __DIR__ . '/' . $template . '.php';

        if (!file_exists($caminho)) {
            throw new Exception("View não encontrada: " . $template);
        }

        extract($this->dados);  //transforma os valores guardados em variáveis q o template consegue acessar

        require $caminho;
    }
}