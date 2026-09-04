<?php

/**
 * Helpers de presentación para las vistas de Silo — formateo, no lógica de
 * dominio (eso vive en SiloService). Cargado explícitamente por
 * App\Controllers\Silo\Web, no globalmente.
 */

if (!function_exists('silo_formatear_tamano')) {
    function silo_formatear_tamano(?int $bytes): string
    {
        if (!$bytes) {
            return '—';
        }
        if ($bytes >= 1_000_000_000_000) {
            return number_format($bytes / 1_000_000_000_000, 2) . ' TB';
        }
        if ($bytes >= 1_000_000_000) {
            return number_format($bytes / 1_000_000_000, 2) . ' GB';
        }

        return number_format($bytes / 1_000_000, 1) . ' MB';
    }
}

if (!function_exists('silo_capacidad_partes')) {
    /**
     * Igual que silo_formatear_tamano() pero separado en {valor, unidad}
     * para pintar el número grande y la unidad pequeña en la tarjeta de
     * unidad (`/silo/unidades`). Sin capacidad conocida, valor = '—'.
     *
     * @return array{valor: string, unidad: string}
     */
    function silo_capacidad_partes(?int $bytes): array
    {
        if (!$bytes) {
            return ['valor' => '—', 'unidad' => ''];
        }
        if ($bytes >= 1_000_000_000_000) {
            return ['valor' => number_format($bytes / 1_000_000_000_000, 2), 'unidad' => 'TB'];
        }
        if ($bytes >= 1_000_000_000) {
            return ['valor' => number_format($bytes / 1_000_000_000, 2), 'unidad' => 'GB'];
        }

        return ['valor' => number_format($bytes / 1_000_000, 1), 'unidad' => 'MB'];
    }
}

if (!function_exists('silo_fecha_humana')) {
    function silo_fecha_humana(?string $fecha): string
    {
        if (!$fecha) {
            return 'sin fecha';
        }
        $ts = strtotime($fecha);
        if ($ts === false) {
            return $fecha;
        }
        $meses = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

        return (int) date('j', $ts) . ' ' . $meses[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    }
}

if (!function_exists('silo_descripcion_carpeta')) {
    /**
     * Parte legible del nombre de carpeta: quita del principio el ID de
     * negocio y el token de fecha, deja categoría + elementos. Espera el
     * array de la pieza (usa nombre_carpeta e id_negocio).
     */
    function silo_descripcion_carpeta(array $pieza): string
    {
        $nombre = trim((string) ($pieza['nombre_carpeta'] ?? ''));
        if ($nombre === '') {
            return '';
        }

        $partes = preg_split('/\s+/', $nombre);
        $id     = (string) ($pieza['id_negocio'] ?? '');

        if (isset($partes[0]) && ($partes[0] === $id || ctype_digit($partes[0]))) {
            array_shift($partes);
        }
        if (isset($partes[0]) && (ctype_digit($partes[0]) || $partes[0] === 'sinfecha')) {
            array_shift($partes);
        }

        return implode(' ', $partes);
    }
}

if (!function_exists('silo_icono_vocabulario')) {
    function silo_icono_vocabulario(string $tipo): string
    {
        return match ($tipo) {
            'persona'   => 'bi-person-fill',
            'lugar'     => 'bi-geo-alt-fill',
            'evento'    => 'bi-calendar-event',
            'categoria' => 'bi-collection-fill',
            'tema'      => 'bi-folder2',
            default     => 'bi-tag-fill',
        };
    }
}

if (!function_exists('silo_badges_carpeta')) {
    /**
     * Nombre de carpeta como badges. Las personas van solo con icono +
     * nombre (sin fondo); el lugar y el tema con fondo gris claro; la
     * categoría con fondo ámbar. Con $enBloques la galería saca un tipo de
     * vocabulario por línea (categoría, tema, lugar, personas); sin él
     * (listado) va todo en la misma línea, envolviendo. Espera la pieza con
     * `categoria_nombre` y `atributos` [{tipo, nombre}] ya resueltos
     * (SiloPiezaModel los adjunta).
     */
    function silo_badges_carpeta(array $pieza, bool $enBloques = false): string
    {
        $porTipo = [];

        $cat = trim((string) ($pieza['categoria_nombre'] ?? ''));
        if ($cat !== '' && strtolower($cat) !== 'sin_clasificar') {
            $porTipo['categoria'][] = $cat;
        }
        foreach ($pieza['atributos'] ?? [] as $a) {
            $porTipo[(string) $a['tipo']][] = (string) $a['nombre'];
        }

        if ($porTipo === []) {
            return '<span class="badge text-bg-secondary fw-normal">'
                . '<i class="bi bi-folder2 me-1"></i>sin clasificar</span>';
        }

        // Clase del badge por tipo; '' = sin fondo, solo icono + nombre.
        $clasePorTipo = [
            'categoria' => 'text-bg-warning',
            'evento'    => 'text-bg-info',
            'lugar'     => 'bg-secondary-subtle text-secondary-emphasis border',
            'persona'   => '',
            'tema'      => 'text-bg-light border',
        ];
        // Orden de las líneas; los tipos no listados van al final.
        $orden = ['categoria', 'evento', 'tema', 'lugar', 'persona'];
        $tipos = array_merge(
            array_values(array_intersect($orden, array_keys($porTipo))),
            array_values(array_diff(array_keys($porTipo), $orden)),
        );

        $salida = '';
        foreach ($tipos as $tipo) {
            $icono = silo_icono_vocabulario($tipo);
            $clase = $clasePorTipo[$tipo] ?? 'text-bg-secondary';
            $trozos = '';
            foreach ($porTipo[$tipo] as $nombre) {
                if ($clase === '') {
                    // Mismo tamaño que los badges (.badge => font-size .75em),
                    // pero sin fondo ni relleno: solo icono + nombre.
                    $trozos .= '<span class="badge fw-normal bg-transparent text-body p-0 me-2 text-nowrap">'
                        . '<i class="bi ' . $icono . ' me-1"></i>'
                        . esc($nombre) . '</span>';
                } else {
                    $trozos .= '<span class="badge ' . $clase . ' fw-normal me-1">'
                        . '<i class="bi ' . $icono . ' me-1"></i>'
                        . esc($nombre) . '</span>';
                }
            }
            $salida .= $enBloques ? '<span class="d-block mt-1">' . $trozos . '</span>' : $trozos;
        }

        return trim($salida);
    }
}

if (!function_exists('silo_icono_unidad')) {
    /**
     * Dibujo (SVG inline, trazo `currentColor`) del tipo físico de una
     * unidad para las tarjetas de `/silo/unidades`: USB, disco interno,
     * disco externo. Sin tipo conocido, icono genérico de Bootstrap Icons.
     * Devuelve HTML crudo — el llamador lo echa sin escapar.
     */
    function silo_icono_unidad(?string $tipo, ?int $tam = null): string
    {
        // Sin $tam: sin width/height/font-size propios, para que el CSS de
        // la página lo ajuste al hueco disponible (tarjetas de
        // /silo/unidades). Con $tam: tamaño fijo en píxeles (selector del
        // modal). `silo-hdd` es la clase compartida de _estilos_nivel.php
        // que tiñe el icono con el acento del nivel que lo envuelve.
        $tamanoAttr = $tam !== null ? ' width="' . $tam . '" height="' . $tam . '"' : '';
        $atributos = $tamanoAttr . ' viewBox="0 0 64 64" fill="none" '
            . 'stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" '
            . 'class="silo-icono-unidad silo-hdd" aria-hidden="true"';

        return match ($tipo) {
            // Pendrive: cuerpo redondeado arriba, conector metálico estrecho
            // abajo con su línea de contacto, piloto como puntito.
            'usb' => "<svg {$atributos}>"
                . '<rect x="20" y="6" width="24" height="34" rx="7"/>'
                . '<rect x="26" y="40" width="12" height="16" rx="2"/>'
                . '<line x1="26" y1="46" x2="38" y2="46"/>'
                . '<circle cx="32" cy="18" r="2.2" fill="currentColor" stroke="none"/>'
                . '</svg>',
            // Disco interno: carcasa plana vista desde arriba, tornillos en
            // las esquinas, etiqueta rectangular y pines de conexión abajo.
            'hdd_interno' => "<svg {$atributos}>"
                . '<rect x="8" y="12" width="48" height="34" rx="4"/>'
                . '<rect x="14" y="18" width="24" height="10" rx="2"/>'
                . '<circle cx="12" cy="16" r="1.6" fill="currentColor" stroke="none"/>'
                . '<circle cx="52" cy="16" r="1.6" fill="currentColor" stroke="none"/>'
                . '<circle cx="12" cy="42" r="1.6" fill="currentColor" stroke="none"/>'
                . '<circle cx="52" cy="42" r="1.6" fill="currentColor" stroke="none"/>'
                . '<line x1="8" y1="50" x2="20" y2="50"/>'
                . '<line x1="24" y1="50" x2="40" y2="50"/>'
                . '</svg>',
            // Disco externo: carcasa redondeada con piloto circular y un
            // cable que sale hacia un conector — lo que lo distingue del
            // interno es justamente el cable.
            'hdd_externo' => "<svg {$atributos}>"
                . '<rect x="10" y="10" width="34" height="34" rx="6"/>'
                . '<circle cx="27" cy="27" r="8"/>'
                . '<circle cx="27" cy="27" r="2" fill="currentColor" stroke="none"/>'
                . '<path d="M44 34 C 50 34, 50 44, 56 44"/>'
                . '<rect x="54" y="40" width="8" height="8" rx="1.5"/>'
                . '</svg>',
            default => '<i class="bi bi-hdd silo-icono-unidad silo-hdd"'
                . ($tam !== null ? ' style="font-size:' . $tam . 'px"' : '')
                . ' aria-hidden="true"></i>',
        };
    }
}

if (!function_exists('silo_icono_tipo')) {
    function silo_icono_tipo(string $tipo): string
    {
        return match ($tipo) {
            'foto'  => 'bi-image',
            'video' => 'bi-camera-video',
            default => 'bi-file-earmark',
        };
    }
}
