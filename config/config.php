
<?php

//Models e Controllers possam reutilizar a mesma conexão
//Assim, sempre que precisares acessar o banco, podes chamar getConnection(). 
// A função reutiliza a conexão durante a execução da requisição.

function getConnection(): PDO
{
    static $db = null;

    if ($db === null) {
        try {
            $db = new PDO(
                "mysql:host=localhost;dbname=bancodedados_pokefish;charset=utf8mb4",
                "root",
                ""
            );

            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            die("Erro na conexão com o banco de dados: " . $e->getMessage());
        }
    }

    return $db;
}
