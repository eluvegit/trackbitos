<?php

namespace App\Models;

use CodeIgniter\Model;

/** Paso de una guía (p. ej. "Cogida"), en orden. */
class PiezaGuiaPasoModel extends Model
{
    protected $table         = 'piezas_guia_pasos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['guia_id', 'nombre', 'orden'];
}
