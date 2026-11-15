<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Coche — apuntar / editar una acción (estilo fv). Una sola caja de texto:
 * al escribir se sugieren los avisos del coche; si el texto coincide con
 * uno, la acción lo renueva.
 *
 * @var array|null $accion
 * @var array      $reminders avisos del coche: [['id', 'title'], ...]
 * @var int        $previo    aviso preseleccionado (?aviso=)
 */
$a      = $accion ?? null;
$editar = (bool) $a;
$rid    = (int) old('recordatorio_id', $a['recordatorio_id'] ?? $previo);
$titulos = array_column($reminders, 'title', 'id');
$titulo = (string) old('title', $a['title'] ?? ($titulos[$rid] ?? ''));
?>
<div class="fv rc estrecho">
    <?= view('recordatorios/_cabecera', [
        'modulo' => 'coche',
        'activa' => 'historial',
        'migas'  => ['Historial' => site_url('coche/acciones'), ($editar ? 'Editar' : 'Apuntar') => null],
        'titulo' => $editar ? 'Editar acción' : 'Apuntar acción',
    ]) ?>

    <form class="rc-form" method="post" action="<?= site_url('coche/acciones/guardar') ?>">
        <?= csrf_field() ?>
        <?php if ($editar): ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><?php endif; ?>
        <input type="hidden" name="recordatorio_id" id="rcRid" value="<?= $rid ?: '' ?>">

        <div class="fv-panel">
            <label class="rc-campo">
                <span>Qué se hizo</span>
                <input class="fv-input rc-grande" name="title" id="rcTitulo" list="rcAvisos" value="<?= esc($titulo, 'attr') ?>" placeholder="Cambio de aceite, lavado, revisión…" required maxlength="100" autocomplete="off" <?= $editar ? '' : 'autofocus' ?>>
                <datalist id="rcAvisos"><?php foreach ($reminders as $r): ?><option value="<?= esc($r['title'], 'attr') ?>"><?php endforeach; ?></datalist>
            </label>
            <p class="rc-info" id="rcEnlace" hidden><i class="bi bi-bell"></i><span>Renueva el aviso «<b></b>»: su próxima fecha se calcula desde este día.</span></p>
            <div class="rc-filas">
                <label class="rc-campo">
                    <span>Fecha</span>
                    <input type="date" class="fv-input" name="date" value="<?= esc((string) old('date', $a['date'] ?? date('Y-m-d')), 'attr') ?>" required>
                </label>
                <label class="rc-campo">
                    <span>Kilómetros <small>(opcional)</small></span>
                    <input type="number" class="fv-input" name="kilometers" min="0" inputmode="numeric" value="<?= esc((string) old('kilometers', $a['kilometers'] ?? ''), 'attr') ?>">
                </label>
            </div>
            <label class="rc-campo">
                <span>Notas <small>(opcional)</small></span>
                <textarea class="fv-input" name="notes" rows="2"><?= esc((string) old('notes', $a['notes'] ?? '')) ?></textarea>
            </label>
        </div>

        <div class="rc-pie">
            <div class="der">
                <a class="fv-btn" href="<?= site_url('coche/acciones') ?>">Cancelar</a>
                <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Guardar</button>
            </div>
        </div>
    </form>
</div>
<script>
(() => {
    // Si lo escrito coincide con un aviso del coche, la acción queda ligada a él.
    const avisos = <?= json_encode(array_map(static fn ($r) => ['id' => (int) $r['id'], 't' => $r['title']], $reminders)) ?>;
    const norm = s => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim().replace(/\s+/g, ' ');
    const titulo = document.getElementById('rcTitulo'), rid = document.getElementById('rcRid'), enlace = document.getElementById('rcEnlace');
    const pintar = () => {
        const a = avisos.find(x => norm(x.t) === norm(titulo.value));
        rid.value = a ? a.id : '';
        enlace.hidden = !a;
        if (a) enlace.querySelector('b').textContent = a.t;
    };
    titulo.addEventListener('input', pintar);
    pintar();
})();
</script>
<?= $this->endSection() ?>
