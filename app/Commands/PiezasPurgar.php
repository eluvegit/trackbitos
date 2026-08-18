<?php

namespace App\Commands;

use App\Services\PiezaAlmacen;
use App\Services\PiezaService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Vacía la papelera de Piezas pasados N días (invariante 6: nada se borra,
 * se aparta; y lo apartado caduca a los 30 días) — tanto la de ficheros
 * (sesiones purgadas al validar, referencias/renders borrados) como la de
 * piezas enteras (`PiezaService::borrarFamilia`).
 *
 * Pensado para un cron diario en el servidor:
 *     php /ruta/a/trackbitos/spark piezas:purgar
 *
 * Es el único punto del módulo que borra de verdad, y solo puede tocar lo
 * que ya lleva un mes en la papelera — el plazo de gracia es lo que hace
 * que este borrado sea seguro.
 */
class PiezasPurgar extends BaseCommand
{
    protected $group       = 'Piezas';
    protected $name        = 'piezas:purgar';
    protected $description = 'Vacía la papelera de Piezas (ficheros y piezas apartados hace más de N días).';
    protected $usage       = 'piezas:purgar [dias]';
    protected $arguments   = ['dias' => 'Días de gracia antes de borrar. Por defecto, 30.'];

    public function run(array $params)
    {
        $dias = isset($params[0]) ? max(1, (int) $params[0]) : 30;

        // Primero las piezas: purgarFamiliasBorradas aparta a la papelera de
        // ficheros lo que aún viviera en su sitio original, así que tiene
        // que correr antes de que purgarPapelera se lleve esos mismos
        // ficheros por edad.
        $piezas = (new PiezaService())->purgarFamiliasBorradas($dias);

        if ($piezas === []) {
            CLI::write("Piezas: nada que purgar (ninguna lleva más de {$dias} días en la papelera).", 'green');
        } else {
            CLI::write(sprintf('Piezas purgadas (%d):', count($piezas)), 'yellow');
            foreach ($piezas as $nombre) {
                CLI::write('  ' . $nombre);
            }
        }

        $borrados = (new PiezaAlmacen())->purgarPapelera($dias);

        if (!$borrados) {
            CLI::write("Ficheros: nada que purgar (la papelera no tiene ficheros de más de {$dias} días).", 'green');

            return;
        }

        CLI::write(sprintf('Ficheros purgados (%d) con más de %d días en la papelera:', count($borrados), $dias), 'yellow');
        foreach ($borrados as $fichero) {
            CLI::write('  ' . $fichero);
        }
    }
}
