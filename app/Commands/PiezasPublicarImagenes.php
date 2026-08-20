<?php

namespace App\Commands;

use App\Models\PiezaReferenciaModel;
use App\Models\PiezaRenderModel;
use App\Services\PiezaAlmacen;
use App\Services\PiezaImagenesPublicas;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Genera las copias públicas (miniatura y vista) de las imágenes de Piezas
 * que ya están subidas — las de un servidor que venía sirviéndolas por
 * controlador, o las de una máquina donde se acaba de clonar el repositorio.
 *
 * Hay que pasarlo una vez tras desplegar. Antes de hacerlo nada se rompe: la
 * galería sigue tirando del controlador (`imagen_pieza()` cae solo), pero va
 * lenta y se cuelgan imágenes cuando hay muchas en pantalla, que es
 * justamente lo que esto viene a arreglar.
 *
 * Es idempotente: lo ya publicado se salta, así que se puede repetir sin
 * pensar. Con `--rehacer` se regenera todo, para cuando se cambie el tamaño
 * o la calidad de los derivados.
 */
class PiezasPublicarImagenes extends BaseCommand
{
    protected $group       = 'Piezas';
    protected $name        = 'piezas:publicar-imagenes';
    protected $description = 'Genera en public/piezas-img las miniaturas y vistas de renders y referencias.';
    protected $usage       = 'piezas:publicar-imagenes [--rehacer]';
    protected $options     = ['--rehacer' => 'Regenera también las que ya estaban publicadas.'];

    public function run(array $params)
    {
        $rehacer = array_key_exists('rehacer', CLI::getOptions());

        $almacen  = new PiezaAlmacen();
        $publicas = new PiezaImagenesPublicas();

        if (!function_exists('imagewebp')) {
            CLI::error('Este PHP no tiene WebP en GD, y los derivados se guardan en WebP.');
            CLI::write('Comprueba la extensión gd con: php -r "print_r(gd_info());"');

            return;
        }

        $publicadas = 0;
        $saltadas   = 0;
        $sinFichero = 0;
        $fallidas   = 0;

        foreach ([['render', new PiezaRenderModel()], ['referencia', new PiezaReferenciaModel()]] as [$tipo, $modelo]) {
            foreach ($modelo->findAll() as $registro) {
                $hash = $registro['hash_imagen'] ?? null;
                $ruta = $registro['ruta_imagen'] ?? null;

                // Sin hash no hay nombre de fichero que valga: el nombre
                // público ES el hash. Se recalcula del original en vez de
                // dejarlo fuera, que es lo que pasa con los registros
                // antiguos que se guardaron antes de que hubiera columna.
                if (!$almacen->existe($ruta)) {
                    $sinFichero++;
                    CLI::write(CLI::color("  · {$tipo} #{$registro['id']}: no está el original ({$ruta})", 'yellow'));
                    continue;
                }

                if (!$hash) {
                    $hash = $almacen->hash($ruta);
                    $modelo->update($registro['id'], ['hash_imagen' => $hash]);
                }

                try {
                    $hechos = $publicas->publicar($almacen->absoluta($ruta), $hash, $rehacer);
                } catch (Throwable $e) {
                    $fallidas++;
                    CLI::error("  · {$tipo} #{$registro['id']}: {$e->getMessage()}");
                    continue;
                }

                if ($hechos === []) {
                    $saltadas++;
                    continue;
                }

                $publicadas++;
                CLI::write("  · {$tipo} #{$registro['id']}: " . implode(' + ', $hechos));
            }
        }

        CLI::newLine();
        CLI::write(CLI::color("Publicadas: {$publicadas}", 'green')
            . CLI::color("  ·  ya estaban: {$saltadas}", 'white')
            . ($sinFichero ? CLI::color("  ·  sin original: {$sinFichero}", 'yellow') : '')
            . ($fallidas ? CLI::color("  ·  con error: {$fallidas}", 'red') : ''));
        CLI::write('Ocupan ' . number_format($publicas->tamanoTotal() / 1024, 0, ',', '.') . ' KB en public/'
            . PiezaImagenesPublicas::DIRECTORIO . '.');
    }
}
