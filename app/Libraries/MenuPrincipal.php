<?php

namespace App\Libraries;

class MenuPrincipal
{
    public const MODOS = ['clasica' => 'Clásica', 'a' => 'A · Rejilla', 'b' => 'B · Por áreas', 'c' => 'C · Riel + Hoy'];
    public const FAVS_DEFECTO = ['journal', 'comidas/diario/hoy', 'piezas'];

    public static function modo(): string
    {
        $m = $_COOKIE['tb_menu'] ?? 'clasica';
        return isset(self::MODOS[$m]) ? $m : 'clasica';
    }

    public static function areas(): array
    {
        return [
            'dia'      => 'Día a día',
            'agenda'   => 'Agenda',
            'creacion' => 'Creación',
            'captura'  => 'Captura',
        ];
    }

    public static function modulos(): array
    {
        return [
            ['ruta' => 'comidas/diario/hoy', 'emoji' => '🍽️', 'icono' => 'bi-egg-fried', 'titulo' => 'Comida', 'area' => 'dia', 'corto' => 'Menús y dieta', 'texto' => 'Planifica tus menús, dieta y seguimiento alimenticio.'],
            ['ruta' => 'gimnasio', 'emoji' => '🏋️', 'icono' => 'bi-activity', 'titulo' => 'Gimnasio', 'area' => 'dia', 'corto' => 'Entrenos y progresos', 'texto' => 'Registra tus entrenamientos, progresos y objetivos físicos.'],
            ['ruta' => 'compras', 'emoji' => '🛒', 'icono' => 'bi-cart3', 'titulo' => 'Compras', 'area' => 'dia', 'corto' => 'Listas y gastos', 'texto' => 'Lleva control de tus compras, listas y gastos.'],
            ['ruta' => 'lentillas', 'emoji' => '👁️', 'icono' => 'bi-eye', 'titulo' => 'Lentillas', 'area' => 'dia', 'corto' => 'Cambios y reemplazos', 'texto' => 'Lleva un registro de cambios, limpieza y reemplazos.'],
            ['ruta' => 'coche', 'emoji' => '🚗', 'icono' => 'bi-car-front', 'titulo' => 'Coche', 'area' => 'agenda', 'corto' => 'Aceite, revisiones, ruedas', 'texto' => 'Controla cambios de aceite, revisiones, neumáticos y más.'],
            ['ruta' => 'youtube', 'emoji' => '▶️', 'icono' => 'bi-play-btn', 'titulo' => 'YouTube', 'area' => 'captura', 'corto' => 'Vídeos guardados', 'texto' => 'Permite revisar los vídeos guardados como interesantes.'],
            ['ruta' => 'enlaces', 'emoji' => '📒', 'icono' => 'bi-link-45deg', 'titulo' => 'Enlaces', 'area' => 'captura', 'corto' => 'Enlaces interesantes', 'texto' => 'Permite revisar los enlaces registrados interesantes.'],
            ['ruta' => 'journal', 'emoji' => '📨', 'icono' => 'bi-journal-check', 'titulo' => 'Journal', 'area' => 'agenda', 'corto' => 'Tareas y bullet journal', 'texto' => 'Permite hacer y seguir tareas y bullet journal.'],
            ['ruta' => 'reading', 'emoji' => '📖', 'icono' => 'bi-book', 'titulo' => 'Lectura', 'area' => 'agenda', 'corto' => 'Libros a tu ritmo', 'texto' => 'Sigue tus libros a tu ritmo, sin presión ni rachas.'],
            ['ruta' => 'hogar', 'emoji' => '🏠', 'icono' => 'bi-house', 'titulo' => 'Hogar', 'area' => 'dia', 'corto' => 'Limpieza por habitación', 'texto' => 'Checklist rutinario de limpieza y tareas del hogar por habitación.'],
            ['ruta' => 'recordatorios', 'emoji' => '📅', 'icono' => 'bi-calendar-event', 'titulo' => 'Recordatorios', 'area' => 'agenda', 'corto' => 'ITV, médicos, DNI…', 'texto' => 'ITV, revisiones médicas, vacunas, DNI, carnet... y cuándo tocan.'],
            ['ruta' => 'braintogram', 'emoji' => '🧠', 'icono' => 'bi-cpu', 'titulo' => 'Braintogram', 'area' => 'captura', 'corto' => 'Segundo cerebro', 'texto' => 'Log de ingesta del bot de Telegram: segundo cerebro en construcción.'],
            ['ruta' => 'buscapp', 'emoji' => '📟', 'icono' => 'bi-broadcast', 'titulo' => 'Buscapp', 'area' => 'captura', 'corto' => 'Avisos tipo busca', 'texto' => 'Avisos de llamada estilo busca/telegrama: usuarios y envíos de la app.'],
            ['ruta' => 'rodajes', 'emoji' => '🎬', 'icono' => 'bi-film', 'titulo' => 'Rodajes', 'area' => 'creacion', 'corto' => 'Escenas de un rodaje', 'texto' => 'Permite gestionar las escenas de un rodaje.'],
            ['ruta' => 'sesiones', 'emoji' => '📸', 'icono' => 'bi-camera', 'titulo' => 'Sesiones', 'area' => 'creacion', 'corto' => 'Kanban de sesiones', 'texto' => 'Kanban de sesiones de foto/vídeo: moodboard, equipo y model releases.'],
            ['ruta' => 'piezas', 'emoji' => '🧱', 'icono' => 'bi-box', 'titulo' => 'Piezas', 'area' => 'creacion', 'corto' => 'Modelos 3D y stock', 'texto' => 'Versionado de modelos 3D en Blender: qué versión es la buena y dónde está.'],
            ['ruta' => 'silo', 'emoji' => '🗄️', 'icono' => 'bi-archive', 'titulo' => 'Silo', 'area' => 'creacion', 'corto' => 'Material terminado', 'texto' => 'Archivo de material resultante: fotos y vídeos editados, clasificados y ubicados.'],
        ];
    }

    public static function favoritos(): array
    {
        $rutas = array_column(self::modulos(), 'ruta');
        $c = $_COOKIE['tb_favs'] ?? null;
        $favs = $c === null ? self::FAVS_DEFECTO : array_filter(explode(',', $c));
        return array_values(array_intersect($favs, $rutas));
    }

    public static function activo(): ?string
    {
        $uri = uri_string();
        foreach (self::modulos() as $m) {
            $base = explode('/', $m['ruta'])[0];
            if ($uri === $base || str_starts_with($uri, $base . '/')) {
                return $m['ruta'];
            }
        }
        return null;
    }
}
