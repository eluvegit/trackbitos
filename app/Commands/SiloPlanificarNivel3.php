<?php namespace App\Commands;

use App\Services\SiloPropagacionService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Reparte las categorías de Nivel 3 entre las unidades automáticas (Nivel 3
 * sin categoría puesta a mano y con capacidad): cada una recibe varias
 * categorías enteras, sin partir nunca una entre dos (ver
 * SiloPropagacionService::calcularPlanNivel3()). Sin --aplicar es solo un
 * informe; con --aplicar hace lo mismo que el botón "Recalcular reparto"
 * para Nivel 3.
 *
 * Uso: php spark silo:planificar-nivel3 [--aplicar]
 */
class SiloPlanificarNivel3 extends BaseCommand
{
    protected $group       = 'custom';
    protected $name        = 'silo:planificar-nivel3';
    protected $description = 'Reparte las categorías de Nivel 3 entre las unidades sin categoría puesta a mano, sin partir ninguna.';
    protected $usage       = 'silo:planificar-nivel3 [--aplicar]';

    public function run(array $params)
    {
        $aplicar     = CLI::getOption('aplicar') !== null;
        $propagacion = new SiloPropagacionService();

        $plan = $propagacion->calcularPlanNivel3();
        if ($plan === []) {
            CLI::error('No hay categorías por repartir (o todas tienen ya una unidad de Nivel 3 puesta a mano).');

            return;
        }

        if ($aplicar) {
            $res = $propagacion->repartirCopia3();
            CLI::write("APLICADO: {$res['colocadas']} carpeta(s) colocada(s), {$res['pendientes']} pendiente(s).", 'yellow');
        } else {
            CLI::write('Solo informe, añade --aplicar para reconstruir Nivel 3.', 'yellow');
        }

        foreach ($plan as $run) {
            $gb = number_format($run['bytes'] / 1_000_000_000, 2);

            [$color, $marca] = match ($run['estado']) {
                'excede'     => ['red', ' -- EXCEDE la capacidad de todas las unidades, hace falta una mayor solo para esto'],
                'sin_unidad' => ['red', ' -- SIN UNIDAD con sitio: da de alta más unidades de Nivel 3'],
                default      => ['green', ''],
            };

            $unidad = $run['unidad_id'] !== null ? "unidad #{$run['unidad_id']}" : 'sin unidad';
            CLI::write(sprintf('%s: %s -> %s GB%s', $unidad, implode(', ', $run['categorias']), $gb, $marca), $color);
        }
    }
}
