<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * La pieza conceptual (cuerpo, brazo, casco). Las referencias e imágenes
 * de compartir cuelgan de la familia, no de la variante: son comunes a
 * todas sus variantes.
 */
class PiezaFamiliaModel extends Model
{
    protected $table         = 'piezas_familias';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = '';

    protected $allowedFields = ['nombre', 'categoria_id', 'notas', 'borrado_en'];

    protected $validationRules = [
        'nombre' => 'required|max_length[150]',
    ];
}
