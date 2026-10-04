<?php

namespace App\Commands;

use App\Services\GimnasioCatalogoService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class GimnasioCatalogo extends BaseCommand
{
    protected $group       = 'custom';
    protected $name        = 'gimnasio:catalogo';
    protected $description = 'Importa/actualiza el catálogo de ejercicios (exercises-dataset) en gimnasio_catalogo. No toca tus ejercicios.';
    protected $usage       = 'gimnasio:catalogo [ruta_o_url_exercises.json]';

    public function run(array $params)
    {
        try {
            $r = (new GimnasioCatalogoService())->importar($params[0] ?? null);
            CLI::write("Catálogo importado: {$r['nuevos']} nuevos, {$r['actualizados']} actualizados.", 'green');
        } catch (\Throwable $e) {
            CLI::error($e->getMessage());
        }
    }
}
