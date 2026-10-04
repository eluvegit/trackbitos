<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Catálogo externo de ejercicios (solo lectura desde la app: se rellena con
 * GimnasioCatalogoService::importar). Ver la migración CreateGimnasioCatalogo.
 */
class GimnasioCatalogoModel extends Model
{
    protected $table         = 'gimnasio_catalogo';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'ext_id', 'nombre', 'parte', 'equipo', 'objetivo', 'secundarios',
        'instrucciones_es', 'pasos_es', 'instrucciones_en', 'imagen', 'gif',
    ];
    protected $useTimestamps = true;
}
