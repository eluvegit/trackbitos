<?php

/**
 * Icono de una guía del TPV: un icono de Bootstrap Icons ("bi-…") o uno
 * propio dibujado aquí ("svg-…"), para lo que Bootstrap no tiene (p. ej.
 * un brazo). Los propios usan currentColor y miden 1em, como los de bi.
 */
if (!function_exists('icono_guia')) {
    function icono_guia(string $icono, string $clase = ''): string
    {
        $propios = [
            // Brazo de muñeco: cónico, tetón del hombro a un lado, muñeca
            // estrecha y mano en C abierta hacia abajo.
            // [viewBox, contenido]: el viewBox deja margen alrededor y centra el dibujo.
            'svg-brazo' => ['-1.75 -0.6 18 18', '<circle cx="4.6" cy="3.3" r="1.3" fill="currentColor" stroke="none"/>'
                . '<rect x="4.9" y="2.75" width="1.8" height="1.1" fill="currentColor" stroke="none"/>'
                . '<path d="M6.4 3.6 Q6.3 1.6 7.6 1.1 Q8.6 0.7 9.7 1.1 Q11.2 1.7 11 3.9 L10.2 10.3 Q10.1 11.1 9.3 11.1 H8 Q7.2 11.1 7.1 10.3 Z" fill="currentColor" stroke="none"/>'
                . '<rect x="8" y="10.9" width="1.3" height="1.3" fill="currentColor" stroke="none"/>'
                . '<path d="M7.1 15.3 A1.9 1.9 0 1 1 10.2 15.3" stroke-width="1.5"/>'],
        ];

        if (isset($propios[$icono])) {
            [$caja, $dibujo] = $propios[$icono];

            return '<svg class="icono-guia ' . esc($clase, 'attr') . '" viewBox="' . $caja . '" width="1em" height="1em" fill="none" '
                . 'stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" '
                . 'style="vertical-align: -0.125em;">' . $dibujo . '</svg>';
        }

        return '<i class="bi ' . esc($icono, 'attr') . ' ' . esc($clase, 'attr') . '"></i>';
    }
}
