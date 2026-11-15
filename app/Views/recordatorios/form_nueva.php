<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Alta / edición de un recordatorio (estilo fv). Es el formulario común:
 * también se llega desde Coche y Lentillas (con `volver`).
 *
 * @var array|null $recordatorio
 * @var string     $categoria  categoría inicial
 * @var array      $categorias [valor => [etiqueta, icono]]
 * @var string     $volver     '' | coche | lentillas
 */
use App\Libraries\RecordatorioServicio;

$r      = $recordatorio;
$editar = (bool) $r;
$val    = static fn (string $k, $def = '') => old($k, $r[$k] ?? $def);
$cat    = (string) old('categoria', $categoria);
$modulo = $volver !== '' ? $volver : 'recordatorios';
$atras  = site_url(['coche' => 'coche/recordatorios', 'lentillas' => 'lentillas/avisos'][$volver] ?? 'recordatorios');
// Por defecto: avisa; los de Coche y Lentillas nuevos nacen «persistentes».
$persistente = (bool) old('persistente', $r ? $r['persistente'] : isset(RecordatorioServicio::MODULOS[$cat]));
$avisar      = (bool) old('avisar', $r ? $r['avisar'] : 1);
$elemento    = mb_strtolower((string) $val('elemento'));
?>
<div class="fv rc estrecho">
    <?= view('recordatorios/_cabecera', [
        'modulo' => $modulo,
        'activa' => 'avisos',
        'migas'  => $volver !== '' ? ['Avisos' => $atras, ($editar ? 'Editar' : 'Nuevo') => null] : [($editar ? 'Editar' : 'Nuevo') => null],
        'titulo' => $editar ? 'Editar recordatorio' : 'Nuevo recordatorio',
    ]) ?>

    <form class="rc-form" method="post" action="<?= $editar ? site_url('recordatorios/actualizar/' . $r['id']) : site_url('recordatorios/crear') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="volver" value="<?= esc($volver, 'attr') ?>">

        <div class="fv-panel">
            <label class="rc-campo">
                <span>Qué</span>
                <input class="fv-input rc-grande" name="titulo" value="<?= esc((string) $val('titulo'), 'attr') ?>" placeholder="ITV, cambio de aceite, DNI…" required maxlength="150" <?= $editar ? '' : 'autofocus' ?>>
            </label>
            <div class="rc-campo">
                <span>Categoría</span>
                <div class="rc-cats" id="rcCat">
                    <?php foreach ($categorias as $k => [$txt, $ico]): ?>
                        <label><input type="radio" name="categoria" value="<?= $k ?>" <?= $cat === $k ? 'checked' : '' ?>><span><?= rec_icono($ico) ?><?= esc($txt) ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <p class="rc-info" data-solo="vehiculo" hidden><i class="bi bi-car-front"></i><span>Aparece también en <b>Coche</b>. Al marcarlo como hecho se apunta en el historial del coche.</span></p>
            <p class="rc-info" data-solo="lentillas" hidden><i class="bi bi-eye"></i><span>Aparece también en <b>Lentillas</b>. Al marcarlo como hecho se apunta el cambio y se descuenta del stock.</span></p>
        </div>

        <div class="fv-panel">
            <div class="rc-filas">
                <label class="rc-campo">
                    <span>Próxima fecha</span>
                    <input type="date" class="fv-input" name="fecha_evento" value="<?= esc((string) $val('fecha_evento'), 'attr') ?>">
                </label>
                <label class="rc-campo">
                    <span>Se repite cada… meses</span>
                    <input type="number" class="fv-input" name="periodo_meses" min="0" inputmode="numeric" value="<?= esc((string) $val('periodo_meses'), 'attr') ?>" placeholder="12">
                </label>
                <label class="rc-campo">
                    <span>…y días</span>
                    <input type="number" class="fv-input" name="periodo_dias" min="0" inputmode="numeric" value="<?= esc((string) $val('periodo_dias'), 'attr') ?>" placeholder="0">
                </label>
            </div>
            <?php if ($editar && !empty($r['ultima_vez'])): ?>
                <p class="rc-info"><i class="bi bi-check2"></i><span>Última vez: <?= rec_fecha($r['ultima_vez']) ?> (<?= rec_hace($r['ultima_vez']) ?>). Si cambias el plazo sin tocar la fecha, la próxima fecha se recalcula desde ese día.</span></p>
            <?php else: ?>
                <p class="rc-info"><i class="bi bi-info-circle"></i><span>La fecha puede quedar vacía si aún no lo has hecho nunca: se pondrá sola la primera vez que lo marques como hecho.</span></p>
            <?php endif; ?>

            <div class="rc-filas">
                <label class="rc-campo" data-solo="vehiculo" hidden>
                    <span>Cada… km <small>(solo informativo)</small></span>
                    <input type="number" class="fv-input" name="intervalo_km" min="0" inputmode="numeric" value="<?= esc((string) $val('intervalo_km'), 'attr') ?>" placeholder="10000">
                </label>
                <label class="rc-campo" data-solo="lentillas" hidden>
                    <span>Qué cambio lo renueva</span>
                    <select class="rc-sel" name="elemento">
                        <option value="">— Ninguno —</option>
                        <?php foreach (RecordatorioServicio::ELEMENTOS as $el): ?>
                            <option value="<?= esc($el, 'attr') ?>" <?= RecordatorioServicio::norm($elemento) === RecordatorioServicio::norm($el) ? 'selected' : '' ?>><?= esc(mb_strtoupper(mb_substr($el, 0, 1)) . mb_substr($el, 1)) ?><?= $el === 'lentillas' ? ' (las dos, o una sola)' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        </div>

        <div class="fv-panel">
            <label class="rc-inter">
                <input type="checkbox" name="avisar" value="1" <?= $avisar ? 'checked' : '' ?>>
                <span><b>Avisar en el panel y en Telegram</b><small>Cuando falte un mes o menos. Apágalo en los frecuentes (limpiar el coche, lentillas) para que no hagan ruido.</small></span>
            </label>
            <label class="rc-inter">
                <input type="checkbox" name="persistente" value="1" <?= $persistente ? 'checked' : '' ?>>
                <span><b>Se queda vencido hasta que lo haga</b><small>Para lo que depende de hacerlo (aceite, lentillas). Sin marcar, pasados 30 días de la fecha salta solo al siguiente ciclo (ITV, cumpleaños).</small></span>
            </label>
        </div>

        <div class="fv-panel">
            <label class="rc-campo">
                <span>Notas</span>
                <textarea class="fv-input" name="notas" rows="2"><?= esc((string) $val('notas')) ?></textarea>
            </label>
        </div>

        <div class="rc-pie">
            <?php if ($editar): ?>
                <button type="submit" class="fv-btn fv-btn-peligro" formaction="<?= site_url('recordatorios/borrar/' . $r['id']) ?>" formnovalidate
                        onclick="return confirm('¿Borrar este recordatorio? Su historial se conserva.')"><i class="bi bi-trash"></i>Borrar</button>
            <?php endif; ?>
            <div class="der">
                <a class="fv-btn" href="<?= $atras ?>">Cancelar</a>
                <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Guardar</button>
            </div>
        </div>
    </form>
</div>

<script>
(() => {
    // Campos y avisos propios de una categoría (km del coche, elemento de lentillas).
    const pintar = () => {
        const cat = document.querySelector('#rcCat input:checked')?.value || '';
        document.querySelectorAll('[data-solo]').forEach(el => el.hidden = el.dataset.solo !== cat);
    };
    document.getElementById('rcCat').addEventListener('change', pintar);
    pintar();
})();
</script>
<?= $this->endSection() ?>
