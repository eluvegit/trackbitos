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

if (!function_exists('gim_vista')) {
    /**
     * Vista nueva (estilo fv) o clásica para todo el módulo, igual que en
     * Journal y Piezas: ?nueva=0 / ?nueva=1 la fija en la sesión y por
     * defecto se ve la nueva.
     */
    function gim_vista(string $vista): string
    {
        $nueva = service('request')->getGet('nueva');
        if ($nueva !== null) {
            session()->set('gimnasio_nueva', $nueva === '1');
        }
        return session('gimnasio_nueva') !== false ? $vista . '_nueva' : $vista;
    }
}

if (!function_exists('gim_tipos')) {
    /** Tipos de sesión con su color (calendario, chips y leyendas). */
    function gim_tipos(): array
    {
        return [
            'Fuerza'       => '#ff6a3d',
            'Hipertrofia'  => '#8e8cff',
            'Cardio'       => '#3ecf8e',
            'HIIT'         => '#f5a524',
            'Escalada'     => '#4cc9f0',
            'Movilidad'    => '#c084fc',
            'Recuperación' => '#94a3b8',
        ];
    }
}

if (!function_exists('gim_tipo_color')) {
    function gim_tipo_color(?string $tipo): string
    {
        return gim_tipos()[$tipo ?? ''] ?? '#71717a';
    }
}

if (!function_exists('gim_kg')) {
    /** 60 → "60", 62.5 → "62,5". */
    function gim_kg($peso): string
    {
        $p = (float) $peso;
        $txt = floor($p) == $p ? (string) (int) $p : rtrim(rtrim(number_format($p, 3, '.', ''), '0'), '.');
        return str_replace('.', ',', $txt);
    }
}

if (!function_exists('gim_num')) {
    /** Miles con punto: 12500 → "12.500". */
    function gim_num($n): string
    {
        return number_format((float) $n, 0, ',', '.');
    }
}

if (!function_exists('gim_fecha_corta')) {
    /** "lun 3 nov" (con año si no es el actual). */
    function gim_fecha_corta(string $fecha, bool $diaSemana = true): string
    {
        static $dias = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
        static $meses = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        $t = strtotime($fecha);
        $txt = (int) date('j', $t) . ' ' . $meses[(int) date('n', $t)];
        if (date('Y', $t) !== date('Y')) {
            $txt .= ' ' . date('Y', $t);
        }
        return $diaSemana ? $dias[(int) date('w', $t)] . ' ' . $txt : $txt;
    }
}

if (!function_exists('gim_mes_corto')) {
    /** "2026-11-03" → "nov". */
    function gim_mes_corto(string $fecha): string
    {
        return ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'][(int) date('n', strtotime($fecha))];
    }
}

if (!function_exists('gim_hace')) {
    /** "hoy", "ayer", "hace 3 días", "hace 2 sem", "en 2 días". */
    function gim_hace(string $fecha): string
    {
        $d = (int) (new DateTime('today'))->diff(new DateTime(date('Y-m-d', strtotime($fecha))))->format('%r%a');
        return match (true) {
            $d === 0  => 'hoy',
            $d === -1 => 'ayer',
            $d === 1  => 'mañana',
            $d > 1    => 'en ' . $d . ' días',
            $d > -14  => 'hace ' . abs($d) . ' días',
            $d > -60  => 'hace ' . intdiv(abs($d), 7) . ' sem',
            default   => 'hace ' . intdiv(abs($d), 30) . ' meses',
        };
    }
}

if (!function_exists('gim_meses')) {
    function gim_meses(): array
    {
        return [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    }
}

if (!function_exists('gim_linea')) {
    /**
     * Gráfica de línea con área en SVG (estilo fv). $puntos lleva 'fecha' y
     * el campo indicado; el récord ($prFecha) va marcado con un punto.
     */
    function gim_linea(array $puntos, string $campo = 'e1rm', ?string $prFecha = null, int $w = 640, int $h = 160): string
    {
        if (count($puntos) < 2) {
            return '';
        }
        $vals = array_map(fn ($p) => (float) $p[$campo], $puntos);
        $min = min($vals);
        $max = max($vals);
        if ($max == $min) {
            $max += 1;
            $min -= 1;
        }
        $padX = 8;
        $padT = 14;
        $padB = 18;
        $n = count($puntos);
        $xy = [];
        foreach ($puntos as $i => $p) {
            $x = $padX + ($i / ($n - 1)) * ($w - 2 * $padX);
            $y = $padT + (1 - (((float) $p[$campo] - $min) / ($max - $min))) * ($h - $padT - $padB);
            $xy[] = [round($x, 1), round($y, 1), $p];
        }
        $linea = implode(' ', array_map(fn ($c) => $c[0] . ',' . $c[1], $xy));
        $area = $xy[0][0] . ',' . ($h - $padB) . ' ' . $linea . ' ' . end($xy)[0] . ',' . ($h - $padB);
        $gid = 'g' . substr(md5($linea), 0, 6);

        $svg = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" class="gm-svg" role="img">'
            . '<defs><linearGradient id="' . $gid . '" x1="0" x2="0" y1="0" y2="1">'
            . '<stop offset="0" stop-color="var(--fv-accent)" stop-opacity=".28"/><stop offset="1" stop-color="var(--fv-accent)" stop-opacity="0"/>'
            . '</linearGradient></defs>'
            . '<line x1="0" x2="' . $w . '" y1="' . $padT . '" y2="' . $padT . '" class="gm-svg-guia"/>'
            . '<line x1="0" x2="' . $w . '" y1="' . ($h - $padB) . '" y2="' . ($h - $padB) . '" class="gm-svg-guia"/>'
            . '<polygon points="' . $area . '" fill="url(#' . $gid . ')"/>'
            . '<polyline points="' . $linea . '" fill="none" stroke="var(--fv-accent)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>';
        foreach ($xy as [$x, $y, $p]) {
            $esPr = $prFecha !== null && $p['fecha'] === $prFecha;
            $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="' . ($esPr ? 4.5 : 2.2) . '" class="' . ($esPr ? 'gm-svg-pr' : 'gm-svg-p') . '">'
                . '<title>' . esc(gim_fecha_corta($p['fecha'], false)) . ' · ' . gim_kg($p[$campo]) . '</title></circle>';
        }
        $svg .= '<text x="' . $padX . '" y="' . ($h - 4) . '" class="gm-svg-t">' . esc(gim_fecha_corta($puntos[0]['fecha'], false)) . '</text>'
            . '<text x="' . ($w - $padX) . '" y="' . ($h - 4) . '" text-anchor="end" class="gm-svg-t">' . esc(gim_fecha_corta(end($puntos)['fecha'], false)) . '</text>'
            . '<text x="' . ($w - $padX) . '" y="10" text-anchor="end" class="gm-svg-t">' . gim_kg(round($max, 1)) . '</text>'
            . '</svg>';
        return $svg;
    }
}

if (!function_exists('gim_barras')) {
    /** Barras verticales finas en SVG (volumen por sesión). */
    function gim_barras(array $puntos, string $campo = 'volumen', int $w = 640, int $h = 70): string
    {
        $puntos = array_slice($puntos, -60);
        if (!$puntos) {
            return '';
        }
        $max = max(array_map(fn ($p) => (float) $p[$campo], $puntos)) ?: 1;
        $n = count($puntos);
        $paso = $w / max($n, 12);
        $ancho = max(2, $paso * .62);
        $svg = '<svg viewBox="0 0 ' . $w . ' ' . $h . '" class="gm-svg gm-svg-bar" preserveAspectRatio="none" role="img">';
        foreach ($puntos as $i => $p) {
            $alto = max(1.5, ((float) $p[$campo] / $max) * ($h - 4));
            $svg .= '<rect x="' . round($i * $paso, 1) . '" y="' . round($h - $alto, 1) . '" width="' . round($ancho, 1) . '" height="' . round($alto, 1) . '" rx="1.5" class="gm-svg-barra">'
                . '<title>' . esc(gim_fecha_corta($p['fecha'], false)) . ' · ' . gim_num($p[$campo]) . ' kg</title></rect>';
        }
        return $svg . '</svg>';
    }
}

if (!function_exists('gim_heatmap')) {
    /**
     * Calendario de días entrenados estilo GitHub: columnas = semanas (lunes
     * arriba), color = tipo de sesión. $dias viene de GimnasioStats::dias().
     * Los días sin entreno son botones con data-fecha (para crear uno).
     */
    function gim_heatmap(array $dias, int $semanas = 53, ?string $hasta = null): string
    {
        $fin = new DateTime($hasta ?? 'today');
        $inicio = (clone $fin)->modify('monday this week')->modify('-' . ($semanas - 1) . ' weeks');
        $hoy = date('Y-m-d');
        $mesesCortos = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

        $cols = '';
        $etiquetas = '';
        $mesAnterior = null;
        $d = clone $inicio;
        for ($s = 0; $s < $semanas; $s++) {
            $mes = (int) $d->format('n');
            $etiquetas .= '<span>' . ($mes !== $mesAnterior ? $mesesCortos[$mes] : '') . '</span>';
            $mesAnterior = $mes;

            $cols .= '<div class="gm-hm-col">';
            for ($i = 0; $i < 7; $i++) {
                $f = $d->format('Y-m-d');
                if ($f > $fin->format('Y-m-d')) {
                    $cols .= '<i class="fut"></i>';
                } elseif (isset($dias[$f])) {
                    $x = $dias[$f];
                    $tit = gim_fecha_corta($f) . ($x['tipo'] ? ' · ' . $x['tipo'] : '') . ' · ' . $x['ejercicios'] . ' ejercicios';
                    $cols .= '<a href="' . site_url('gimnasio/entrenamientos/registro/' . $x['id']) . '" class="si' . ($f === $hoy ? ' hoy' : '') . '" style="--c:' . gim_tipo_color($x['tipo']) . '" title="' . esc($tit) . '"></a>';
                } else {
                    $cols .= '<button type="button" data-fecha="' . $f . '"' . ($f === $hoy ? ' class="hoy"' : '') . ' title="' . esc(gim_fecha_corta($f)) . '"></button>';
                }
                $d->modify('+1 day');
            }
            $cols .= '</div>';
        }

        return '<div class="gm-hm" style="--sem:' . $semanas . '">'
            . '<div class="gm-hm-dias"><span>L</span><span></span><span>X</span><span></span><span>V</span><span></span><span>D</span></div>'
            . '<div class="gm-hm-scroll"><div class="gm-hm-meses">' . $etiquetas . '</div><div class="gm-hm-grid">' . $cols . '</div></div>'
            . '</div>';
    }
}

if (!function_exists('gim_calendario_mes')) {
    /**
     * Calendario de un mes (lunes primero). Días con entreno → enlace a la
     * sesión; días vacíos pasados o de hoy → botón submit con name="fecha"
     * (va dentro de un formulario que crea el entreno de ese día).
     */
    function gim_calendario_mes(array $dias, string $ym): string
    {
        $primero = new DateTime($ym . '-01');
        $hoy = date('Y-m-d');
        $html = '<div class="gm-cal"><div class="gm-cal-h">' . gim_meses()[(int) $primero->format('n')] . ' <span>' . $primero->format('Y') . '</span></div>'
            . '<div class="gm-cal-g"><b>L</b><b>M</b><b>X</b><b>J</b><b>V</b><b>S</b><b>D</b>';
        $html .= str_repeat('<i></i>', (int) $primero->format('N') - 1);
        $nDias = (int) $primero->format('t');
        for ($d = 1; $d <= $nDias; $d++) {
            $f = $primero->format('Y-m-') . str_pad((string) $d, 2, '0', STR_PAD_LEFT);
            $esHoy = $f === $hoy ? ' hoy' : '';
            if (isset($dias[$f])) {
                $x = $dias[$f];
                $tit = gim_fecha_corta($f) . ($x['tipo'] ? ' · ' . $x['tipo'] : '') . ' · ' . $x['ejercicios'] . ' ejercicios';
                $html .= '<a class="si' . $esHoy . '" style="--c:' . gim_tipo_color($x['tipo']) . '" href="' . site_url('gimnasio/entrenamientos/registro/' . $x['id']) . '" title="' . esc($tit) . '">'
                    . $d . '<small>' . esc($x['tipo'] ? mb_substr($x['tipo'], 0, 5) : $x['ejercicios'] . ' ej') . '</small></a>';
            } elseif ($f > $hoy) {
                $html .= '<span class="fut">' . $d . '</span>';
            } else {
                $html .= '<button name="fecha" value="' . $f . '" class="no' . $esHoy . '" title="Apuntar el entreno del ' . esc(gim_fecha_corta($f)) . '">' . $d . '<small>+</small></button>';
            }
        }
        return $html . '</div></div>';
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
