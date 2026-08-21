<?php

namespace App\Models;

use CodeIgniter\Model;

class PiezaPedidoModel extends Model
{
    protected $table         = 'piezas_pedidos';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    public const ESTADOS = ['nuevo', 'en_produccion', 'completado', 'cancelado'];

    protected $allowedFields = [
        'origen', 'estado', 'referencia_externa', 'notas', 'creado_en', 'actualizado_en',
    ];

    protected $validationRules = [
        'origen' => 'required|max_length[30]',
        'estado' => 'permit_empty|in_list[nuevo,en_produccion,completado,cancelado]',
    ];

    public function conLineas(int $pedidoId): ?array
    {
        $pedido = $this->find($pedidoId);
        if (!$pedido) {
            return null;
        }

        $pedido['lineas'] = (new PiezaPedidoLineaModel())->where('pedido_id', $pedidoId)->findAll();

        return $pedido;
    }

    public function recientes(int $limite = 50): array
    {
        return $this->orderBy('creado_en', 'DESC')->findAll($limite);
    }
}
