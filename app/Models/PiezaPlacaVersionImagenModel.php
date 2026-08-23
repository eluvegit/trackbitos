<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Capturas de cómo quedó una pieza concreta dentro de una placa (fila de
 * PiezaPlacaVersionModel): la mejor posición impresa, con notas de cómo
 * estaba puesta y un veredicto rápido. Varias por fila y en momentos
 * distintos — igual que PiezaPlacaImagenModel pero a nivel de pieza, no de
 * plataforma entera.
 */
class PiezaPlacaVersionImagenModel extends Model
{
    protected $table         = 'piezas_placa_version_imagenes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'placa_version_id', 'ruta_imagen', 'hash_imagen', 'tamano_bytes', 'notas', 'resultado', 'orden', 'subida_en',
    ];

    public const RESULTADOS = [
        'bien'    => 'Bien',
        'regular' => 'Regular',
        'mal'     => 'Mal',
    ];

    // ruta_imagen no lleva 'required': el alta es en dos pasos (insertar
    // para obtener el id, guardar el fichero con ese id en el nombre,
    // actualizar la ruta) — mismo patrón que PiezaPlacaImagenModel.
    protected $validationRules = [
        'placa_version_id' => 'required|is_natural_no_zero',
        'ruta_imagen'      => 'permit_empty|max_length[500]',
        'resultado'        => 'permit_empty|in_list[bien,regular,mal]',
    ];

    public function siguienteOrden(int $placaVersionId): int
    {
        $fila = $this->where('placa_version_id', $placaVersionId)->selectMax('orden')->first();

        return ((int) ($fila['orden'] ?? -1)) + 1;
    }
}
