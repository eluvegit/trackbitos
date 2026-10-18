<?php

namespace App\Models;

use CodeIgniter\Model;

/** Opción de un paso de guía (p. ej. "Mujer"). */
class PiezaGuiaOpcionModel extends Model
{
    protected $table         = 'piezas_guia_opciones';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['paso_id', 'nombre', 'orden'];
}
