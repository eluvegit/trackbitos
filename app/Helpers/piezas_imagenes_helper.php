<?php

use App\Services\PiezaImagenesPublicas;

/**
 * La URL con la que pintar una imagen de Piezas en una vista.
 *
 * Devuelve el fichero estático de `public/piezas-img` cuando está publicado
 * —que es lo normal y lo que hace que una galería de treinta miniaturas no
 * dispare treinta arranques del framework— y cae al controlador de siempre
 * cuando todavía no lo está. Esa caída es lo que permite subir este código a
 * un servidor donde ya hay imágenes sin publicar: se ven igual, más lentas,
 * hasta que se pase `php spark piezas:publicar-imagenes`.
 *
 * @param array<string, mixed> $registro fila de piezas_renders o piezas_referencias
 * @param string               $tipo     'render' o 'referencia', para la URL de respaldo
 */
if (!function_exists('imagen_pieza')) {
    function imagen_pieza(array $registro, string $tipo, string $tamano = PiezaImagenesPublicas::MINIATURA): string
    {
        // Estática y no `service()`: el módulo instancia sus servicios a
        // mano (ver el constructor de Piezas\Web), y esto se llama una vez
        // por imagen — no tiene sentido rehacerlo en cada vuelta del bucle.
        static $publicas = null;
        $publicas ??= new PiezaImagenesPublicas();

        return $publicas->url($registro['hash_imagen'] ?? null, $tamano)
            ?? site_url("piezas/{$tipo}/" . (int) $registro['id'] . '/imagen');
    }
}
