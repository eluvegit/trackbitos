<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Imágenes de referencia de un pedido: lo que pide el cliente, cuando aún
 * no hay pieza en el catálogo donde guardarlo. Alta y baja inmediatas, igual
 * que las fotos de una placa (PiezaPlacaImagenModel).
 */
class PiezaPedidoImagenModel extends Model
{
    protected $table         = 'piezas_pedido_imagenes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'pedido_id', 'ruta_imagen', 'hash_imagen', 'tamano_bytes', 'notas', 'orden', 'subida_en',
    ];

    // Alta en dos pasos (insertar para tener el id, guardar el fichero con
    // ese id en el nombre, actualizar la ruta) — mismo patrón que las de placa.
    protected $validationRules = [
        'pedido_id'   => 'required|is_natural_no_zero',
        'ruta_imagen' => 'permit_empty|max_length[500]',
    ];

    public function siguienteOrden(int $pedidoId): int
    {
        $fila = $this->where('pedido_id', $pedidoId)->selectMax('orden')->first();

        return ((int) ($fila['orden'] ?? -1)) + 1;
    }

    public function dePedido(int $pedidoId): array
    {
        return $this->where('pedido_id', $pedidoId)->orderBy('orden')->orderBy('id')->findAll();
    }
}
