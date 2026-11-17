<?php

class PaginaController
{
    public function inicio(): void
    {
        require __DIR__ . '/../Views/layouts/painel_inicial.php';
    }

    public function sobre(): void
    {
        require __DIR__ . '/../Views/layouts/sobre.php';
    }
}
