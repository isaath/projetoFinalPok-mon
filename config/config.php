<?php

try{
    $db = new PDO("mysql:host=Localhost;dbname=bancodedados_pokefish", "root", "");
    $db->setAttribute(PDO: :ATTR_ERRMODE, PDO: :ERROMODE_EXCEPTION);
}
catch(PDOException $e){
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
    //die -> a conexão com o banco de dados é um requisito 
    // obrigatório para a execução do restante da aplicação
    // ent o die exibe a mensagem de erro e dps já encerra a execução do php!
}