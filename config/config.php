
<?php

// Este arquivo centraliza a conexão com o banco de dados.
// A ideia é reutilizar sempre a mesma ligação durante a execução da aplicação,
// evitando abrir várias conexões desnecessárias para cada operação.

function getConnection(): PDO
{
    // A variável estática mantém a conexão em memória durante a requisição.
    static $db = null;

    // Se ainda não existe uma conexão, ela é criada agora.
    if ($db === null) {
        try {
            // Cria a conexão PDO com o MySQL local.
            // host: servidor do banco (localhost)
            // dbname: nome do banco utilizado pelo projeto
            // charset: define a codificação UTF-8 para aceitar caracteres especiais.
            $db = new PDO(
                "mysql:host=localhost;dbname=bancodedados_pokefish;charset=utf8mb4",
                "root",
                ""
            );

            // Força que erros de SQL gerem exceções em vez de apenas avisos.
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Faz com que os resultados vindos do banco venham como arrays associativos,
            // por exemplo: ['id_usuario' => 1, 'nome' => 'Ana'].
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // Registra o detalhe técnico no servidor, sem expor dados internos na página.
            error_log('Falha ao conectar ao banco de dados: ' . $e->getMessage());
            http_response_code(500);
            die('Não foi possível conectar ao banco de dados. Verifique a configuração local.');
        }
    }

    // Retorna a conexão pronta para uso pelos Models e Controllers.
    return $db;
}
