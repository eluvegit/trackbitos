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

/* -------------------------------------------------------------------------
 * Catálogo externo (gimnasio_catalogo, dataset exercises-dataset)
 * Los datos vienen en inglés; aquí solo se traducen las etiquetas y se
 * generan términos en español para que el buscador entienda "sentadilla".
 * ---------------------------------------------------------------------- */

if (!function_exists('gim_catalogo_media')) {
    /** URL pública de una imagen/GIF del dataset (servido por jsDelivr). */
    function gim_catalogo_media(?string $ruta): string
    {
        return $ruta ? 'https://cdn.jsdelivr.net/gh/hasaneyldrm/exercises-dataset@main/' . ltrim($ruta, '/') : '';
    }
}

if (!function_exists('gim_catalogo_partes')) {
    function gim_catalogo_partes(): array
    {
        return [
            'upper arms' => 'Brazos', 'lower arms' => 'Antebrazos', 'upper legs' => 'Piernas',
            'lower legs' => 'Gemelos / tibial', 'back' => 'Espalda', 'waist' => 'Abdomen',
            'chest' => 'Pecho', 'shoulders' => 'Hombros', 'cardio' => 'Cardio', 'neck' => 'Cuello',
        ];
    }
}

if (!function_exists('gim_catalogo_equipos')) {
    function gim_catalogo_equipos(): array
    {
        return [
            'body weight' => 'Peso corporal', 'dumbbell' => 'Mancuerna', 'cable' => 'Polea',
            'barbell' => 'Barra', 'leverage machine' => 'Máquina', 'band' => 'Banda',
            'smith machine' => 'Multipower', 'kettlebell' => 'Kettlebell', 'weighted' => 'Lastrado',
            'stability ball' => 'Fitball', 'ez barbell' => 'Barra Z', 'assisted' => 'Asistido',
            'sled machine' => 'Máquina trineo', 'medicine ball' => 'Balón medicinal', 'rope' => 'Cuerda',
            'roller' => 'Rodillo', 'resistance band' => 'Goma', 'bosu ball' => 'Bosu',
            'olympic barbell' => 'Barra olímpica', 'wheel roller' => 'Rueda abdominal',
            'upper body ergometer' => 'Ergómetro de brazos', 'skierg machine' => 'SkiErg',
            'hammer' => 'Martillo', 'stationary bike' => 'Bici estática', 'tire' => 'Neumático',
            'trap bar' => 'Barra hexagonal', 'elliptical machine' => 'Elíptica', 'stepmill machine' => 'Escaladora',
        ];
    }
}

if (!function_exists('gim_catalogo_objetivos')) {
    function gim_catalogo_objetivos(): array
    {
        return [
            'abs' => 'Abdominales', 'pectorals' => 'Pectorales', 'biceps' => 'Bíceps', 'glutes' => 'Glúteos',
            'delts' => 'Deltoides', 'triceps' => 'Tríceps', 'upper back' => 'Espalda alta', 'lats' => 'Dorsales',
            'calves' => 'Gemelos', 'quads' => 'Cuádriceps', 'forearms' => 'Antebrazos',
            'cardiovascular system' => 'Cardiovascular', 'hamstrings' => 'Isquiotibiales', 'spine' => 'Lumbares',
            'traps' => 'Trapecios', 'adductors' => 'Aductores', 'serratus anterior' => 'Serrato',
            'abductors' => 'Abductores', 'levator scapulae' => 'Elevador de la escápula',
        ];
    }
}

if (!function_exists('gim_catalogo_etiqueta')) {
    /** Traduce un valor de parte/equipo/objetivo; si no hay traducción, lo devuelve tal cual. */
    function gim_catalogo_etiqueta(string $tipo, ?string $valor): string
    {
        $mapa = match ($tipo) {
            'parte'    => gim_catalogo_partes(),
            'equipo'   => gim_catalogo_equipos(),
            'objetivo' => gim_catalogo_objetivos(),
            default    => [],
        };
        return $mapa[$valor] ?? ucfirst((string) $valor);
    }
}

if (!function_exists('gim_catalogo_terminos')) {
    /**
     * Texto de búsqueda de una fila del catálogo: nombre en inglés + sus
     * palabras clave traducidas + etiquetas en español. Así "remo mancuerna"
     * encuentra "dumbbell bent over row".
     */
    function gim_catalogo_terminos(array $c): string
    {
        static $dic = [
            'squat' => 'sentadilla', 'deadlift' => 'peso muerto', 'bench press' => 'press banca',
            'press' => 'press empuje', 'row' => 'remo', 'lunge' => 'zancada',
            'pull-up' => 'dominada', 'pull up' => 'dominada', 'chin-up' => 'dominada supina',
            'push-up' => 'flexion flexiones', 'push up' => 'flexion flexiones', 'dip' => 'fondos',
            'fly' => 'aperturas', 'raise' => 'elevacion elevaciones', 'front' => 'frontal',
            'rear' => 'posterior pajaro', 'pulldown' => 'jalon', 'shrug' => 'encogimiento',
            'extension' => 'extension', 'kickback' => 'patada', 'crunch' => 'abdominal',
            'sit-up' => 'abdominal', 'plank' => 'plancha', 'bridge' => 'puente',
            'hip thrust' => 'puente gluteo', 'calf' => 'gemelo talones', 'stretch' => 'estiramiento',
            'incline' => 'inclinado', 'decline' => 'declinado', 'seated' => 'sentado',
            'standing' => 'de pie', 'lying' => 'tumbado', 'kneeling' => 'de rodillas',
            'one arm' => 'una mano unilateral', 'single arm' => 'una mano unilateral',
            'one leg' => 'una pierna unilateral', 'single leg' => 'una pierna unilateral',
            'alternate' => 'alterno', 'hammer' => 'martillo', 'close grip' => 'agarre cerrado',
            'wide grip' => 'agarre ancho', 'reverse' => 'inverso invertido', 'step-up' => 'step subida',
            'jump' => 'salto pliometria pliometrico', 'plyo' => 'pliometria pliometrico',
            'hops' => 'saltos pliometria pliometrico', 'skater' => 'patinador pliometria pliometrico',
            'clap' => 'palmada pliometria pliometrico', 'burpee' => 'pliometria pliometrico', 'twist' => 'giro rotacion', 'good morning' => 'buenos dias',
            'hyperextension' => 'hiperextension', 'wrist' => 'muneca', 'farmers walk' => 'paseo granjero',
            'leg press' => 'prensa', 'leg curl' => 'curl femoral', 'hip' => 'cadera', 'glute' => 'gluteo',
            'neck' => 'cuello', 'shoulder' => 'hombro', 'chest' => 'pecho', 'back' => 'espalda',
            'leg' => 'pierna', 'arm' => 'brazo', 'walk' => 'caminar', 'run' => 'correr', 'bike' => 'bici',
            'climber' => 'escalador', 'upright row' => 'remo al menton', 'skull' => 'rompecraneos',
            'military' => 'militar', 'preacher' => 'predicador scott', 'concentration' => 'concentrado',
            'romanian' => 'rumano', 'stiff leg' => 'piernas rigidas rumano', 'bulgarian' => 'bulgara',
            'split squat' => 'bulgara zancada estatica', 'goblet' => 'copa', 'front squat' => 'sentadilla frontal',
            'adduction' => 'aduccion', 'abduction' => 'abduccion', 'rotation' => 'rotacion', 'band' => 'banda goma',
        ];

        $nombre = mb_strtolower($c['nombre']);
        $extra = [];
        foreach ($dic as $en => $es) {
            if (str_contains($nombre, $en)) {
                $extra[] = $es;
            }
        }

        $texto = $nombre . ' ' . implode(' ', $extra) . ' '
            . gim_catalogo_etiqueta('parte', $c['parte']) . ' '
            . gim_catalogo_etiqueta('equipo', $c['equipo']) . ' '
            . gim_catalogo_etiqueta('objetivo', $c['objetivo']);

        // Sin tildes, igual que normaliza() en el JS del buscador
        return strtr(mb_strtolower($texto), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    }
}

if (!function_exists('gim_catalogo_grupo_sugerido')) {
    /** Grupo propio (gim_grupos) más probable para un ejercicio del catálogo. */
    function gim_catalogo_grupo_sugerido(array $c): string
    {
        if (preg_match('/jump|plyo|hops|skater|clap|burpee/i', $c['nombre'])) {
            return 'pliometria';
        }
        if (in_array($c['equipo'], ['leverage machine', 'sled machine'], true)) {
            return 'maquinas';
        }
        return match ($c['objetivo']) {
            'biceps'  => 'biceps',
            'triceps' => 'triceps',
            'forearms', 'levator scapulae' => 'especificos',
            default   => match ($c['parte']) {
                'upper legs', 'lower legs' => 'piernas',
                'back'      => 'espalda',
                'waist'     => 'abdominales',
                'chest'     => 'pecho',
                'shoulders' => 'hombros',
                'cardio'    => 'cardio',
                default     => 'especificos',
            },
        };
    }
}
