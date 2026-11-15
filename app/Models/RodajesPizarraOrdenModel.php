<?php

namespace App\Models;

use CodeIgniter\Model;

/** Orden de las tarjetas en cada vista de la pizarra (salvo la narrativa, que usa rodajes_escenas.orden). */
class RodajesPizarraOrdenModel extends Model
{
    protected $table         = 'rodajes_pizarra_orden';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['escena_id', 'vista', 'posicion'];
}
