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
        'origen', 'estado', 'orden', 'prioritario', 'fecha_entrega', 'referencia_externa', 'notas',
        'creado_en', 'actualizado_en',
    ];

    protected $validationRules = [
        'origen' => 'required|max_length[30]',
        'estado' => 'permit_empty|in_list[nuevo,en_produccion,completado,cancelado]',
        'fecha_entrega' => 'permit_empty|valid_date[Y-m-d]',
    ];

    public function conLineas(int $pedidoId): ?array
    {
        $pedido = $this->find($pedidoId);
        if (!$pedido) {
            return null;
        }

        $pedido['lineas'] = (new PiezaPedidoLineaModel())->where('pedido_id', $pedidoId)
            ->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();

        return $pedido;
    }

    /**
     * Cómo pintar la fecha de entrega de un pedido: texto corto ("hoy",
     * "mañana", "en 3 días", "hace 2 días", dd/mm) y clase de color. Solo
     * avisa en rojo/ámbar mientras el pedido sigue abierto; uno hecho o
     * cancelado la muestra apagada. Null si no tiene fecha.
     *
     * @return array{texto: string, clase: string, vencido: bool}|null
     */
    public static function entrega(array $pedido): ?array
    {
        if (empty($pedido['fecha_entrega'])) {
            return null;
        }

        $fecha = new \DateTimeImmutable($pedido['fecha_entrega']);
        $dias  = (int) (new \DateTimeImmutable('today'))->diff($fecha)->format('%r%a');
        $fechaCorta = $fecha->format('d/m');

        $texto = match (true) {
            $dias === 0  => 'hoy',
            $dias === 1  => 'mañana',
            $dias === -1 => 'ayer · ' . $fechaCorta,
            $dias < 0    => 'hace ' . -$dias . ' días · ' . $fechaCorta,
            $dias <= 7   => 'en ' . $dias . ' días · ' . $fechaCorta,
            default      => $fechaCorta,
        };

        $abierto = in_array($pedido['estado'] ?? '', ['nuevo', 'en_produccion'], true);
        $clase = match (true) {
            !$abierto  => 'text-body-secondary',
            $dias <= 0 => 'text-danger fw-semibold',
            $dias <= 3 => 'text-warning-emphasis fw-semibold',
            default    => 'text-body-secondary',
        };

        return ['texto' => $texto, 'clase' => $clase, 'vencido' => $abierto && $dias < 0];
    }

    /**
     * Orden del tablero: los de estrella arriba, luego el orden manual
     * (arrastrado) y, entre los que aún no se han colocado (orden 0), los
     * más recientes primero — así uno nuevo aparece arriba hasta que se mueva.
     */
    public function recientes(int $limite = 50): array
    {
        return $this->orderBy('prioritario', 'DESC')->orderBy('orden', 'ASC')
            ->orderBy('creado_en', 'DESC')->findAll($limite);
    }
}
