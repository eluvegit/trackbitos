<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Una placa descargada: fecha y nombre (autogenerado, editable). El
 * contenido real —qué versiones llevaba— vive en PiezaPlacaVersionModel.
 */
class PiezaPlacaModel extends Model
{
    protected $table         = 'piezas_placas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = '';

    protected $allowedFields = ['nombre'];

    protected $validationRules = [
        'nombre' => 'required|max_length[150]',
    ];
}
