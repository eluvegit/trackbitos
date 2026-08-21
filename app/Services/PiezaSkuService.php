<?php

namespace App\Services;

use App\Models\PiezaSkuContadorModel;

/**
 * SKU automático e inmutable por variante (fase 44): sustituye al campo
 * manual que había antes (pensado para el código de la tienda) — ahora
 * identifica de forma unívoca cada variante para poder pedirla desde fuera
 * (integración con la web de piezas y pedidos), y no se vuelve a tocar
 * jamás una vez asignado, ni aunque la pieza cambie de categoría.
 *
 * El número no es el contador tal cual: se "revuelve" con
 * `codigo = (contador * A) mod M` para que dos SKU consecutivos no se lean
 * como un correlativo (una permutación completa sobre 0..M-1, sin
 * colisiones posibles mientras el contador no supere M).
 *
 * A = 34419 sale de reducir mod M la concatenación de las posiciones en el
 * abecedario de S-T-E-R-C-L-I-C-K-S (19-20-05-18-03-12-09-03-11-19 →
 * 19200518031209031119), un número que desborda el entero nativo de PHP y
 * por eso se calculó una sola vez con bcmod(); aquí solo queda el
 * resultado. M = 99991 (primo, 5 cifras) da margen de sobra para el tamaño
 * de este catálogo.
 */
class PiezaSkuService
{
    private const A = 34419;
    private const M = 99991;

    private PiezaSkuContadorModel $contadorModel;

    public function __construct()
    {
        $this->contadorModel = new PiezaSkuContadorModel();
    }

    /** Consume el contador de verdad: cada llamada gasta un número que no vuelve. */
    public function generar(): string
    {
        return self::codigoDe($this->contadorModel->siguiente());
    }

    /**
     * Qué número lleva el contador ahora mismo, sin gastar ninguno — para
     * previsualizar (p. ej. `piezas:asignar-sku` en modo simulación) sin
     * comprometer el contador real.
     */
    public function contadorActual(): int
    {
        return $this->contadorModel->actual();
    }

    /**
     * La parte pura de la fórmula, separada de `generar()` para poder
     * simular varios códigos seguidos sin tocar el contador (el comando de
     * backfill enseña una vista previa antes de --confirmar).
     */
    public static function codigoDe(int $contador): string
    {
        $revuelto = ($contador * self::A) % self::M;
        // Solo pasaría con contador múltiplo de M (el primero, a partir del
        // 99991-ésimo SKU): evita un código "S-00000" que se leería como un
        // error de generación.
        $revuelto = $revuelto === 0 ? self::M : $revuelto;

        return sprintf('S-%05d', $revuelto);
    }
}
