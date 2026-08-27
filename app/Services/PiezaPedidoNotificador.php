<?php

namespace App\Services;

use App\Models\PiezaFamiliaModel;
use App\Models\PiezaPedidoModel;
use App\Models\PiezaVarianteModel;
use Throwable;

/**
 * Aviso por correo de que ha entrado un pedido nuevo en el módulo de piezas
 * (desde sterclicks o de alta manual), para no depender de ir mirando el
 * tablero de /piezas/pedidos a ver si hay algo que revisar.
 */
class PiezaPedidoNotificador
{
    private const DESTINATARIO = 'eluvemail@gmail.com';

    /**
     * Sin SMTP configurado (email.* en .env) no revienta el flujo que crea
     * el pedido: cualquier fallo al enviar queda solo logueado.
     */
    public function avisarNuevoPedido(int $pedidoId): bool
    {
        $pedido = (new PiezaPedidoModel())->conLineas($pedidoId);
        if (!$pedido) {
            return false;
        }

        $varianteModel = new PiezaVarianteModel();
        $familiaModel = new PiezaFamiliaModel();
        foreach ($pedido['lineas'] as &$linea) {
            $variante = $linea['variante_id'] ? $varianteModel->find($linea['variante_id']) : null;
            $linea['nombreFamilia'] = $variante ? ($familiaModel->find($variante['familia_id'])['nombre'] ?? null) : null;
            $linea['nombreVariante'] = $variante['nombre'] ?? $linea['descripcion_libre'];
        }
        unset($linea);

        $html = view('piezas/emails/pedido_nuevo', [
            'pedido'      => $pedido,
            'totalPiezas' => array_sum(array_column($pedido['lineas'], 'cantidad')),
            'urlPedido'   => site_url('piezas/pedido/' . $pedidoId),
        ]);

        try {
            $email = \Config\Services::email();
            $email->setTo(self::DESTINATARIO);
            $email->setSubject('Tienes un nuevo pedido para revisar (#' . $pedidoId . ')');
            $email->setMailType('html');
            $email->setMessage($html);

            if ($email->send()) {
                return true;
            }

            log_message('error', 'PiezaPedidoNotificador: fallo al enviar aviso del pedido {id}: {debug}', [
                'id'    => $pedidoId,
                'debug' => $email->printDebugger(['headers']),
            ]);

            return false;
        } catch (Throwable $e) {
            log_message('error', 'PiezaPedidoNotificador: excepción al enviar aviso del pedido {id}: {msg}', [
                'id'  => $pedidoId,
                'msg' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
