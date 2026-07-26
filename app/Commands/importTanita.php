<?php namespace App\Commands;

use App\Services\TanitaImportService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ImportTanita extends BaseCommand
{
    protected $group       = 'custom';
    protected $name        = 'import:tanita';
    protected $description = 'Importa un CSV exportado de una báscula Tanita a comida_pesos';
    protected $usage       = 'import:tanita <path_csv>';

    public function run(array $params)
    {
        $path = $params[0] ?? null;
        if (!$path || !is_file($path)) {
            CLI::error('Debe indicar la ruta a un CSV válido.');
            return;
        }

        try {
            $resumen = (new TanitaImportService())->importFromCsv($path);
        } catch (\Throwable $e) {
            CLI::error($e->getMessage());
            return;
        }

        CLI::write("Filas leídas: {$resumen['total']}", 'yellow');
        CLI::write("Días únicos procesados: {$resumen['dias']}", 'yellow');
        CLI::write("Insertadas: {$resumen['insertadas']}", 'green');
        CLI::write("Actualizadas (ya existía registro ese día): {$resumen['actualizadas']}", 'green');
        foreach ($resumen['errores'] as $err) {
            CLI::error($err);
        }
        CLI::write('Con error: ' . count($resumen['errores']), $resumen['errores'] ? 'red' : 'green');
    }
}
