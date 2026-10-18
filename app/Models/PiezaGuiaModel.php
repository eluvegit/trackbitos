<?php

namespace App\Models;

use CodeIgniter\Model;

/** Guía de elección del TPV de inventario (ver App\Services\PiezaGuias). */
class PiezaGuiaModel extends Model
{
    protected $table         = 'piezas_guias';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = '';

    protected $allowedFields = ['nombre', 'icono', 'orden', 'activa'];
}
