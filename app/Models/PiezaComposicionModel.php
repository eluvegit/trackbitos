<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * "Compuesta de": qué otras piezas (en una versión concreta) están
 * presentes en la escena de esta variante. Puramente informativo — ver la
 * cabecera de la migración para por qué está aparte de `origen_version_id`.
 */
class PiezaComposicionModel extends Model
{
    protected $table         = 'piezas_composiciones';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = '';

    protected $allowedFields = ['variante_id', 'version_componente_id', 'notas'];
}
