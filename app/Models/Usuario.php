<?php

class Usuario
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function autenticar(string $email, string $senha): ?array
    {
        // A consulta preparada impede que os dados do formulário alterem o SQL.
        $consulta = $this->db->prepare(
            'SELECT id_usuario, nome, email, senha
             FROM usuarios
             WHERE email = :email
             LIMIT 1'
        );
        $consulta->execute(['email' => $email]);

        $usuario = $consulta->fetch();

        if (
            !is_array($usuario)
            || !isset($usuario['senha'])
            || !password_verify($senha, $usuario['senha'])
        ) {
            return null;
        }

        // A senha não é incluída nos dados que serão guardados na sessão.
        return [
            'id_usuario' => (int) $usuario['id_usuario'],
            'nome' => (string) $usuario['nome'],
            'email' => (string) $usuario['email'],
        ];
    }

    public function cadastrar(string $nome, string $email, string $senha): bool
    {
        $consulta = $this->db->prepare(
            'SELECT id_usuario
             FROM usuarios
             WHERE email = :email
             LIMIT 1'
        );
        $consulta->execute(['email' => $email]);

        if ($consulta->fetchColumn() !== false) {
            return false;
        }

        // Guarda somente o hash da senha, nunca o texto digitado pelo usuário.
        $hashSenha = password_hash($senha, PASSWORD_DEFAULT);

        $insercao = $this->db->prepare(
            'INSERT INTO usuarios (nome, email, senha, dinheiro, data_cadastro)
             VALUES (:nome, :email, :senha, 0, CURRENT_TIMESTAMP)'
        );
        $insercao->execute([
            'nome' => $nome,
            'email' => $email,
            'senha' => $hashSenha,
        ]);

        return true;
    }
}
