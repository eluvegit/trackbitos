<?php

/**
 * Cómo se pinta el stock de una pieza en todas las pantallas: el total en
 * grande (lo pone cada vista) y, en pequeño, dónde está repartido. Los datos
 * salen de App\Services\PiezaInventario::desglose().
 */

if (!function_exists('stock_desglose')) {
    /**
     * "en hueco 8 · impresas 3 · pedido 1", con la parte de esta pantalla
     * resaltada. Solo salen las partes con unidades (y la resaltada siempre).
     *
     * @param array{total:int, hueco:int, impresas:int, pedido:int} $d
     * @param string|null $resalta        'hueco' | 'impresas' | 'pedido': la parte que cuenta esta pantalla
     * @param string      $etiquetaHueco  cómo llamar a la parte del hueco ("en hueco", "aquí", "sin ubicación", "E1H2"…)
     * @param bool        $soloSiReparto  true = nada si todo está en el hueco (para listados densos)
     */
    function stock_desglose(array $d, ?string $resalta = null, string $etiquetaHueco = 'en hueco', bool $soloSiReparto = false): string
    {
        if ($soloSiReparto && (int) $d['impresas'] === 0 && (int) $d['pedido'] === 0) {
            return '';
        }

        $etiquetas = ['hueco' => $etiquetaHueco, 'impresas' => 'caja impresas', 'pedido' => 'caja pedido'];
        $partes    = [];
        foreach ($etiquetas as $clave => $texto) {
            $n = (int) $d[$clave];
            if ($n === 0 && $clave !== $resalta) {
                continue;
            }
            $partes[] = '<span class="st-parte' . ($clave === $resalta ? ' aqui' : '') . '">' . esc($texto) . ' <b>' . $n . '</b></span>';
        }

        return $partes === [] ? '' : '<span class="st-des">' . implode('<i>·</i>', $partes) . '</span>';
    }
}

if (!function_exists('stock_mov_attrs')) {
    /**
     * Atributos del botón que abre el modal común de alta / baja
     * (piezas/_modal_mov_stock). $origen preselecciona de dónde sale una
     * baja; sin él, el hueco (o la caja, si es lo único con unidades).
     *
     * @param array{total:int, hueco:int, impresas:int, pedido:int} $d
     */
    function stock_mov_attrs(int $varianteId, string $nombre, array $d, string $sentido, ?string $origen = null, bool $tieneHueco = true): string
    {
        return ' data-bs-toggle="modal" data-bs-target="#modalMovStock"'
            . ' data-variante="' . $varianteId . '"'
            . ' data-nombre="' . esc($nombre, 'attr') . '"'
            . ' data-sentido="' . ($sentido === 'baja' ? 'baja' : 'alta') . '"'
            . ' data-total="' . (int) $d['total'] . '"'
            . ' data-hueco="' . (int) $d['hueco'] . '"'
            . ' data-impresas="' . (int) $d['impresas'] . '"'
            . ' data-pedido="' . (int) $d['pedido'] . '"'
            . ' data-tiene-hueco="' . ($tieneHueco ? '1' : '0') . '"'
            . ($origen !== null ? ' data-origen="' . esc($origen, 'attr') . '"' : '');
    }
}
