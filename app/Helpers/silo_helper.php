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
            'categoria' => 'bi-folder-fill',
            'tema'      => 'bi-folder2',
            default     => 'bi-tag-fill',
        };
    }
}

if (!function_exists('silo_badges_carpeta')) {
    /**
     * Nombre de carpeta como badges con icono según el tipo: categoría y
     * tema con icono de carpeta, personas con perfil, lugares con
     * dirección. Espera la pieza con `categoria_nombre` y `atributos`
     * [{tipo, nombre}] ya resueltos (SiloPiezaModel los adjunta).
     */
    function silo_badges_carpeta(array $pieza): string
    {
        $items = [];

        $cat = trim((string) ($pieza['categoria_nombre'] ?? ''));
        if ($cat !== '' && strtolower($cat) !== 'sin_clasificar') {
            $items[] = ['tipo' => 'categoria', 'nombre' => $cat];
        }
        foreach ($pieza['atributos'] ?? [] as $a) {
            $items[] = ['tipo' => (string) $a['tipo'], 'nombre' => (string) $a['nombre']];
        }

        if ($items === []) {
            return '<span class="badge text-bg-light border text-muted fw-normal">'
                . '<i class="bi bi-folder2 me-1"></i>sin clasificar</span>';
        }

        $clasePorTipo = [
            'categoria' => 'text-bg-warning',
            'tema'      => 'text-bg-light border',
            'persona'   => 'text-bg-primary',
            'lugar'     => 'text-bg-success',
            'evento'    => 'text-bg-info',
        ];

        $html = '';
        foreach ($items as $it) {
            $clase = $clasePorTipo[$it['tipo']] ?? 'text-bg-secondary';
            $html .= '<span class="badge ' . $clase . ' fw-normal">'
                . '<i class="bi ' . silo_icono_vocabulario($it['tipo']) . ' me-1"></i>'
                . esc($it['nombre']) . '</span> ';
        }

        return trim($html);
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
