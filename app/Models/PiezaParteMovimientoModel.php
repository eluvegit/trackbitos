<?php

namespace App\Models;

use CodeIgniter\Model;

class PiezaParteMovimientoModel extends Model
{
    protected $table         = 'piezas_partes_movimientos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['parte_id', 'delta', 'motivo', 'nota', 'placa_version_id', 'creado_en'];
}
