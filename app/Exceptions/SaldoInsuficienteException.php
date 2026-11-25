<?php

namespace App\Exceptions;

use InvalidArgumentException;

/** Indica que o saldo do jogador não cobre o preço do item solicitado. */
class SaldoInsuficienteException extends InvalidArgumentException
{
}
