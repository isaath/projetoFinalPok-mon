<?php

/** Carrega as telas públicas de apresentação do projeto. */
class PaginaController
{
    /** Exibe a página inicial antes do acesso à área autenticada. */
    public function inicio(): void
    {
        require __DIR__ . '/../Views/layouts/painel_inicial.php';
    }

    /** Exibe a página com a proposta e a história do jogo. */
    public function sobre(): void
    {
        require __DIR__ . '/../Views/layouts/sobre.php';
    }
}
