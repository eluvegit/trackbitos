<?php

namespace App\Models;

use CodeIgniter\Model;

class PiezaPedidoModel extends Model
{
    protected $table         = 'piezas_pedidos';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    // Fases del pedido: nuevo -> diseñando (clave histórica en_produccion)
    // -> para impresión -> hecho.
    public const ESTADOS = ['nuevo', 'en_produccion', 'para_impresion', 'completado'];

    // Los que aún no están hechos.
    public const ABIERTOS = ['nuevo', 'en_produccion', 'para_impresion'];

    protected $allowedFields = [
        'origen', 'estado', 'orden', 'prioritario', 'fecha_entrega', 'referencia_externa', 'notas',
        'creado_en', 'actualizado_en',
    ];

    protected $validationRules = [
        'origen' => 'required|max_length[30]',
        'estado' => 'permit_empty|in_list[nuevo,en_produccion,para_impresion,completado]',
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
     * avisa en rojo/ámbar mientras el pedido sigue abierto; uno hecho la
     * muestra apagada. Null si no tiene fecha.
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

        $abierto = in_array($pedido['estado'] ?? '', self::ABIERTOS, true);
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

    /** Todos los que aún no están hechos, en el orden del tablero. */
    public function abiertos(): array
    {
        return $this->whereIn('estado', self::ABIERTOS)
            ->orderBy('prioritario', 'DESC')->orderBy('orden', 'ASC')
            ->orderBy('creado_en', 'DESC')->findAll();
    }

    /**
     * Columna "Hecho" a trozos: se acumulan sin fin, así que el tablero trae
     * los últimos terminados (actualizado_en) y el resto se pide al bajar.
     */
    public function hechos(int $limite, int $desde = 0): array
    {
        return $this->where('estado', 'completado')
            ->orderBy('actualizado_en', 'DESC')->orderBy('id', 'DESC')
            ->findAll($limite, $desde);
    }
}
