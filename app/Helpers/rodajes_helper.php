<?php
// app/Helpers/rodajes_helper.php

if (!function_exists('rod_vista')) {
    /**
     * Vista nueva (estilo fv) o clásica para todo el módulo de Rodajes, igual
     * que en Lectura/Gimnasio/Journal/Piezas: ?nueva=0 / ?nueva=1 la fija en
     * la sesión y por defecto se ve la nueva.
     */
    function rod_vista(string $vista): string
    {
        $nueva = service('request')->getGet('nueva');
        if ($nueva !== null) {
            session()->set('rodajes_nueva', $nueva === '1');
        }
        return session('rodajes_nueva') !== false ? $vista . '_nueva' : $vista;
    }
}

if (!function_exists('rod_es_video')) {
    function rod_es_video(string $ruta): bool
    {
        return in_array(strtolower(pathinfo($ruta, PATHINFO_EXTENSION)), ['mp4', 'webm', 'ogg', 'mov'], true);
    }
}

if (!function_exists('rod_toma_estados')) {
    /** Estados de toma en el plató: [clave => [texto, icono, clase de color fv]]. */
    function rod_toma_estados(): array
    {
        return [
            'pendiente'  => ['Pendiente', 'bi-circle', 'e-neu'],
            'hecha'      => ['Hecha', 'bi-check-lg', 'e-ok'],
            'repetir'    => ['Repetir', 'bi-arrow-repeat', 'e-warn'],
            'descartada' => ['Descartada', 'bi-x-lg', 'e-neu'],
        ];
    }
}

if (!function_exists('rod_titulo_escena')) {
    /** Texto principal de una escena: descripción, si no bloque, si no «Escena #n». */
    function rod_titulo_escena(array $e): string
    {
        return trim((string) ($e['escena_descripcion'] ?? ''))
            ?: (trim((string) ($e['escena_bloque'] ?? '')) ?: 'Escena #' . (int) ($e['orden'] ?? 0));
    }
}

if (!function_exists('rod_ficha_tecnica')) {
    /**
     * Ficha técnica compacta en cuadrícula de 4 columnas con posiciones FIJAS
     * (cada dato siempre en el mismo sitio, para leerla de un vistazo en el
     * plató). Fila 1 encuadre, fila 2 exposición, fila 3 resto. Una fila sin
     * ningún dato no se pinta; dentro de una fila, los huecos salen como «—».
     */
    function rod_ficha_tecnica(array $e, bool $conCamara = false): string
    {
        $filas = [
            ['Plano' => 'camara_tipo_plano', 'Ángulo' => 'camara_angulo', 'Movim.' => 'camara_movimiento', 'Soporte' => 'camara_soporte'],
            ['Óptica' => 'camara_optica', 'Diafr.' => 'camara_apertura', 'Obtur.' => 'camara_velocidad', 'ISO' => 'camara_iso'],
            ['FPS' => 'camara_fps', 'Balance' => 'camara_wb', 'ND' => 'camara_nd'] + ($conCamara ? ['Cámara' => 'camara_modelo'] : []),
        ];
        $html = '';
        foreach ($filas as $fila) {
            $valores = array_map(static fn ($c) => trim((string) ($e[$c] ?? '')), $fila);
            if (implode('', $valores) === '') continue;
            foreach ($valores as $k => $v) {
                $html .= '<div' . ($v === '' ? ' class="vacio"' : '') . '><span class="k">' . $k . '</span><span class="v">'
                    . ($v === '' ? '—' : esc($v)) . '</span></div>';
            }
            // Fila 3 sin cámara tiene 3 celdas: se rellena para no descuadrar.
            for ($i = count($valores); $i < 4; $i++) $html .= '<div class="vacio"></div>';
        }
        return $html === '' ? '' : '<div class="rd-tec">' . $html . '</div>';
    }
}

if (!function_exists('rod_campos')) {
    /**
     * Pares etiqueta => texto en rejilla de 2 columnas: los cortos van de dos
     * en dos y los largos (o con saltos de línea) ocupan toda la fila.
     */
    function rod_campos(array $pares, array $siempreAnchos = []): string
    {
        $html = '';
        foreach ($pares as $k => $v) {
            $v = trim((string) $v);
            if ($v === '') continue;
            $ancho = in_array($k, $siempreAnchos, true) || mb_strlen($v) > 34 || str_contains($v, "\n");
            $html .= '<div class="rd-c' . ($ancho ? ' ancho' : '') . '"><span class="k">' . esc($k) . '</span><div class="v">' . nl2br(esc($v)) . '</div></div>';
        }
        return $html === '' ? '' : '<div class="rd-campos">' . $html . '</div>';
    }
}

if (!function_exists('rod_resumen_plano')) {
    /** Una línea: «Medio · Frontal · Dolly · 35mm · f/2.8» (para listados). */
    function rod_resumen_plano(array $e): string
    {
        $partes = [];
        foreach (['camara_tipo_plano', 'camara_angulo', 'camara_movimiento', 'camara_optica', 'camara_apertura'] as $c) {
            $v = trim((string) ($e[$c] ?? ''));
            if ($v !== '') $partes[] = esc($v);
        }
        return implode('<i>·</i>', $partes);
    }
}

if (!function_exists('rod_chips_camara')) {
    /** Chips monoespaciados con los datos técnicos que tenga la escena. */
    function rod_chips_camara(array $e, bool $soloPlano = false): string
    {
        $campos = $soloPlano
            ? ['camara_tipo_plano' => '', 'camara_angulo' => '', 'camara_movimiento' => '']
            : [
                'camara_tipo_plano' => '', 'camara_angulo' => '', 'camara_movimiento' => '',
                'camara_optica' => '', 'camara_apertura' => '', 'camara_iso' => 'ISO ',
                'camara_velocidad' => '', 'camara_fps' => 'fps', 'camara_wb' => '', 'camara_nd' => '',
                'camara_soporte' => '',
            ];
        $out = '';
        foreach ($campos as $campo => $extra) {
            $v = trim((string) ($e[$campo] ?? ''));
            if ($v === '') continue;
            $txt = $extra === 'fps' ? $v . ' fps' : $extra . $v;
            $out .= '<span class="rd-chip">' . esc($txt) . '</span>';
        }
        return $out;
    }
}
