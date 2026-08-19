<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Imágenes de render de una variante. Cuelgan siempre de `variante_id`
 * (existe desde que se crea la pieza, no hace falta haber promocionado
 * nada) y, opcionalmente, de `version_id` cuando se sabe qué iteración
 * concreta salió así — eso es lo que permite verlas también en el
 * historial de esa versión. A diferencia de las referencias (comunes a
 * toda la familia — ver PiezaReferenciaModel), un render es de una
 * variante concreta.
 */
class PiezaRenderModel extends Model
{
    protected $table         = 'piezas_renders';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'variante_id', 'version_id', 'ruta_imagen', 'hash_imagen', 'tamano_bytes', 'notas', 'subida_en',
    ];

    // ruta_imagen no lleva 'required': mismo alta en dos pasos que
    // PiezaReferenciaModel — ver Web::subirRender. version_id es opcional
    // a propósito (fase 31): antes de la primera promoción no hay versión.
    protected $validationRules = [
        'variante_id' => 'required|is_natural_no_zero',
        'ruta_imagen' => 'permit_empty|max_length[500]',
    ];
}
