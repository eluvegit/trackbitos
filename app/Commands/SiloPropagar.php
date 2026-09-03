<?php namespace App\Commands;

use App\Services\SiloPropagacionService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Recalcula la propagación a Copia 2 (año) y Copia 3 (categoría) para
 * todo lo que ya vive en Nivel 1 — normalmente no hace falta ejecutarlo a
 * mano, porque cada ingesta ya se propaga sola (SiloIngestaService), pero
 * sirve de backfill si se añaden piezas de Nivel 1 sin pasar por
 * ingestarCarpeta() o tras cambios en la lógica de propagación.
 */
class SiloPropagar extends BaseCommand
{
    protected $group       = 'custom';
    protected $name        = 'silo:propagar';
    protected $description = 'Recalcula la propagación a Copia 2/3 de todo el catálogo de Nivel 1.';

    public function run(array $params)
    {
        $propagadas = (new SiloPropagacionService())->propagarTodo();
        CLI::write("Propagación recalculada sobre {$propagadas} pieza(s) de Nivel 1.", 'green');
    }
}
