<?php

if (! function_exists('inventario_url')) {
    /**
     * URL dentro de la sección de inventario: en inventario.eluve.es cuelga
     * de la raíz; en el dominio principal, de /inventario.
     */
    function inventario_url(string $ruta = ''): string
    {
        $ruta = ltrim($ruta, '/');

        if (config('App')->esInventario()) {
            return site_url($ruta);
        }

        return site_url(rtrim('inventario/' . $ruta, '/'));
    }
}

if (! function_exists('inventario_hueco_url')) {
    /**
     * Ficha del estuche de una pieza con su hueco resaltado (#E1H2), o
     * null si la pieza no tiene hueco.
     */
    function inventario_hueco_url(array $pieza): ?string
    {
        if (empty($pieza['estuche_id']) || empty($pieza['codigo'])) {
            return null;
        }

        return inventario_url('estuches/' . (int) $pieza['estuche_id']) . '#' . rawurlencode($pieza['codigo']);
    }
}
