<?php

namespace App\Models;

use CodeIgniter\Model;

/** Qué versiones llevaba una placa (PiezaPlacaModel), una fila por versión. */
class PiezaPlacaVersionModel extends Model
{
    protected $table         = 'piezas_placas_versiones';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['placa_id', 'version_id'];
}
