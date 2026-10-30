<?php

namespace App\Models;

use CodeIgniter\Model;

/** Albarán de salida: una foto (opcional) = un albarán. Ver App\Services\PiezaInventario. */
class PiezaSalidaModel extends Model
{
    protected $table         = 'piezas_salidas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'fecha', 'destino_id', 'estado', 'ruta_imagen', 'hash_imagen', 'tamano_bytes', 'nota',
        'confirmada_en', 'creado_en', 'actualizado_en',
    ];
}
