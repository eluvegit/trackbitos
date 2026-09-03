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
 * Repetible: si "Maestro #1" ya existe la reutiliza en vez de duplicarla
 * (las piezas sí se vuelven a dar de alta con IDs nuevos en cada pasada).
 *
 * El juego de carpetas cubre varias categorías (stock, producto, personal,
 * boda, corporativo, naturaleza, sin clasificar) y varios años + sin fecha,
 * para ver la propagación repartir en cubos distintos de Copia 2 (año) y
 * Copia 3 (categoría).
 */
class SiloSimularIngesta extends BaseCommand
{
    protected $group       = 'custom';
    protected $name        = 'silo:simular-ingesta';
    protected $description = 'Simula el escaneo de una unidad Maestro con ~10 carpetas de ejemplo de distintas clases.';

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

        // {ID} se sustituye por el siguiente id de negocio justo antes de ingestar.
        $carpetas = [
            [
                'nombre' => '{ID} 20260714 stock, sesion danza, arte por danza, lucia, marta',
                'ficheros' => [
                    ['nombre' => 'Trailer.mp4', 'tamano_bytes' => 85_400_000],
                    ['nombre' => 'Version 4K.mp4', 'tamano_bytes' => 512_300_000],
                    ['nombre' => 'Blanco y Negro.jpg', 'tamano_bytes' => 8_200_000],
                    ['nombre' => 'Color 01.jpg', 'tamano_bytes' => 7_900_000],
                    ['nombre' => 'Color 02.jpg', 'tamano_bytes' => 8_100_000],
                ],
            ],
            [
                'nombre' => '{ID} 20260722 producto, catalogo otono, zapatillas',
                'ficheros' => [
                    ['nombre' => 'Zapatilla_01.jpg', 'tamano_bytes' => 6_500_000],
                    ['nombre' => 'Zapatilla_02.jpg', 'tamano_bytes' => 6_700_000],
                    ['nombre' => 'Zapatilla_03.jpg', 'tamano_bytes' => 6_600_000],
                    ['nombre' => 'Making of.mp4', 'tamano_bytes' => 210_000_000],
                ],
            ],
            [
                'nombre' => '{ID} sinfecha personal, cumpleanos papa',
                'ficheros' => [
                    ['nombre' => 'IMG_0231.jpg', 'tamano_bytes' => 5_100_000],
                    ['nombre' => 'IMG_0232.jpg', 'tamano_bytes' => 5_300_000],
                ],
            ],
            [
                'nombre' => '{ID} 20260801 sin_clasificar',
                'ficheros' => [
                    ['nombre' => 'IMG_0400.jpg', 'tamano_bytes' => 4_800_000],
                ],
            ],
            [
                // Año distinto a propósito, para ver la propagación por año en unidades separadas.
                'nombre' => '{ID} 20250310 personal, escapada asturias',
                'ficheros' => [
                    ['nombre' => 'IMG_0010.jpg', 'tamano_bytes' => 5_500_000],
                    ['nombre' => 'IMG_0011.jpg', 'tamano_bytes' => 5_600_000],
                ],
            ],
            [
                'nombre' => '{ID} 20260215 boda, laura y diego, finca los olivos',
                'ficheros' => [
                    ['nombre' => 'Ceremonia.mp4', 'tamano_bytes' => 1_240_000_000],
                    ['nombre' => 'Pareja 01.jpg', 'tamano_bytes' => 9_100_000],
                    ['nombre' => 'Pareja 02.jpg', 'tamano_bytes' => 9_300_000],
                    ['nombre' => 'Invitados.jpg', 'tamano_bytes' => 8_800_000],
                ],
            ],
            [
                // Precisión de mes (AAAAMM).
                'nombre' => '{ID} 202605 corporativo, memoria anual, oficinas madrid',
                'ficheros' => [
                    ['nombre' => 'Hall.jpg', 'tamano_bytes' => 7_200_000],
                    ['nombre' => 'Equipo.jpg', 'tamano_bytes' => 7_500_000],
                    ['nombre' => 'Resumen.mp4', 'tamano_bytes' => 320_000_000],
                ],
            ],
            [
                // Precisión de año (AAAA), y un tercer año para Copia 2.
                'nombre' => '{ID} 2024 naturaleza, fauna pirineos, quebrantahuesos',
                'ficheros' => [
                    ['nombre' => 'DSC_1001.jpg', 'tamano_bytes' => 11_400_000],
                    ['nombre' => 'DSC_1002.jpg', 'tamano_bytes' => 12_000_000],
                    ['nombre' => 'DSC_1003.jpg', 'tamano_bytes' => 10_900_000],
                    ['nombre' => 'Vuelo.mp4', 'tamano_bytes' => 640_000_000],
                ],
            ],
            [
                'nombre' => '{ID} 20241120 producto, packaging cosmetica, serum facial',
                'ficheros' => [
                    ['nombre' => 'Bote_frontal.jpg', 'tamano_bytes' => 6_100_000],
                    ['nombre' => 'Bote_detalle.jpg', 'tamano_bytes' => 6_400_000],
                    ['nombre' => 'Bodegon.jpg', 'tamano_bytes' => 7_000_000],
                ],
            ],
            [
                'nombre' => '{ID} 20250628 boda, marta y javi, playa',
                'ficheros' => [
                    ['nombre' => 'Atardecer.jpg', 'tamano_bytes' => 9_600_000],
                    ['nombre' => 'Baile.mp4', 'tamano_bytes' => 780_000_000],
                    ['nombre' => 'Grupo.jpg', 'tamano_bytes' => 8_900_000],
                ],
            ],
            [
                'nombre' => '{ID} 20260905 stock, ciudad nocturna, timelapse',
                'ficheros' => [
                    ['nombre' => 'Timelapse_centro.mp4', 'tamano_bytes' => 430_000_000],
                    ['nombre' => 'Skyline 01.jpg', 'tamano_bytes' => 8_300_000],
                    ['nombre' => 'Skyline 02.jpg', 'tamano_bytes' => 8_100_000],
                ],
            ],
        ];

        foreach ($carpetas as $c) {
            $nombre = str_replace('{ID}', $silo->siguienteIdNegocio(), $c['nombre']);

            foreach ($c['ficheros'] as &$f) {
                $f['hash'] = hash('sha256', $nombre . '|' . $f['nombre']);
            }
            unset($f);

            $pieza = $ingesta->ingestarCarpeta($unidad['id'], $nombre, $c['ficheros']);
            CLI::write('Ingestada: ' . $pieza['nombre_carpeta'] . ' (id_negocio=' . $pieza['id_negocio'] . ')', 'green');
        }

        CLI::write('OK. Revisa /silo y /silo/mi-pc en el navegador.');
    }
}
