<?php
// app/Helpers/enlaces_helper.php

if (!function_exists('enl_vista')) {
    /**
     * Vista nueva (estilo fv) o clásica, como en el resto de módulos:
     * ?nueva=0 / ?nueva=1 la fija en la sesión; por defecto, la nueva.
     * También decide los parciales que devuelven las llamadas AJAX.
     */
    function enl_vista(string $vista): string
    {
        $nueva = service('request')->getGet('nueva');
        if ($nueva !== null) {
            session()->set('enlaces_nueva', $nueva === '1');
        }
        return session('enlaces_nueva') !== false ? $vista . '_nueva' : $vista;
    }
}

if (!function_exists('enl_dominio')) {
    /** Dominio sin «www.» de una URL, o ''. */
    function enl_dominio(?string $url): string
    {
        $host = parse_url((string) $url, PHP_URL_HOST);
        return $host ? preg_replace('/^www\./', '', $host) : '';
    }
}

if (!function_exists('enl_hace')) {
    /** «hoy», «hace 3 días», «hace 2 meses», «hace 1 año». */
    function enl_hace(?string $fecha): string
    {
        $t = $fecha ? strtotime($fecha) : false;
        if (!$t) return '';
        $dias = (int) floor((time() - $t) / 86400);
        if ($dias < 1)   return 'hoy';
        if ($dias < 31)  return 'hace ' . $dias . ($dias === 1 ? ' día' : ' días');
        $meses = (int) floor($dias / 30.44);
        if ($meses < 12) return 'hace ' . $meses . ($meses === 1 ? ' mes' : ' meses');
        $anios = (int) floor($meses / 12);
        return 'hace ' . $anios . ($anios === 1 ? ' año' : ' años');
    }
}
