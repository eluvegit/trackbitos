<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Capturas de la plataforma del laminador para una placa (fase 43): de
 * dónde partía la impresión y cómo quedó orientada/soportada. Una placa
 * compleja puede llevar varias, igual que las referencias de una variante
 * — alta y baja son inmediatas (su propio botón), no parte del guardado
 * general de la bitácora.
 */
class PiezaPlacaImagenModel extends Model
{
    protected $table         = 'piezas_placa_imagenes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'placa_id', 'ruta_imagen', 'hash_imagen', 'tamano_bytes', 'notas', 'orden', 'subida_en',
    ];

    // ruta_imagen no lleva 'required': el alta es en dos pasos (insertar
    // para obtener el id, guardar el fichero con ese id en el nombre,
    // actualizar la ruta) — mismo patrón que PiezaReferenciaModel.
    protected $validationRules = [
        'placa_id'    => 'required|is_natural_no_zero',
        'ruta_imagen' => 'permit_empty|max_length[500]',
    ];

    public function siguienteOrden(int $placaId): int
    {
        $fila = $this->where('placa_id', $placaId)->selectMax('orden')->first();

        return ((int) ($fila['orden'] ?? -1)) + 1;
    }
}
