<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * De qué pedidos salió una placa: los que se cargaron con "Cargar a la
 * placa" mientras se montaba. Varios por placa, porque cargar un pedido se
 * suma a lo que ya hubiera. Solo procedencia — no cuadra qué cubre la placa
 * de cada pedido, eso se sigue juzgando a mano.
 */
class PiezaPlacaPedidoModel extends Model
{
    protected $table         = 'piezas_placa_pedidos';
    protected $primaryKey    = 'placa_id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['placa_id', 'pedido_id'];

    /** @param int[] $pedidoIds */
    public function enlazar(int $placaId, array $pedidoIds): void
    {
        foreach (array_unique(array_map('intval', $pedidoIds)) as $pedidoId) {
            if ($pedidoId > 0) {
                $this->db->table($this->table)->ignore(true)
                    ->insert(['placa_id' => $placaId, 'pedido_id' => $pedidoId]);
            }
        }
    }

    /** @return int[] */
    public function deLaPlaca(int $placaId): array
    {
        return array_map('intval', array_column($this->where('placa_id', $placaId)->orderBy('pedido_id')->findAll(), 'pedido_id'));
    }

    /**
     * Pedidos de todas las placas de una vez, para el histórico entero.
     *
     * @return array<int, int[]> placa_id => [pedido_id, ...]
     */
    public function porPlaca(): array
    {
        $mapa = [];
        foreach ($this->orderBy('pedido_id')->findAll() as $fila) {
            $mapa[(int) $fila['placa_id']][] = (int) $fila['pedido_id'];
        }

        return $mapa;
    }
}
