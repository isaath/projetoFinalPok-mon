<?php

// Modelo responsável por regras relacionadas a usuários do sistema.
class Usuario
{
    // Armazena a conexão com o banco para executar consultas SQL.
    private PDO $db;

    // Construtor recebe a conexão e salva em uma propriedade da classe.
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Verifica se há um usuário com o e-mail informado e se a senha corresponde.
    public function autenticar(string $email, string $senha): ?array
    {
        // A consulta preparada impede que dados do formulário modifiquem o SQL.
        $consulta = $this->db->prepare(
            'SELECT id_usuario, nome, email, senha
             FROM usuarios
             WHERE email = :email
             LIMIT 1'
        );

        // Executa a consulta com o e-mail recebido do formulário.
        $consulta->execute(['email' => $email]);

        // Busca o primeiro resultado encontrado.
        $usuario = $consulta->fetch();

        // Verifica se o usuário foi encontrado e se a senha confere.
        if (
            !is_array($usuario)
            || !isset($usuario['senha'])
            || !password_verify($senha, $usuario['senha'])
        ) {
            // Se não existir ou a senha estiver errada, retorna null.
            return null;
        }

        // Retorna somente os dados públicos do usuário, sem incluir a senha.
        return [
            'id_usuario' => (int) $usuario['id_usuario'],
            'nome' => (string) $usuario['nome'],
            'email' => (string) $usuario['email'],
        ];
    }

    // Cadastra um novo usuário, validando se o e-mail já existe.
    public function cadastrar(string $nome, string $email, string $senha): bool
    {
        // Busca se já existe algum usuário com esse e-mail.
        $consulta = $this->db->prepare(
            'SELECT id_usuario
             FROM usuarios
             WHERE email = :email
             LIMIT 1'
        );
        $consulta->execute(['email' => $email]);

        // Se houver registro, impede o cadastro duplicado.
        if ($consulta->fetchColumn() !== false) {
            return false;
        }

        // Criptografa a senha usando password_hash, nunca gravando texto puro.
        $hashSenha = password_hash($senha, PASSWORD_DEFAULT);

        // Insere o novo usuário no banco com saldo inicial zerado e data atual.
        $insercao = $this->db->prepare(
            'INSERT INTO usuarios (nome, email, senha, dinheiro, data_cadastro)
             VALUES (:nome, :email, :senha, 0, CURRENT_TIMESTAMP)'
        );
        $insercao->execute([
            'nome' => $nome,
            'email' => $email,
            'senha' => $hashSenha,
        ]);

        // Retorna true quando o cadastro foi concluído com sucesso.
        return true;
    }
}
