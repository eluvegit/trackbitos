<?php

namespace App\Services;

/**
 * Cuántas placas hacen falta para una tanda de piezas, y qué va en cada una
 * — una aproximación por superficie, no una vista real del laminador (un
 * auto-arrange como el de Chitubox anida la silueta real de la pieza, no
 * solo su superficie, y por eso siempre va a caber más de lo que diga esto).
 *
 * El cálculo es deliberadamente simple: superficie de la placa entre
 * superficie de cada pieza, sin intentar encajar rectángulos por ancho y
 * fondo por separado. Cada pieza se va sumando a la placa actual (en el
 * mismo orden en que llegan los items) hasta que la siguiente ya no cabría
 * por superficie; entonces se abre placa nueva.
 *
 * Como ese reparto "a superficie pura" no deja ningún hueco de verdad entre
 * piezas, se calcula con dos capacidades distintas para poder enseñar las
 * dos versiones:
 *   - Sin margen (factor 1.0): el máximo teórico, solo por superficie —
 *     "si las piezas se pudieran tocar entre sí". Nunca va a caber esto de
 *     verdad, es la cota optimista.
 *   - Con margen de seguridad (factor MARGEN_SEGURIDAD, 10% por defecto):
 *     se reserva ese porcentaje de la placa sin contar como aprovechable,
 *     para dejar hueco a los márgenes reales entre piezas. Más conservador,
 *     más realista para saber cuántas placas hacen falta.
 */
class PiezaEmpaquetadoService
{
    /** Ancho real de la plataforma, en mm. */
    public const PLACA_ANCHO_MM = 211.68;

    /** Fondo real de la plataforma, en mm. */
    public const PLACA_FONDO_MM = 118.37;

    /**
     * Margen de seguridad, como fracción de la superficie de la placa que
     * se deja sin contar como aprovechable (huecos reales entre piezas que
     * el cálculo por superficie pura no ve). 0.10 = solo se cuenta el 90%
     * de la placa como superficie disponible.
     */
    public const MARGEN_SEGURIDAD = 0.10;

    /**
     * @param list<array{etiqueta: string, ancho: float, fondo: float, filaId?: int}> $items
     *        Una fila por unidad física a colocar (ya expandidas por cantidad):
     *        no se agrupan aquí copias de la misma pieza.
     * @param float|null $margenSeguridad Fracción de la placa a reservar como
     *        colchón (0.0 a 1.0). Por defecto self::MARGEN_SEGURIDAD; pásese
     *        0.0 para el reparto optimista "solo superficie", sin colchón.
     * @return list<array{piezas: list<array>, areaUsadaMm2: float}> una entrada por placa que hace falta
     */
    public function repartir(array $items, ?float $margenSeguridad = null): array
    {
        $margenSeguridad ??= self::MARGEN_SEGURIDAD;

        if ($items === []) {
            return [];
        }

        // Descarta lo que nunca podría entrar (más grande que la propia
        // plataforma en ancho o en fondo) — la superficie sola no pilla
        // esto: una pieza larga y estrecha puede tener poca superficie y
        // aun así no caber físicamente en ningún lado.
        $items = array_values(array_filter(
            $items,
            static fn(array $i) => $i['ancho'] <= self::PLACA_ANCHO_MM && $i['fondo'] <= self::PLACA_FONDO_MM
        ));

        $capacidadMm2 = self::PLACA_ANCHO_MM * self::PLACA_FONDO_MM * (1 - $margenSeguridad);

        $bins = [];
        $piezasBinActual = [];
        $areaBinActual = 0.0;
        foreach ($items as $item) {
            $areaItem = $item['ancho'] * $item['fondo'];

            if ($piezasBinActual !== [] && $areaBinActual + $areaItem > $capacidadMm2) {
                $bins[] = ['piezas' => $piezasBinActual, 'areaUsadaMm2' => $areaBinActual];
                $piezasBinActual = [];
                $areaBinActual = 0.0;
            }

            $piezasBinActual[] = $item;
            $areaBinActual += $areaItem;
        }
        $bins[] = ['piezas' => $piezasBinActual, 'areaUsadaMm2' => $areaBinActual];

        return $bins;
    }
}
