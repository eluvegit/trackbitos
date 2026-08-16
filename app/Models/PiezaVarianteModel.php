<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Una línea de diseño dentro de una familia (torso-recto, pose-futbolista).
 * Numeración de versiones propia desde v001. origen_version_id solo se
 * rellena cuando la variante nace de "derivar variante" (sección 3 spec).
 */
class PiezaVarianteModel extends Model
{
    protected $table         = 'piezas_variantes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = '';

    protected $allowedFields = ['familia_id', 'nombre', 'origen_version_id', 'notas'];

    protected $validationRules = [
        'familia_id' => 'required|integer',
        'nombre'     => 'required|max_length[150]',
    ];
}
