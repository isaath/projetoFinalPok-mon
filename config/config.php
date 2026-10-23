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


// minha ia integrada ao código solicitou a mudança de código para:


<?php

$host = 'localhost';
$nomeBanco = 'bancodedados_pokefish';
$usuario = 'root';
$senha = '';

$dsn = "mysql:host=$host;dbname=$nomeBanco;charset=utf8mb4";

try {
    return new PDO($dsn, $usuario, $senha, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    throw new RuntimeException(
        'Não foi possível conectar ao banco de dados.',
        0,
        $e
    );
}