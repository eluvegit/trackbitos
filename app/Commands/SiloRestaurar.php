<?php namespace App\Commands;

use App\Services\SiloCatalogoService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Restaura el catálogo de Silo (todas las tablas `silo_*`) desde la réplica
 * que el agente deja en la raíz de cada unidad (`.catalogo.sql.gz`, ver
 * SiloCatalogoService). Enseña el sello de la réplica (y su
 * `.catalogo.meta.json` si está al lado) frente a la BD viva y pide
 * confirmación; antes de tocar nada guarda el estado actual en
 * writable/silo/ por si hay que deshacerlo (restaurando ese fichero).
 */
class SiloRestaurar extends BaseCommand
{
    protected $group       = 'custom';
    protected $name        = 'silo:restaurar';
    protected $description = 'Restaura las tablas de Silo desde una réplica .catalogo.sql.gz de una unidad.';
    protected $usage       = 'silo:restaurar <ruta al .catalogo.sql.gz> [--si]';
    protected $arguments   = ['ruta' => 'El .catalogo.sql.gz (p. ej. J:\\.catalogo.sql.gz).'];
    protected $options     = ['--si' => 'No preguntar (para scripts).'];

    public function run(array $params)
    {
        $ruta = $params[0] ?? null;
        // spark trabaja desde public/: una ruta relativa se entiende desde la raíz del proyecto.
        if ($ruta && !is_file($ruta) && is_file(ROOTPATH . $ruta)) {
            $ruta = ROOTPATH . $ruta;
        }
        if (!$ruta || !is_file($ruta)) {
            CLI::error('Indica la ruta a un .catalogo.sql.gz existente.');
            CLI::write('Uso: php spark ' . $this->usage);

            return EXIT_ERROR;
        }

        $servicio = new SiloCatalogoService();
        $vivo     = $servicio->metaActual();

        $rutaMeta = preg_replace('/\.sql\.gz$/', '.meta.json', $ruta);
        $meta     = is_file($rutaMeta) ? (json_decode((string) file_get_contents($rutaMeta), true) ?: []) : [];

        CLI::write('Réplica: ' . $ruta, 'yellow');
        if ($meta) {
            CLI::write('  generada:       ' . ($meta['generado_en'] ?? '?') . ' (unidad ' . ($meta['unidad_origen'] ?? '?') . ')');
            CLI::write('  último evento:  #' . ($meta['ultimo_evento_id'] ?? '?') . '   (BD viva: #' . $vivo['ultimo_evento_id'] . ')');
            CLI::write('  esquema:        ' . ($meta['version_esquema'] ?? '?') . '   (BD viva: ' . ($vivo['version_esquema'] ?? '?') . ')');
            CLI::write('  piezas:         ' . ($meta['tablas']['silo_piezas'] ?? '?') . '   (BD viva: ' . ($vivo['tablas']['silo_piezas'] ?? '?') . ')');
            if (($meta['version_esquema'] ?? null) !== ($vivo['version_esquema'] ?? null)) {
                CLI::write('  ! El esquema no coincide: tras restaurar, ejecuta `php spark migrate` si la réplica es más antigua.', 'red');
            }
        } else {
            CLI::write('  (sin .catalogo.meta.json al lado: no se puede comparar con la BD viva)');
        }

        if (!CLI::getOption('si') && CLI::prompt('¿Sustituir TODAS las tablas silo_* de la BD viva por esta réplica?', ['n', 's']) !== 's') {
            CLI::write('Cancelado, no se ha tocado nada.');

            return EXIT_SUCCESS;
        }

        try {
            $resultado = $servicio->restaurar($ruta);
        } catch (\Throwable $e) {
            CLI::error('Error restaurando: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write("Restaurado: {$resultado['sentencias']} sentencia(s).", 'green');
        CLI::write('Copia del estado anterior (para deshacer, restáurala igual): ' . $resultado['copia_previa']);

        return EXIT_SUCCESS;
    }
}
