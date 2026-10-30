<?php

namespace App\Models;

use CodeIgniter\Model;

class PiezaSalidaLineaModel extends Model
{
    protected $table         = 'piezas_salida_lineas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'salida_id', 'variante_id', 'descripcion', 'cantidad', 'origen', 'x', 'y', 'orden',
    ];
}
