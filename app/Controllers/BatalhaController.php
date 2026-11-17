<?php

use App\Models\Batalha;

require_once __DIR__ . '/../Services/PokeApiService.php';

class BatalhaController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function index(): void
    {
        if (!isset($_SESSION['usuario']['id_usuario'])) {
            header('Location: index.php?rota=login');
            exit;
        }

        $idUsuario = filter_var(
            $_SESSION['usuario']['id_usuario'],
            FILTER_VALIDATE_INT
        );

        if ($idUsuario === false || $idUsuario < 1) {
            unset($_SESSION['usuario']);
            header('Location: index.php?rota=login');
            exit;
        }

        $dadosPrevia = null;
        $imagemJogador = null;
        $imagemAdversario = null;
        $erroBatalha = null;
        $erroImagem = null;

        try {
            $dadosPrevia = (new Batalha($this->db))->carregarPrevia($idUsuario, 1);
        } catch (PDOException | RuntimeException $exception) {
            error_log('Erro ao carregar a prévia da batalha: ' . $exception->getMessage());
            http_response_code(500);
            $erroBatalha = 'Não foi possível carregar os dados da batalha agora.';
        }

        if (
            $dadosPrevia !== null
            && $dadosPrevia['pokemon_jogador'] !== null
            && $dadosPrevia['pokemon_adversario'] !== null
        ) {
            try {
                $servico = new PokeApiService();
                $imagemJogador = $servico->obterImagem(
                    $dadosPrevia['pokemon_jogador']['nome']
                );
                $imagemAdversario = $servico->obterImagem(
                    $dadosPrevia['pokemon_adversario']['nome_pokemon']
                );
            } catch (RuntimeException | JsonException $exception) {
                error_log('Erro ao carregar imagens da batalha: ' . $exception->getMessage());
                $erroImagem = 'Não foi possível carregar as imagens pela PokeAPI agora.';
            }
        }

        define('POKEBAGUAL_BATALHA_VIEW', true);
        require __DIR__ . '/../Views/batalha/index.php';
    }
}