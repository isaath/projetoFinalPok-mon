<?php
// Valores de exemplo para testar a verificação de hash de senha do PHP.
$hash  = 'COLE_AQUI_O_HASH_DO_BANCO';
$senha = 'A_SENHA_QUE_VOCE_DIGITOU';

// Mostra o tamanho do hash informado para ajudar a identificar erros de cópia.
var_dump(strlen($hash));
// Verifica se a senha em texto corresponde ao hash armazenado.
var_dump(password_verify($senha, $hash));