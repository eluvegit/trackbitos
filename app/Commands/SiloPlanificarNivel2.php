<?php namespace App\Commands;

use App\Services\SiloPropagacionService;
use App\Services\SiloService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Reparte los años de Nivel 2 entre las unidades **ya dadas de alta** (cada
 * una con su capacidad real, no una capacidad uniforme inventada — petición
 * 2026-09-05): agrupa años consecutivos hasta llenar cada unidad en el
 * orden en que se registraron, sin fragmentar nunca un año entre dos (ver
 * SiloPropagacionService::calcularPlanNivel2()). Sin --aplicar es solo un
 * informe (no toca la BD); con --aplicar reconstruye qué buckets tiene cada
 * unidad y las ubicaciones de copia 2 — no borra ni crea unidades, así que
 * cualquier identificación física/ruta de montaje/etiqueta puesta a mano se
 * conserva.
 *
 * Uso: php spark silo:planificar-nivel2 [--aplicar]
 */
class SiloPlanificarNivel2 extends BaseCommand
{
    protected $group       = 'custom';
    protected $name        = 'silo:planificar-nivel2';
    protected $description = 'Reparte los años de Nivel 2 entre las unidades ya dadas de alta, agrupando consecutivos sin fragmentar ninguno.';
    protected $usage       = 'silo:planificar-nivel2 [--aplicar]';

    public function run(array $params)
    {
        $aplicar = CLI::getOption('aplicar') !== null;

        $propagacion = new SiloPropagacionService();
        $silo        = new SiloService();
        $plan        = $aplicar ? $propagacion->aplicarPlanNivel2() : $propagacion->calcularPlanNivel2();

        if ($plan === []) {
            CLI::error('No hay unidades de Nivel 2 con capacidad dada de alta (o no hay piezas todavía). Da de alta unidades en /silo/unidades primero.');

            return;
        }

        CLI::write($aplicar ? 'APLICADO (buckets y ubicaciones de Nivel 2 reconstruidos)' : 'Solo informe, añade --aplicar para reconstruir Nivel 2.', 'yellow');

        foreach ($plan as $run) {
            $gb    = number_format($run['bytes'] / 1_000_000_000, 2);
            $anios = $silo->comprimirAnios($run['anios']);

            [$color, $marca] = match ($run['estado']) {
                'excede'     => ['red', ' -- EXCEDE la capacidad de esa unidad, hace falta una mayor solo para esto'],
                'sin_unidad' => ['red', ' -- SIN UNIDAD: da de alta más unidades de Nivel 2'],
                default      => ['green', ''],
            };

            $unidad = $run['unidad_id'] !== null ? "unidad #{$run['unidad_id']}" : 'sin unidad';
            CLI::write(sprintf('%s: %s -> %s GB%s', $unidad, $anios, $gb, $marca), $color);
        }
    }
}
