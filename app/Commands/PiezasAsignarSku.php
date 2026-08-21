<?php

namespace App\Commands;

use App\Models\PiezaVarianteModel;
use App\Services\PiezaSkuService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Comando de un solo uso (fase 44): asigna el SKU automático a las
 * variantes que ya existían antes de que `PiezaSkuService` sustituyera al
 * campo manual. Recorre las variantes SIN sku, por orden de creación
 * (`creado_en`, y de empate por `id`), y les da el siguiente número del
 * mismo contador global que ya usan las variantes nuevas — así el
 * catálogo entero queda con una numeración consistente y no se reutiliza
 * ningún hueco.
 *
 * Por defecto solo enseña lo que haría; hace falta --confirmar para
 * guardarlo de verdad. Se puede ejecutar más de una vez sin miedo: cada
 * vuelta solo toca las variantes que sigan sin SKU (borradas incluidas,
 * para que ninguna se quede huérfana de código si algún día se restaura).
 */
class PiezasAsignarSku extends BaseCommand
{
    protected $group       = 'Piezas';
    protected $name        = 'piezas:asignar-sku';
    protected $description = 'Asigna el SKU automático a las variantes que todavía no tienen (catálogo previo a la fase 44).';
    protected $usage       = 'piezas:asignar-sku [--confirmar]';
    protected $options     = [
        '--confirmar' => 'Guarda de verdad. Sin esta opción solo enseña lo que haría.',
    ];

    public function run(array $params)
    {
        $confirmar = array_key_exists('confirmar', $params) || in_array('--confirmar', $params, true);

        $varianteModel = new PiezaVarianteModel();
        // withDeleted() no aplica aquí: el borrado de variantes es un
        // borrado propio (borrado_en), no soft-delete de CI4 — findAll()
        // ya las trae todas, vivas y en papelera.
        $variantes = $varianteModel
            ->where('sku', null)
            ->orderBy('creado_en', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        if ($variantes === []) {
            CLI::write('Todas las variantes ya tienen SKU. Nada que hacer.', 'green');

            return;
        }

        CLI::write(sprintf('%d variante(s) sin SKU:', count($variantes)));

        $skuService = new PiezaSkuService();
        // Sin --confirmar, la vista previa NO toca el contador real (usa
        // codigoDe() puro sobre el valor actual + un contador local): llamar
        // a generar() en modo simulación gastaría números para siempre sin
        // llegar a guardarlos.
        $siguienteLocal = $skuService->contadorActual();

        foreach ($variantes as $variante) {
            $sku = $confirmar
                ? $skuService->generar()
                : PiezaSkuService::codigoDe(++$siguienteLocal);

            CLI::write(sprintf(
                '  #%-5d %-40s → %s%s',
                (int) $variante['id'],
                mb_substr($variante['nombre'], 0, 40),
                $sku,
                $variante['borrado_en'] ? '  (en papelera)' : ''
            ));

            if ($confirmar) {
                $varianteModel->update((int) $variante['id'], ['sku' => $sku]);
            }
        }

        CLI::newLine();
        CLI::write($confirmar
            ? 'SKU asignado y guardado.'
            : 'Simulación: no se ha guardado nada. Añade --confirmar para hacerlo.', $confirmar ? 'green' : 'yellow');
    }
}
