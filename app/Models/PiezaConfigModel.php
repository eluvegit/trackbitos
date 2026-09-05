<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Ajustes del módulo Piezas que no son datos del catálogo en sí: qué tarea
 * de Journal está enlazada como fuente de "Pendientes de crear" (spec:
 * journal es el punto de entrada, esta tabla solo recuerda la referencia) y
 * las pautas de promoción (checklist recordatorio antes de promocionar).
 * Fila única (id=1) — no hace falta un modelo de ajustes más genérico para
 * dos valores.
 */
class PiezaConfigModel extends Model
{
    protected $table = 'piezas_config';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'id',
        'tarea_journal_id',
        'pautas_promocion',
        'calc_capas_referencia',
        'calc_minutos_referencia',
        'calc_minutos_preparacion',
        'precio_resina_eur_litro',
        'densidad_resina_g_ml',
        'actualizado_en',
    ];

    private const FILA = 1;

    /**
     * Densidad de una resina de fotopolímero típica (g/mL). Solo entra en
     * juego para convertir entre volumen y peso con soportes cuando de un
     * trozo se apuntó uno pero no el otro.
     */
    private const DENSIDAD_RESINA_DEFECTO = 1.1;

    /**
     * Punto de partida de la calculadora de tiempo mientras no se haya
     * guardado nada: 600 capas medidas tardaron 92 minutos (≈ 0,1533
     * min/capa) y se cuentan 45 minutos fijos de preparación.
     */
    private const CALC_CAPAS_REF_DEFECTO   = 600;
    private const CALC_MINUTOS_REF_DEFECTO  = 92.0;
    private const CALC_MINUTOS_PREP_DEFECTO = 45.0;

    public function tareaJournalId(): ?int
    {
        $fila = $this->find(self::FILA);

        return $fila && $fila['tarea_journal_id'] !== null ? (int) $fila['tarea_journal_id'] : null;
    }

    /**
     * `save()` no vale aquí: con la clave primaria puesta asume update() y,
     * si la fila todavía no existe (primer enlace), la actualización no
     * toca ninguna fila y el cambio se pierde en silencio.
     */
    public function enlazarTarea(?int $tareaId): void
    {
        $datos = ['tarea_journal_id' => $tareaId, 'actualizado_en' => date('Y-m-d H:i:s')];

        if ($this->find(self::FILA)) {
            $this->update(self::FILA, $datos);
        } else {
            $this->insert(['id' => self::FILA] + $datos);
        }
    }

    /**
     * Una línea de texto por pauta, en el orden en que se escribieron.
     * Líneas en blanco descartadas: son las que quedan al editar el
     * textarea, no una pauta real.
     */
    public function pautasPromocion(): array
    {
        $fila = $this->find(self::FILA);
        if (!$fila || $fila['pautas_promocion'] === null) {
            return [];
        }

        return array_values(array_filter(array_map(
            'trim',
            preg_split('/\r\n|\r|\n/', $fila['pautas_promocion'])
        ), static fn($linea) => $linea !== ''));
    }

    /** Mismo motivo que enlazarTarea(): save() no crea la fila si aún no existe. */
    public function guardarPautas(string $texto): void
    {
        $datos = ['pautas_promocion' => trim($texto) !== '' ? $texto : null, 'actualizado_en' => date('Y-m-d H:i:s')];

        if ($this->find(self::FILA)) {
            $this->update(self::FILA, $datos);
        } else {
            $this->insert(['id' => self::FILA] + $datos);
        }
    }

    /**
     * Ajustes de la calculadora de tiempo del índice, con los valores por
     * defecto ya aplicados cuando la fila todavía no existe o algún campo
     * está a null. `minutosPorCapa` es derivado (referencia ÷ capas), no se
     * guarda: así basta con reeditar la medición para recalibrarla.
     */
    public function calculadoraTiempo(): array
    {
        $fila = $this->find(self::FILA) ?: [];

        $capasRef = isset($fila['calc_capas_referencia']) && $fila['calc_capas_referencia'] !== null
            ? (int) $fila['calc_capas_referencia']
            : self::CALC_CAPAS_REF_DEFECTO;
        $minutosRef = isset($fila['calc_minutos_referencia']) && $fila['calc_minutos_referencia'] !== null
            ? (float) $fila['calc_minutos_referencia']
            : self::CALC_MINUTOS_REF_DEFECTO;
        $minutosPrep = isset($fila['calc_minutos_preparacion']) && $fila['calc_minutos_preparacion'] !== null
            ? (float) $fila['calc_minutos_preparacion']
            : self::CALC_MINUTOS_PREP_DEFECTO;

        return [
            'capasReferencia'    => $capasRef,
            'minutosReferencia'  => $minutosRef,
            'minutosPreparacion' => $minutosPrep,
            'minutosPorCapa'     => $capasRef > 0 ? $minutosRef / $capasRef : 0.0,
        ];
    }

    /** Mismo motivo que enlazarTarea(): save() no crea la fila si aún no existe. */
    public function guardarCalculadoraTiempo(int $capasReferencia, float $minutosReferencia, float $minutosPreparacion): void
    {
        $datos = [
            'calc_capas_referencia'    => max(1, $capasReferencia),
            'calc_minutos_referencia'  => max(0, $minutosReferencia),
            'calc_minutos_preparacion' => max(0, $minutosPreparacion),
            'actualizado_en'           => date('Y-m-d H:i:s'),
        ];

        if ($this->find(self::FILA)) {
            $this->update(self::FILA, $datos);
        } else {
            $this->insert(['id' => self::FILA] + $datos);
        }
    }

    /**
     * Ajuste global de resina para el coste por pieza: precio por litro (o
     * null si aún no se ha puesto, y entonces no hay coste que calcular) y
     * densidad g/mL, con el valor típico ya aplicado si no se ha tocado.
     */
    public function resina(): array
    {
        $fila = $this->find(self::FILA) ?: [];

        $precioLitro = isset($fila['precio_resina_eur_litro']) && $fila['precio_resina_eur_litro'] !== null
            ? (float) $fila['precio_resina_eur_litro']
            : null;
        $densidad = isset($fila['densidad_resina_g_ml']) && $fila['densidad_resina_g_ml'] !== null
            && (float) $fila['densidad_resina_g_ml'] > 0
            ? (float) $fila['densidad_resina_g_ml']
            : self::DENSIDAD_RESINA_DEFECTO;

        return [
            'precioLitro' => $precioLitro,
            'densidad'    => $densidad,
        ];
    }

    /** Mismo motivo que enlazarTarea(): save() no crea la fila si aún no existe. */
    public function guardarResina(?float $precioLitro, ?float $densidad): void
    {
        $datos = [
            'precio_resina_eur_litro' => $precioLitro !== null && $precioLitro > 0 ? $precioLitro : null,
            'densidad_resina_g_ml'    => $densidad !== null && $densidad > 0 ? $densidad : null,
            'actualizado_en'          => date('Y-m-d H:i:s'),
        ];

        if ($this->find(self::FILA)) {
            $this->update(self::FILA, $datos);
        } else {
            $this->insert(['id' => self::FILA] + $datos);
        }
    }
}
