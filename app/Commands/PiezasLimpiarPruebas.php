<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Deshace los seeders de prueba de los badges (ZZEstadosPiezasSeeder), que
 * insertan una categoría, seis piezas, sus versiones/sesiones y una máquina
 * falsa. Todo lo que crean lleva el prefijo "ZZ ", así que se identifica sin
 * ambigüedad — pero como esto se ejecuta en producción, por defecto solo
 * enseña lo que encontraría: para borrar de verdad hay que pedirlo con
 * --confirmar.
 *
 * Comprueba además la huella de cada pieza antes de darla por de prueba (las
 * versiones que crea el seeder tienen ruta_blend 'zz/prueba.blend' y ningún
 * fichero real detrás). Si algo no encaja, lo avisa y no lo toca.
 */
class PiezasLimpiarPruebas extends BaseCommand
{
    protected $group       = 'Piezas';
    protected $name        = 'piezas:limpiar-pruebas';
    protected $description = 'Borra los datos que dejó ZZEstadosPiezasSeeder (piezas "ZZ ", su categoría y su máquina).';
    protected $usage       = 'piezas:limpiar-pruebas [--confirmar]';
    protected $options     = [
        '--confirmar' => 'Borra de verdad. Sin esta opción solo enseña lo que hay.',
    ];

    private const PREFIJO         = 'ZZ ';
    private const CATEGORIA       = 'ZZ Prueba badges';
    private const MAQUINA_UUID    = 'zz-maquina-de-prueba';
    private const RUTA_DE_PRUEBA  = 'zz/';

    public function run(array $params)
    {
        $borrar = array_key_exists('confirmar', $params) || in_array('--confirmar', $params, true);
        $db     = Database::connect();

        $familias = $db->table('piezas_familias')->like('nombre', self::PREFIJO, 'after')->get()->getResultArray();

        if ($familias === []) {
            CLI::write('No hay ninguna pieza con el prefijo "ZZ ".', 'green');
        }

        $sospechosas = [];
        $total = ['variantes' => 0, 'versiones' => 0, 'ramas' => 0, 'sesiones' => 0];

        foreach ($familias as $f) {
            $variantes = $db->table('piezas_variantes')->where('familia_id', $f['id'])->get()->getResultArray();
            $ids       = array_column($variantes, 'id');

            $versiones = $ids === [] ? [] : $db->table('piezas_versiones')->whereIn('variante_id', $ids)->get()->getResultArray();
            $ramas     = $ids === [] ? [] : $db->table('piezas_ramas')->whereIn('variante_id', $ids)->get()->getResultArray();
            $sesiones  = $ramas === [] ? [] : $db->table('piezas_sesiones')->whereIn('rama_id', array_column($ramas, 'id'))->get()->getResultArray();

            // Huella: el seeder nunca deja ficheros de verdad. Una versión con
            // otra ruta significa que ahí hubo trabajo real, y eso no se toca.
            $conFicheroReal = array_filter(
                $versiones,
                static fn($v) => !str_starts_with((string) $v['ruta_blend'], self::RUTA_DE_PRUEBA)
            );

            $marca = '';
            if ($conFicheroReal !== []) {
                $sospechosas[] = $f['nombre'];
                $marca = CLI::color('  ← tiene versiones con fichero real, NO se borra', 'red');
            }

            $total['variantes'] += count($variantes);
            $total['versiones'] += count($versiones);
            $total['ramas']     += count($ramas);
            $total['sesiones']  += count($sesiones);

            CLI::write(sprintf(
                '  %-22s %d variante(s), %d versión(es), %d rama(s), %d sesión(es)%s',
                $f['nombre'],
                count($variantes),
                count($versiones),
                count($ramas),
                count($sesiones),
                $marca
            ));
        }

        $categoria = $db->table('piezas_categorias')->where('nombre', self::CATEGORIA)->get()->getRowArray();
        $maquina   = $db->table('piezas_maquinas')->where('uuid', self::MAQUINA_UUID)->get()->getRowArray();

        if ($categoria) {
            CLI::write('  Categoría: ' . $categoria['nombre']);
        }
        if ($maquina) {
            $abiertas = $db->table('piezas_sesiones')
                ->where('maquina_id', $maquina['id'])
                ->where('cerrada_en', null)
                ->countAllResults();

            CLI::write('  Máquina: ' . $maquina['nombre'] . ' (' . $abiertas . ' sesión(es) abierta(s))');
        }

        if (!$borrar) {
            CLI::newLine();
            CLI::write('Simulación: no se ha borrado nada. Añade --confirmar para hacerlo.', 'yellow');

            return;
        }

        $db->transStart();

        foreach ($familias as $f) {
            if (in_array($f['nombre'], $sospechosas, true)) {
                continue;
            }

            $variantes = $db->table('piezas_variantes')->where('familia_id', $f['id'])->get()->getResultArray();
            $ids       = array_column($variantes, 'id');

            if ($ids !== []) {
                $ramas = $db->table('piezas_ramas')->whereIn('variante_id', $ids)->get()->getResultArray();

                // De dentro hacia fuera, para no chocar con ninguna clave ajena.
                if ($ramas !== []) {
                    $db->table('piezas_sesiones')->whereIn('rama_id', array_column($ramas, 'id'))->delete();
                }
                $db->table('piezas_descargas')->whereIn('variante_id', $ids)->delete();
                $db->table('piezas_composiciones')->whereIn('variante_id', $ids)->delete();
                $db->table('piezas_ramas')->whereIn('variante_id', $ids)->delete();

                // Renders y composiciones cuelgan de la versión, no de la
                // variante. El seeder no crea ninguno, pero si los hubiera
                // su clave ajena bloquearía el borrado de las versiones.
                $idsVersion = array_column(
                    $db->table('piezas_versiones')->select('id')->whereIn('variante_id', $ids)->get()->getResultArray(),
                    'id'
                );
                if ($idsVersion !== []) {
                    $db->table('piezas_renders')->whereIn('version_id', $idsVersion)->delete();
                    $db->table('piezas_composiciones')->whereIn('version_componente_id', $idsVersion)->delete();
                }

                // origen_version_id apunta a versiones: se suelta antes de
                // borrarlas para que la FK no bloquee el DELETE.
                $db->table('piezas_variantes')->whereIn('id', $ids)->update(['origen_version_id' => null]);
                $db->table('piezas_versiones')->whereIn('variante_id', $ids)->delete();
                $db->table('piezas_variantes')->whereIn('id', $ids)->delete();
            }

            $db->table('piezas_referencias')->where('familia_id', $f['id'])->delete();
            $db->table('piezas_familias')->where('id', $f['id'])->delete();

            CLI::write('  borrada ' . $f['nombre'], 'dark_gray');
        }

        if ($categoria) {
            $huerfanas = $db->table('piezas_familias')->where('categoria_id', $categoria['id'])->countAllResults();
            if ($huerfanas > 0) {
                CLI::write("  ¡Ojo! {$huerfanas} pieza(s) siguen en esa categoría: se quedarán sin clasificar.", 'yellow');
            }
            $db->table('piezas_categorias')->where('id', $categoria['id'])->delete();
            CLI::write('  borrada la categoría ' . $categoria['nombre'], 'dark_gray');
        }

        if ($maquina) {
            $usada = $db->table('piezas_sesiones')->where('maquina_id', $maquina['id'])->countAllResults()
                + $db->table('piezas_descargas')->where('maquina_id', $maquina['id'])->countAllResults();

            if ($usada > 0) {
                CLI::write('  la máquina ' . $maquina['nombre'] . " sigue referida por {$usada} registro(s): NO se borra.", 'yellow');
            } else {
                $db->table('piezas_maquinas')->where('id', $maquina['id'])->delete();
                CLI::write('  borrada la máquina ' . $maquina['nombre'], 'dark_gray');
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            CLI::error('Falló el borrado: no se ha tocado nada (la transacción se deshizo).');

            return;
        }

        $json = WRITEPATH . 'zz-variantes.json';
        if (is_file($json)) {
            unlink($json);
            CLI::write('  borrado writable/zz-variantes.json', 'dark_gray');
        }

        CLI::newLine();
        CLI::write(sprintf(
            'Quedan %d piezas, %d categorías, %d máquinas.',
            $db->table('piezas_familias')->countAllResults(),
            $db->table('piezas_categorias')->countAllResults(),
            $db->table('piezas_maquinas')->countAllResults()
        ), 'green');

        if ($sospechosas !== []) {
            CLI::newLine();
            CLI::write('Sin tocar por tener ficheros reales: ' . implode(', ', $sospechosas), 'yellow');
        }
    }
}
