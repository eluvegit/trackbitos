<?php

namespace App\Models;

use CodeIgniter\Model;

class PiezaPedidoLineaModel extends Model
{
    protected $table         = 'piezas_pedidos_lineas';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['pedido_id', 'orden', 'variante_id', 'sku', 'descripcion_libre', 'cantidad', 'cantidad_completada', 'hecha', 'notas'];

    protected $validationRules = [
        'pedido_id'         => 'required|is_natural_no_zero',
        'sku'               => 'permit_empty|max_length[50]',
        'descripcion_libre' => 'permit_empty|max_length[150]',
        'cantidad'          => 'required|is_natural_no_zero',
    ];

    /** Siguiente hueco al final del pedido, para que una línea nueva caiga la última. */
    public function siguienteOrden(int $pedidoId): int
    {
        $fila = $this->selectMax('orden', 'max')->where('pedido_id', $pedidoId)->get()->getRowArray();

        return (int) ($fila['max'] ?? 0) + 1;
    }
}
