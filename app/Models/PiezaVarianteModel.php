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

    protected $allowedFields = ['familia_id', 'nombre', 'sku', 'origen_version_id', 'notas', 'enlace_original'];

    protected $validationRules = [
        'familia_id'      => 'required|integer',
        'nombre'          => 'required|max_length[150]',
        // Unicidad comprobada aparte en PiezaService::actualizarSku(), con
        // mensaje que dice a qué variante pertenece ya el SKU — más útil
        // que el genérico de is_unique.
        'sku'             => 'permit_empty|max_length[50]',
        'enlace_original' => 'permit_empty|max_length[500]',
    ];
}
