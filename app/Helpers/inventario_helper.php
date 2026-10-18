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
