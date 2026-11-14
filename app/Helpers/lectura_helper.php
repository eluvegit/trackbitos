<?php
// app/Helpers/lectura_helper.php

if (!function_exists('lec_vista')) {
    /**
     * Vista nueva (estilo fv) o clásica para todo el módulo de Lectura, igual
     * que en Gimnasio/Journal/Piezas: ?nueva=0 / ?nueva=1 la fija en la sesión
     * y por defecto se ve la nueva.
     */
    function lec_vista(string $vista): string
    {
        $nueva = service('request')->getGet('nueva');
        if ($nueva !== null) {
            session()->set('lectura_nueva', $nueva === '1');
        }
        return session('lectura_nueva') !== false ? $vista . '_nueva' : $vista;
    }
}

if (!function_exists('lec_estados')) {
    /** Estado => [icono, etiqueta corta de botón, etiqueta de pestaña]. */
    function lec_estados(): array
    {
        return [
            'leyendo'     => ['bi-play-fill', 'Leyendo', 'Leyendo'],
            'quiero_leer' => ['bi-bookmark', 'Por leer', 'Por leer'],
            'pausado'     => ['bi-pause-fill', 'Pausado', 'Pausados'],
            'terminado'   => ['bi-check-circle-fill', 'Leído', 'Leídos'],
            'abandonado'  => ['bi-x-circle', 'Dejado', 'Dejados'],
        ];
    }
}

if (!function_exists('lec_fecha')) {
    /** "14 nov" / "14 nov 2025" (año solo si no es el actual). */
    function lec_fecha(?string $fecha): string
    {
        if (!$fecha) {
            return '';
        }
        $ts = strtotime($fecha);
        $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        $txt = date('j', $ts) . ' ' . $meses[(int) date('n', $ts) - 1];
        return date('Y', $ts) === date('Y') ? $txt : $txt . ' ' . date('Y', $ts);
    }
}

if (!function_exists('lec_dia_relativo')) {
    function lec_dia_relativo(string $fecha): string
    {
        $dias = (int) round((strtotime(date('Y-m-d')) - strtotime($fecha)) / 86400);
        return match (true) {
            $dias === 0 => 'Hoy',
            $dias === 1 => 'Ayer',
            $dias < 7   => 'Hace ' . $dias . ' días',
            default     => ucfirst(lec_fecha($fecha)),
        };
    }
}

if (!function_exists('lec_puntos')) {
    /**
     * Ventana de constancia como fila de puntos (relleno = tocado). Nunca
     * pinta en rojo los días sin lectura: un hueco es solo un hueco.
     */
    function lec_puntos(array $ventana, bool $grande = false): string
    {
        $html = '<div class="lc-puntos' . ($grande ? ' gr' : '') . '">';
        foreach ($ventana as $d) {
            $hoy = $d['fecha'] === date('Y-m-d') ? ' hoy' : '';
            $html .= '<i class="' . ($d['tocado'] ? 'on' : '') . $hoy . '" title="' . esc(lec_fecha($d['fecha']), 'attr') . '"></i>';
        }
        return $html . '</div>';
    }
}

if (!function_exists('lec_portada')) {
    /**
     * Portada del libro, o un "lomo" de color estable (según el título) con
     * título y autor cuando no hay imagen.
     */
    function lec_portada(array $libro, string $tag = 'div', string $attrs = ''): string
    {
        $abre = '<' . $tag . ' ' . $attrs . ' class="lc-portada%s"%s>';
        if (!empty($libro['cover_url'])) {
            return sprintf($abre, '', '') . '<img src="' . esc($libro['cover_url'], 'attr') . '" alt="" loading="lazy" onerror="this.parentNode.classList.add(&quot;rota&quot;);this.remove()"></' . $tag . '>';
        }
        $h = crc32((string) ($libro['title'] ?? '')) % 360;
        return sprintf($abre, ' sin', ' style="--h:' . $h . '"')
            . '<b>' . esc($libro['title'] ?? '') . '</b>'
            . '<span>' . esc($libro['author'] ?? '') . '</span></' . $tag . '>';
    }
}

if (!function_exists('lec_estrellas')) {
    function lec_estrellas(?int $n): string
    {
        if (!$n) {
            return '';
        }
        $html = '<span class="lc-estrellas" title="' . $n . ' de 5">';
        for ($i = 1; $i <= 5; $i++) {
            $html .= '<i class="bi ' . ($i <= $n ? 'bi-star-fill' : 'bi-star') . '"></i>';
        }
        return $html . '</span>';
    }
}

if (!function_exists('lec_ultima_vez')) {
    /** "hoy" / "ayer" / "23 de agosto" / "23 de agosto de 2025". */
    function lec_ultima_vez(?string $fecha): string
    {
        if (!$fecha) {
            return '';
        }
        $dias = (int) round((strtotime(date('Y-m-d')) - strtotime($fecha)) / 86400);
        if ($dias === 0) return 'hoy';
        if ($dias === 1) return 'ayer';
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $ts = strtotime($fecha);
        $txt = date('j', $ts) . ' de ' . $meses[(int) date('n', $ts) - 1];
        return date('Y', $ts) === date('Y') ? $txt : $txt . ' de ' . date('Y', $ts);
    }
}
