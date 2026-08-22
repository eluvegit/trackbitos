<?php

namespace App\Models;

use CodeIgniter\Model;

class PiezaPedidoLineaModel extends Model
{
    protected $table         = 'piezas_pedidos_lineas';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['pedido_id', 'variante_id', 'sku', 'cantidad', 'cantidad_completada', 'notas'];

    protected $validationRules = [
        'pedido_id' => 'required|is_natural_no_zero',
        'sku'       => 'required|max_length[50]',
        'cantidad'  => 'required|is_natural_no_zero',
    ];
}
