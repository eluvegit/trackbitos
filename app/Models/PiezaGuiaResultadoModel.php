<?php

namespace App\Models;

use CodeIgniter\Model;

/** Pieza a la que lleva una combinación completa de opciones de una guía. */
class PiezaGuiaResultadoModel extends Model
{
    protected $table         = 'piezas_guia_resultados';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['guia_id', 'combinacion', 'variante_id'];
}
