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
