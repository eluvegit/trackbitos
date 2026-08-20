<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Qué versiones llevaba una placa (PiezaPlacaModel), una fila por versión,
 * con cuántas copias iban de cada una y qué se probó en esa pieza en concreto
 * (orientación, soportes) — lo que es de la placa entera va en la placa.
 */
class PiezaPlacaVersionModel extends Model
{
    protected $table         = 'piezas_placas_versiones';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['placa_id', 'version_id', 'cantidad', 'notas'];
}
