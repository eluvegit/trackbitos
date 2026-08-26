<?php

namespace App\Models;

use CodeIgniter\Model;

class PiezaPedidoLineaModel extends Model
{
    protected $table         = 'piezas_pedidos_lineas';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['pedido_id', 'variante_id', 'sku', 'descripcion_libre', 'cantidad', 'cantidad_completada', 'notas'];

    protected $validationRules = [
        'pedido_id'         => 'required|is_natural_no_zero',
        'sku'               => 'permit_empty|max_length[50]',
        'descripcion_libre' => 'permit_empty|max_length[150]',
        'cantidad'          => 'required|is_natural_no_zero',
    ];
}
