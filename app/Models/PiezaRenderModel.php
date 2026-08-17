<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Imágenes de render de una versión concreta: el resultado visual de esa
 * iteración. Cuelgan de la versión, no de la familia, para poder ver la
 * evolución en el historial (a diferencia de las referencias, que son
 * comunes a toda la familia — ver PiezaReferenciaModel).
 */
class PiezaRenderModel extends Model
{
    protected $table         = 'piezas_renders';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'version_id', 'ruta_imagen', 'hash_imagen', 'tamano_bytes', 'notas', 'subida_en',
    ];

    // ruta_imagen no lleva 'required': mismo alta en dos pasos que
    // PiezaReferenciaModel — ver Web::subirRender.
    protected $validationRules = [
        'version_id'  => 'required|is_natural_no_zero',
        'ruta_imagen' => 'permit_empty|max_length[500]',
    ];
}
