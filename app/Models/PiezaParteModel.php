<?php

namespace App\Models;

use CodeIgniter\Model;

class PiezaParteModel extends Model
{
    protected $table         = 'piezas_partes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['variante_id', 'nombre', 'por_unidad', 'orden', 'creado_en'];
}
