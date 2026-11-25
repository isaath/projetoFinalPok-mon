<?php

namespace App\Models;

use UnexpectedValueException;

/** Cria o subtipo de Pokémon correspondente ao tipo principal do registro. */
class PokemonTipoFactory
{
    /** @var array<string, class-string<Pokemon>> */
    private const CLASSES_POR_TIPO = [
        'Agua' => PokemonAgua::class,
        'Eletrico' => PokemonEletrico::class,
        'Fogo' => PokemonFogo::class,
        'Inseto' => PokemonInseto::class,
        'Pedra' => PokemonPedra::class,
        'Planta' => PokemonPlanta::class,
        'Psiquico' => PokemonPsiquico::class,
        'Terra' => PokemonTerra::class,
        'Veneno' => PokemonVeneno::class,
    ];

    /** Instancia a classe de tipo e preenche seus atributos a partir da consulta. */
    public static function criar(array $dados): Pokemon
    {
        $tipoPrincipal = $dados['tipos'][0] ?? null;
        if (!is_string($tipoPrincipal) || !isset(self::CLASSES_POR_TIPO[$tipoPrincipal])) {
            throw new UnexpectedValueException(
                'Não há uma subclasse de Pokémon cadastrada para o tipo principal recebido.'
            );
        }

        $classePokemon = self::CLASSES_POR_TIPO[$tipoPrincipal];
        $pokemon = new $classePokemon();
        $pokemon->setId((int) $dados['id_pokemon']);
        $pokemon->setNome((string) $dados['nome']);
        $pokemon->setHp((int) $dados['hp']);
        $pokemon->setHpMaximo((int) $dados['hp_maximo']);
        $pokemon->setForcaAtaque((int) $dados['forca_ataque']);
        $pokemon->setDefesa((int) $dados['defesa']);
        $pokemon->setAtaqueEspecial((int) $dados['ataque_especial']);
        $pokemon->setDefesaEspecial((int) $dados['defesa_especial']);
        $pokemon->setVelocidade((int) $dados['velocidade']);
        $pokemon->setTipos($dados['tipos']);

        return $pokemon;
    }
}
