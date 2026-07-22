<?php
// app/Helpers/gimnasio_helper.php

if (!function_exists('gim_grupos')) {
    /**
     * Fuente única de los grupos musculares / categorías de ejercicio y su
     * nombre bonito. Usada por los selectores de crear/editar ejercicio, el
     * picker de grupo al registrar una serie y el listado de ejercicios.
     */
    function gim_grupos(): array
    {
        return [
            'biceps' => 'Bíceps',
            'triceps' => 'Tríceps',
            'hombros' => 'Hombros',
            'espalda' => 'Espalda',
            'pecho' => 'Pecho',
            'abdominales' => 'Abdominales',
            'piernas' => 'Piernas',
            'maquinas' => 'Máquinas',
            'calentamientos' => 'Calentamientos',
            'movilidad' => 'Movilidad',
            'cardio' => 'Cardio',
            'especificos' => 'Específicos',
            'recuperacion' => 'Recuperación',
            'pliometria' => 'Pliometría',
            'test' => 'Test',
        ];
    }
}

if (!function_exists('gim_grupo_nombre')) {
    function gim_grupo_nombre(?string $clave): string
    {
        return gim_grupos()[$clave] ?? ($clave ?? '');
    }
}

if (!function_exists('gim_svg_chart')) {
    /**
     * Genera un gráfico de línea en SVG (sin librerías externas) a partir de
     * puntos con clave 'e1rm'. Devuelve cadena vacía si hay menos de 2 puntos.
     */
    function gim_svg_chart(array $puntos, int $width = 600, int $height = 140): string
    {
        if (count($puntos) < 2) {
            return '';
        }

        $valores = array_column($puntos, 'e1rm');
        $min = min($valores);
        $max = max($valores);
        if ($max === $min) {
            $max += 1;
            $min -= 1;
        }

        $padX = 6;
        $padY = 12;
        $n = count($puntos);
        $coords = [];
        foreach ($puntos as $i => $p) {
            $x = $padX + ($i / ($n - 1)) * ($width - 2 * $padX);
            $y = $height - $padY - (($p['e1rm'] - $min) / ($max - $min)) * ($height - 2 * $padY);
            $coords[] = round($x, 1) . ',' . round($y, 1);
        }
        $puntosAttr = implode(' ', $coords);
        [$lastX, $lastY] = explode(',', end($coords));

        $svg  = '<svg viewBox="0 0 ' . $width . ' ' . $height . '" class="gim-chart-svg" preserveAspectRatio="none">';
        $svg .= '<polyline points="' . $puntosAttr . '" fill="none" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />';
        $svg .= '<circle cx="' . $lastX . '" cy="' . $lastY . '" r="4" fill="#a78bfa" />';
        $svg .= '</svg>';

        return $svg;
    }
}
