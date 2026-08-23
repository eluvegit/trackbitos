<?php

namespace App\Services;

/**
 * Cuántas placas hacen falta para una tanda de piezas, y qué va en cada una
 * — una aproximación, no una vista real del laminador. La plataforma se
 * trata como un rectángulo de PLACA_ANCHO_MM × PLACA_FONDO_MM (el tamaño
 * real de la plataforma, medido con la rejilla configurable de Chitubox) y
 * cada pieza ocupa su caja de ocupación exacta en mm, más un margen fijo
 * (MARGEN_MM) por los cuatro lados para no pegar piezas entre sí y fallar
 * por un descuadre de plataforma o un soporte que se salga un poco de su
 * sitio. La medida sale de Chitubox (caja de ocupación de la pieza), no de
 * leer el STL — a ojo, con la orientación con la que se vaya a imprimir.
 *
 * El algoritmo es un "shelf packing" (estanterías): se ordenan las piezas de
 * más a menos fondo y se van colocando, placa a placa EN ORDEN, en el
 * estante de mejor encaje (el que deje menos hueco libre); si ninguno vale,
 * se abre un estante nuevo, y solo si tampoco cabe un estante nuevo se pasa
 * a la siguiente placa. Así la primera placa siempre se aprovecha al máximo
 * antes de sangrar piezas a la segunda. Es una heurística conocida (Best-Fit
 * Decreasing Height), no un empaquetado óptimo — para esto basta: la propia
 * app admite fallar por una pieza o dos, nunca pasarse de optimista de forma
 * gorda.
 */
class PiezaEmpaquetadoService
{
    /** Ancho real de la plataforma, en mm. */
    public const PLACA_ANCHO_MM = 211.68;

    /** Fondo real de la plataforma, en mm. */
    public const PLACA_FONDO_MM = 118.37;

    /**
     * Separación mínima entre piezas, en mm, para no fallar por descuadres
     * de plataforma o soportes que se salgan un poco de su sitio. Se reserva
     * por los cuatro lados de cada pieza (aproximación conservadora: en la
     * práctica solo hace falta entre piezas vecinas, no contra el borde de
     * la plataforma, pero es más simple y nunca se pasa de optimista).
     */
    public const MARGEN_MM = 5.0;

    /**
     * @param list<array{etiqueta: string, ancho: float, fondo: float, filaId?: int}> $items
     *        Una fila por unidad física a colocar (ya expandidas por cantidad):
     *        no se agrupan aquí copias de la misma pieza.
     * @return list<array{piezas: list<array>, areaUsadaMm2: float}> una entrada por placa que hace falta
     */
    public function repartir(array $items): array
    {
        if ($items === []) {
            return [];
        }

        // Descarta lo que nunca podría entrar (más grande que la propia
        // plataforma en cualquiera de los dos lados, sin contar el margen)
        // — no bloquea el resto, simplemente no se puede colocar y se queda
        // fuera del reparto.
        $items = array_values(array_filter(
            $items,
            static fn(array $i) => $i['ancho'] <= self::PLACA_ANCHO_MM && $i['fondo'] <= self::PLACA_FONDO_MM
        ));

        // De más a menos fondo (y de más a menos ancho como desempate): es
        // la heurística que mejor aprovecha las estanterías en un shelf
        // packing — las piezas altas marcan la altura de cada fila, así que
        // conviene colocarlas primero.
        usort($items, static fn(array $a, array $b) => [$b['fondo'], $b['ancho']] <=> [$a['fondo'], $a['ancho']]);

        $bins = [];
        foreach ($items as $item) {
            if ($this->colocarEnBinsExistentes($bins, $item)) {
                continue;
            }

            $bins[] = [
                'estantes' => [$this->estanteNuevo($item)],
                'filaUsada' => $item['fondo'] + self::MARGEN_MM,
            ];
        }

        return array_map(function (array $bin): array {
            $piezas = [];
            $areaUsadaMm2 = 0.0;
            foreach ($bin['estantes'] as $estante) {
                foreach ($estante['piezas'] as $pieza) {
                    $piezas[] = $pieza;
                    $areaUsadaMm2 += $pieza['ancho'] * $pieza['fondo'];
                }
            }

            return ['piezas' => $piezas, 'areaUsadaMm2' => $areaUsadaMm2];
        }, $bins);
    }

    /**
     * Bin a bin, EN ORDEN: no se mira la placa 2 mientras la 1 todavía
     * pueda dar cabida a la pieza, así se aprovecha la primera al máximo
     * antes de abrir la siguiente (en vez de repartir "a la ligera" entre
     * las que ya haya). Dentro de cada bin, el estante elegido es el de
     * mejor encaje (menos hueco libre le queda), no el primero que
     * simplemente valga — así las piezas quedan más apretadas y sobra
     * menos sitio desperdiciado.
     *
     * @param list<array<string,mixed>> $bins
     */
    private function colocarEnBinsExistentes(array &$bins, array $item): bool
    {
        $anchoConMargen = $item['ancho'] + self::MARGEN_MM;
        $fondoConMargen = $item['fondo'] + self::MARGEN_MM;

        foreach ($bins as &$bin) {
            $mejorIndice = null;
            $mejorHueco = null;
            foreach ($bin['estantes'] as $i => $estante) {
                if ($estante['alto'] < $fondoConMargen || $estante['ocupado'] + $anchoConMargen > self::PLACA_ANCHO_MM) {
                    continue;
                }
                $hueco = self::PLACA_ANCHO_MM - ($estante['ocupado'] + $anchoConMargen);
                if ($mejorHueco === null || $hueco < $mejorHueco) {
                    $mejorHueco = $hueco;
                    $mejorIndice = $i;
                }
            }

            if ($mejorIndice !== null) {
                $bin['estantes'][$mejorIndice]['ocupado'] += $anchoConMargen;
                $bin['estantes'][$mejorIndice]['piezas'][] = $item;

                return true;
            }

            if ($bin['filaUsada'] + $fondoConMargen <= self::PLACA_FONDO_MM) {
                $bin['estantes'][] = $this->estanteNuevo($item);
                $bin['filaUsada'] += $fondoConMargen;

                return true;
            }
        }

        return false;
    }

    private function estanteNuevo(array $item): array
    {
        return [
            'alto'     => $item['fondo'] + self::MARGEN_MM,
            'ocupado'  => $item['ancho'] + self::MARGEN_MM,
            'piezas'   => [$item],
        ];
    }
}
