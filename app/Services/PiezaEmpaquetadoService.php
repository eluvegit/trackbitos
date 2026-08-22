<?php

namespace App\Services;

/**
 * Cuántas placas hacen falta para una tanda de piezas, y qué va en cada una
 * — una aproximación, no una vista real del laminador. La plataforma se
 * trata como una rejilla de 6×10 cuadrículas (el margen lateral de la
 * impresora real ya se descuenta de ese 6: no cuenta como hueco
 * aprovechable) y cada pieza ocupa un rectángulo entero de cuadrículas,
 * medido a ojo por quien la sube — no hay ninguna lectura del STL de por
 * medio.
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
    /** Cuadrículas de ancho de la plataforma (el margen lateral de la impresora ya queda fuera). */
    public const COLUMNAS = 6;

    /** Cuadrículas de fondo de la plataforma. */
    public const FILAS = 10;

    /**
     * @param list<array{etiqueta: string, ancho: int, fondo: int, filaId?: int}> $items
     *        Una fila por unidad física a colocar (ya expandidas por cantidad):
     *        no se agrupan aquí copias de la misma pieza.
     * @return list<array{piezas: list<array>, cuadrosUsados: int}> una entrada por placa que hace falta
     */
    public function repartir(array $items): array
    {
        if ($items === []) {
            return [];
        }

        // Descarta lo que nunca podría entrar (más grande que la propia
        // plataforma en cualquiera de los dos lados) — no bloquea el resto,
        // simplemente no se puede colocar y se queda fuera del reparto.
        $items = array_values(array_filter(
            $items,
            static fn(array $i) => $i['ancho'] <= self::COLUMNAS && $i['fondo'] <= self::FILAS
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
                'filaUsada' => $item['fondo'],
            ];
        }

        return array_map(function (array $bin): array {
            $piezas = [];
            $cuadrosUsados = 0;
            foreach ($bin['estantes'] as $estante) {
                foreach ($estante['piezas'] as $pieza) {
                    $piezas[] = $pieza;
                    $cuadrosUsados += $pieza['ancho'] * $pieza['fondo'];
                }
            }

            return ['piezas' => $piezas, 'cuadrosUsados' => $cuadrosUsados];
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
        foreach ($bins as &$bin) {
            $mejorIndice = null;
            $mejorHueco = null;
            foreach ($bin['estantes'] as $i => $estante) {
                if ($estante['alto'] < $item['fondo'] || $estante['ocupado'] + $item['ancho'] > self::COLUMNAS) {
                    continue;
                }
                $hueco = self::COLUMNAS - ($estante['ocupado'] + $item['ancho']);
                if ($mejorHueco === null || $hueco < $mejorHueco) {
                    $mejorHueco = $hueco;
                    $mejorIndice = $i;
                }
            }

            if ($mejorIndice !== null) {
                $bin['estantes'][$mejorIndice]['ocupado'] += $item['ancho'];
                $bin['estantes'][$mejorIndice]['piezas'][] = $item;

                return true;
            }

            if ($bin['filaUsada'] + $item['fondo'] <= self::FILAS) {
                $bin['estantes'][] = $this->estanteNuevo($item);
                $bin['filaUsada'] += $item['fondo'];

                return true;
            }
        }

        return false;
    }

    private function estanteNuevo(array $item): array
    {
        return ['alto' => $item['fondo'], 'ocupado' => $item['ancho'], 'piezas' => [$item]];
    }
}
