<?php

namespace App\Commands;

use App\Services\PiezaAlmacen;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Vacía la papelera del almacén de Piezas pasados N días (invariante 6:
 * nada se borra, se aparta; y lo apartado caduca a los 30 días).
 *
 * Pensado para un cron diario en el servidor:
 *     php /ruta/a/trackbitos/spark piezas:purgar
 *
 * Es el único punto del módulo que borra ficheros de verdad, y solo puede
 * tocar lo que ya lleva un mes en la papelera — el plazo de gracia es lo que
 * hace que este borrado sea seguro.
 */
class PiezasPurgar extends BaseCommand
{
    protected $group       = 'Piezas';
    protected $name        = 'piezas:purgar';
    protected $description = 'Vacía la papelera de Piezas (ficheros apartados hace más de N días).';
    protected $usage       = 'piezas:purgar [dias]';
    protected $arguments   = ['dias' => 'Días de gracia antes de borrar. Por defecto, 30.'];

    public function run(array $params)
    {
        $dias = isset($params[0]) ? max(1, (int) $params[0]) : 30;

        $borrados = (new PiezaAlmacen())->purgarPapelera($dias);

        if (!$borrados) {
            CLI::write("Nada que purgar: la papelera no tiene ficheros de más de {$dias} días.", 'green');

            return;
        }

        CLI::write(sprintf('Purgados %d fichero(s) con más de %d días en la papelera:', count($borrados), $dias), 'yellow');
        foreach ($borrados as $fichero) {
            CLI::write('  ' . $fichero);
        }
    }
}
