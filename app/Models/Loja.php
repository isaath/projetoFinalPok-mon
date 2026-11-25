<?php

namespace App\Models;

use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

/** Consulta o catálogo e executa compras persistentes para cada usuário. */
class Loja
{
    private PDO $db;

    /** Recebe a conexão usada para consultar saldo, itens e inventário. */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Consulta o saldo persistido do usuário e sinaliza se a conta não existir. */
    public function obterSaldo(int $idUsuario): int
    {
        $consulta = $this->db->prepare(
            'SELECT dinheiro FROM usuarios WHERE id_usuario = :id_usuario'
        );
        $consulta->execute(['id_usuario' => $idUsuario]);
        $saldo = $consulta->fetchColumn();

        if ($saldo === false) {
            throw new RuntimeException('O usuário da loja não foi encontrado.');
        }

        return (int) $saldo;
    }

    /** Lista o catálogo junto à quantidade que o usuário já possui de cada item. */
    public function listarItens(int $idUsuario): array
    {
        $consulta = $this->db->prepare(
            'SELECT item.id_item, item.nome, item.tipo, item.preco,
                    item.descricao, item.valor_efeito,
                    COALESCE(inventario.quantidade, 0) AS quantidade
             FROM itens_loja AS item
             LEFT JOIN inventario
                ON inventario.id_item = item.id_item
               AND inventario.id_usuario = :id_usuario
             ORDER BY item.id_item'
        );
        $consulta->execute(['id_usuario' => $idUsuario]);
        $itens = $consulta->fetchAll(PDO::FETCH_ASSOC);

        // PDO pode retornar números como texto; converte campos numéricos antes da View.
        foreach ($itens as &$item) {
            foreach (['id_item', 'preco', 'valor_efeito', 'quantidade'] as $campo) {
                $item[$campo] = (int) $item[$campo];
            }
        }
        unset($item);

        return $itens;
    }

    /**
     * Compra uma unidade do item em uma única transação.
     *
     * Débito, inventário e histórico são confirmados juntos ou revertidos juntos.
     */
    public function comprarItem(int $idUsuario, int $idItem): array
    {
        if ($idUsuario < 1 || $idItem < 1) {
            throw new InvalidArgumentException('Usuário ou item inválido.');
        }

        $this->db->beginTransaction();
        try {
            // Bloqueia o saldo durante a compra para impedir gasto concorrente das mesmas moedas.
            $consultaSaldo = $this->db->prepare(
                'SELECT dinheiro FROM usuarios
                 WHERE id_usuario = :id_usuario
                 FOR UPDATE'
            );
            $consultaSaldo->execute(['id_usuario' => $idUsuario]);
            $saldo = $consultaSaldo->fetchColumn();
            if ($saldo === false) {
                throw new RuntimeException('O usuário da loja não foi encontrado.');
            }

            $consultaItem = $this->db->prepare(
                'SELECT id_item, nome, preco
                 FROM itens_loja
                 WHERE id_item = :id_item
                 FOR UPDATE'
            );
            $consultaItem->execute(['id_item' => $idItem]);
            $item = $consultaItem->fetch(PDO::FETCH_ASSOC);
            if ($item === false) {
                throw new InvalidArgumentException('Esse item não existe na loja.');
            }

            $preco = (int) $item['preco'];
            if ((int) $saldo < $preco) {
                throw new InvalidArgumentException('Saldo insuficiente para comprar esse item.');
            }

            $debitar = $this->db->prepare(
                'UPDATE usuarios
                 SET dinheiro = dinheiro - :preco
                 WHERE id_usuario = :id_usuario
                   AND dinheiro >= :preco'
            );
            $debitar->execute([
                'preco' => $preco,
                'id_usuario' => $idUsuario,
            ]);
            if ($debitar->rowCount() !== 1) {
                throw new InvalidArgumentException('O saldo mudou. Tente a compra novamente.');
            }

            // Se o usuário já possui esse item, incrementa a quantidade em vez de criar outra linha.
            $adicionarInventario = $this->db->prepare(
                'INSERT INTO inventario (id_usuario, id_item, quantidade)
                 VALUES (:id_usuario, :id_item, 1)
                 ON DUPLICATE KEY UPDATE quantidade = quantidade + 1'
            );
            $adicionarInventario->execute([
                'id_usuario' => $idUsuario,
                'id_item' => $idItem,
            ]);

            $registrarCompra = $this->db->prepare(
                'INSERT INTO compras (quantidade, valor_unitario, id_usuario, id_item)
                 VALUES (1, :preco, :id_usuario, :id_item)'
            );
            $registrarCompra->execute([
                'preco' => $preco,
                'id_usuario' => $idUsuario,
                'id_item' => $idItem,
            ]);

            $this->db->commit();
            return [
                'nome' => (string) $item['nome'],
                'saldo' => (int) $saldo - $preco,
            ];
        } catch (Throwable $exception) {
            // Nenhuma compra fica parcialmente gravada caso uma das operações falhe.
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }
}
