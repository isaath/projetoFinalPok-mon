<?php

namespace App\Exceptions;

use InvalidArgumentException;

/**
 * Exceção de regra de negócio lançada por Loja e tratada por BolichoController.
 * Na apresentação, explique que ela permite diferenciar saldo insuficiente de
 * erros de infraestrutura, como uma falha de PDO.
 */
class SaldoInsuficienteException extends InvalidArgumentException
{
}
