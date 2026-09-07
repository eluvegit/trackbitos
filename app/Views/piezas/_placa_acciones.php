<?php
/**
 * El bloque de acciones de una placa, compartido por las dos vistas de
 * tarjeta (_placa_tarjeta.php en grid, _placa_tarjeta_grande.php en el
 * timeline de Impresas). Solo queda "deshacer reparto" (fase 55: borrar se
 * movió a la propia pantalla de editar — ver el pie de _bitacora_form.php —
 * y descargar/cargar/repartir ya se habían quitado de aquí en la fase 54).
 * Por eso solo se pinta cuando aplica: la mayoría de placas no vienen de un
 * reparto y no necesitan esta línea ni su divisor.
 *
 * Espera: $placa, $idPlaca, $origenNombres — las mismas variables que ya
 * están en el scope de quien la incluye.
 */
?>
<?php if (!empty($placa['es_reparto']) && $placa['origen_placa_id'] && isset($origenNombres[(int) $placa['origen_placa_id']])): ?>
    <div class="mt-2 pt-2 border-top" data-acciones-placa>
        <form method="post" action="<?= site_url('piezas/placa/' . $idPlaca . '/deshacer-reparto') ?>"
            onsubmit="return confirm('¿Deshacer el reparto? Esta placa se borra y sus piezas vuelven a «<?= esc($origenNombres[(int) $placa['origen_placa_id']], 'attr') ?>».');">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-warning" title="Volver a juntar con la placa de origen">
                <i class="bi bi-arrow-counterclockwise"></i> Deshacer reparto
            </button>
        </form>
    </div>
<?php endif; ?>
