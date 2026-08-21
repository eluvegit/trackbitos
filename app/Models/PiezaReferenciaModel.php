<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Fotos de referencia del original (medidas con calibre, ángulos) más
 * notas de texto. Cuelgan de la variante en la que se suben (`variante_id`)
 * — cada una se ve solo ahí, no en el resto de variantes de la pieza.
 * `familia_id` se conserva para poder purgarlas junto con la familia y para
 * las de antes de este cambio, que no tienen `variante_id` (null: se siguen
 * viendo desde cualquier variante de esa familia, como compatibilidad).
 */
class PiezaReferenciaModel extends Model
{
    protected $table         = 'piezas_referencias';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'familia_id', 'variante_id', 'ruta_imagen', 'hash_imagen', 'tamano_bytes', 'notas', 'subida_en',
    ];

    // ruta_imagen no lleva 'required': el alta es en dos pasos (insertar
    // para obtener el id, guardar el fichero con ese id en el nombre,
    // actualizar la ruta) — ver Web::subirReferencia. Vacía es el estado
    // intermedio válido, nunca el final.
    protected $validationRules = [
        'familia_id'  => 'required|is_natural_no_zero',
        'ruta_imagen' => 'permit_empty|max_length[500]',
    ];

    /**
     * Las de esta variante, más las de antes de este cambio (sin
     * `variante_id`, compartidas todavía por toda la familia).
     */
    public function deVariante(int $familiaId, int $varianteId): array
    {
        return $this->where('familia_id', $familiaId)
            ->groupStart()
                ->where('variante_id', $varianteId)
                ->orWhere('variante_id', null)
            ->groupEnd()
            ->orderBy('subida_en', 'DESC')
            ->findAll();
    }
}
