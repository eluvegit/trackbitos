<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Fotos de referencia del original (medidas con calibre, ángulos) más
 * notas de texto. Cuelgan de la familia, no de la variante (spec 1.1):
 * son comunes a todas las líneas de diseño y no deben duplicarse.
 */
class PiezaReferenciaModel extends Model
{
    protected $table         = 'piezas_referencias';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'familia_id', 'ruta_imagen', 'hash_imagen', 'tamano_bytes', 'notas', 'subida_en',
    ];

    // ruta_imagen no lleva 'required': el alta es en dos pasos (insertar
    // para obtener el id, guardar el fichero con ese id en el nombre,
    // actualizar la ruta) — ver Web::subirReferencia. Vacía es el estado
    // intermedio válido, nunca el final.
    protected $validationRules = [
        'familia_id'  => 'required|is_natural_no_zero',
        'ruta_imagen' => 'permit_empty|max_length[500]',
    ];
}
