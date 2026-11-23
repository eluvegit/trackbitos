<?php

namespace App\Models;

use CodeIgniter\Model;

/** Opción de ropa/look de un perfil de modelo (texto + foto opcional). */
class SesionModeloRopaModel extends Model
{
    protected $table         = 'sesion_modelo_ropa';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['modelo_id', 'texto', 'imagen', 'orden'];
}
