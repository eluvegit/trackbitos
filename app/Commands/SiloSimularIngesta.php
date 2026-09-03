<?php namespace App\Commands;

use App\Models\SiloUnidadModel;
use App\Services\SiloIngestaService;
use App\Services\SiloService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Simula el escaneo de una unidad Maestro con contenido de ejemplo —
 * ejercita el pipeline real (SiloIngestaService/SiloService) hasta que
 * exista la API/.py que lea un disco de verdad (plan Silo §7.1/§9).
 * Repetible: si "Maestro #1" ya existe la reutiliza en vez de duplicarla.
 */
class SiloSimularIngesta extends BaseCommand
{
    protected $group       = 'custom';
    protected $name        = 'silo:simular-ingesta';
    protected $description = 'Simula el escaneo de una unidad Maestro con contenido de ejemplo (2 sesiones + contenido mixto).';

    public function run(array $params)
    {
        $silo         = new SiloService();
        $ingesta      = new SiloIngestaService();
        $unidadModel  = new SiloUnidadModel();

        $unidad = $unidadModel->where('nivel', 1)->where('numero', 1)->first();
        if (!$unidad) {
            $unidad = $silo->crearUnidad(1, 'Maestro #1');
            CLI::write('Creada unidad Maestro #1 (id=' . $unidad['id'] . ')', 'green');
        } else {
            CLI::write('Reutilizando unidad Maestro #1 (id=' . $unidad['id'] . ')');
        }

        $idSesionDanza  = $silo->siguienteIdNegocio();
        $idProducto     = $silo->siguienteIdNegocio();
        $idCumpleanos   = $silo->siguienteIdNegocio();
        $idSuelta       = $silo->siguienteIdNegocio();
        $idAntigua      = $silo->siguienteIdNegocio();

        $carpetas = [
            [
                'nombre' => "{$idSesionDanza} 20260714 stock, sesion danza, arte por danza, lucia, marta",
                'ficheros' => [
                    ['nombre' => 'Trailer.mp4', 'tamano_bytes' => 85_400_000],
                    ['nombre' => 'Version 4K.mp4', 'tamano_bytes' => 512_300_000],
                    ['nombre' => 'Blanco y Negro.jpg', 'tamano_bytes' => 8_200_000],
                    ['nombre' => 'Color 01.jpg', 'tamano_bytes' => 7_900_000],
                    ['nombre' => 'Color 02.jpg', 'tamano_bytes' => 8_100_000],
                ],
            ],
            [
                'nombre' => "{$idProducto} 20260722 producto, catalogo otono, zapatillas",
                'ficheros' => [
                    ['nombre' => 'Zapatilla_01.jpg', 'tamano_bytes' => 6_500_000],
                    ['nombre' => 'Zapatilla_02.jpg', 'tamano_bytes' => 6_700_000],
                    ['nombre' => 'Zapatilla_03.jpg', 'tamano_bytes' => 6_600_000],
                    ['nombre' => 'Making of.mp4', 'tamano_bytes' => 210_000_000],
                ],
            ],
            [
                'nombre' => "{$idCumpleanos} sinfecha personal, cumpleanos papa",
                'ficheros' => [
                    ['nombre' => 'IMG_0231.jpg', 'tamano_bytes' => 5_100_000],
                    ['nombre' => 'IMG_0232.jpg', 'tamano_bytes' => 5_300_000],
                ],
            ],
            [
                'nombre' => "{$idSuelta} 20260801 sin_clasificar",
                'ficheros' => [
                    ['nombre' => 'IMG_0400.jpg', 'tamano_bytes' => 4_800_000],
                ],
            ],
            [
                // Año distinto a propósito, para poder ver la propagación por año agrupando en unidades separadas.
                'nombre' => "{$idAntigua} 20250310 personal, escapada asturias",
                'ficheros' => [
                    ['nombre' => 'IMG_0010.jpg', 'tamano_bytes' => 5_500_000],
                    ['nombre' => 'IMG_0011.jpg', 'tamano_bytes' => 5_600_000],
                ],
            ],
        ];

        foreach ($carpetas as $c) {
            foreach ($c['ficheros'] as &$f) {
                $f['hash'] = hash('sha256', $c['nombre'] . '|' . $f['nombre']);
            }
            unset($f);

            $pieza = $ingesta->ingestarCarpeta($unidad['id'], $c['nombre'], $c['ficheros']);
            CLI::write('Ingestada: ' . $pieza['nombre_carpeta'] . ' (id_negocio=' . $pieza['id_negocio'] . ')', 'green');
        }

        CLI::write('OK. Revisa /silo en el navegador.');
    }
}
