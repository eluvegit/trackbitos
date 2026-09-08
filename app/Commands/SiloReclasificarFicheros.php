<?php namespace App\Commands;

use App\Models\SiloFicheroModel;
use App\Services\SiloIngestaService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Reetiqueta el `tipo` (foto/video/otro) de todos los `silo_ficheros` ya
 * ingestados según la lista de extensiones ACTUAL de
 * SiloIngestaService::tipoDeExtension(). Se usa después de ampliar esa
 * lista (p. ej. añadir .mpg a vídeo) para que las estadísticas de "lo que
 * más ocupa" y los proxies dejen de contar esos ficheros como "otro" sin
 * tener que re-escanear el Maestro entero.
 *
 * `--dry-run` enseña el reparto de cambios sin tocar la base de datos.
 */
class SiloReclasificarFicheros extends BaseCommand
{
    protected $group       = 'custom';
    protected $name        = 'silo:reclasificar-ficheros';
    protected $description  = 'Recalcula silo_ficheros.tipo (foto/video/otro) por extensión con la lista actual. --dry-run para simular.';
    protected $usage        = 'silo:reclasificar-ficheros [--dry-run]';

    public function run(array $params)
    {
        $dryRun = array_key_exists('dry-run', $params) || in_array('--dry-run', $params, true);
        $model  = new SiloFicheroModel();

        // Solo columnas necesarias; sin returnType objeto para ir ligero.
        $filas = $model->select('id, nombre, tipo')->findAll();
        if ($filas === []) {
            CLI::write('No hay ficheros en silo_ficheros.', 'yellow');
            return;
        }

        // id agrupado por el tipo NUEVO, solo para los que cambian.
        $porNuevoTipo = ['foto' => [], 'video' => [], 'otro' => []];
        $transiciones = [];
        foreach ($filas as $f) {
            $nuevo = SiloIngestaService::tipoDeExtension($f['nombre']);
            if ($nuevo === $f['tipo']) {
                continue;
            }
            $porNuevoTipo[$nuevo][] = (int) $f['id'];
            $clave = ($f['tipo'] ?: 'null') . ' -> ' . $nuevo;
            $transiciones[$clave] = ($transiciones[$clave] ?? 0) + 1;
        }

        $totalCambios = array_sum(array_map('count', $porNuevoTipo));
        if ($totalCambios === 0) {
            CLI::write('Todo ya está bien clasificado (' . count($filas) . ' ficheros revisados).', 'green');
            return;
        }

        CLI::write(($dryRun ? '[dry-run] ' : '') . "Cambios ({$totalCambios} de " . count($filas) . '):', 'yellow');
        foreach ($transiciones as $clave => $n) {
            CLI::write("  {$clave}: {$n}");
        }

        if ($dryRun) {
            CLI::write('Nada escrito (--dry-run).', 'yellow');
            return;
        }

        $db = $model->db();
        foreach ($porNuevoTipo as $tipo => $ids) {
            if ($ids === []) {
                continue;
            }
            foreach (array_chunk($ids, 500) as $lote) {
                $db->table('silo_ficheros')->whereIn('id', $lote)->update(['tipo' => $tipo]);
            }
            CLI::write('  -> ' . count($ids) . " ficheros marcados como '{$tipo}'.", 'green');
        }

        CLI::write('Hecho. Revisa /silo/ranking.', 'green');
    }
}
