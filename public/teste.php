<?php
$hash  = 'COLE_AQUI_O_HASH_DO_BANCO';
$senha = 'A_SENHA_QUE_VOCE_DIGITOU';

var_dump(strlen($hash));
var_dump(password_verify($senha, $hash));