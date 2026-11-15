<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>
<?php
/**
 * Enlaces — revisar uno sin título (estilo fv). Guardar exige título;
 * saltar y borrar no. Atajos S / N / D fuera de los campos.
 *
 * @var array    $item
 * @var int|null $nextId
 * @var array    $categorias
 */
helper('enlaces');
$id = (int) $item['id'];
$dominio = enl_dominio($item['url']);
?>
<div class="fv rc estrecho">
    <?= view('enlaces/_cabecera', [
        'migas'  => ['Sin título' => site_url('enlaces/revision'), '#' . $id => null],
        'titulo' => 'Ponle título',
        'sub'    => $dominio ? '<span class="en-dominio" style="display:inline-flex;gap:6px;align-items:center"><img src="https://www.google.com/s2/favicons?domain=' . urlencode($dominio) . '&sz=32" width="14" height="14" alt="">' . esc($dominio) . '</span>' : '',
    ]) ?>

    <form class="rc-form" action="<?= site_url('enlaces/revision/guardar/' . $id) ?>" method="post">
        <?= csrf_field() ?>
        <div class="en-url-grande"><i class="bi bi-link-45deg"></i><a href="<?= esc($item['url'], 'attr') ?>" target="_blank" rel="noopener"><?= esc($item['url']) ?></a></div>

        <div class="fv-panel">
            <label class="rc-campo">
                <span>Título</span>
                <input class="fv-input rc-grande" name="titulo" required autofocus value="<?= esc((string) old('titulo', $item['titulo'] ?? ''), 'attr') ?>" placeholder="Qué es, en tus palabras">
            </label>
            <div class="rc-filas">
                <label class="rc-campo">
                    <span>Relevancia</span>
                    <select name="relevancia" class="rc-sel">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" <?= (int) ($item['relevancia'] ?: 3) === $i ? 'selected' : '' ?>><?= str_repeat('★', $i) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
                <label class="rc-campo">
                    <span>Fecha</span>
                    <input type="date" name="fecha" class="fv-input" value="<?= esc(substr($item['fecha'] ?? date('Y-m-d'), 0, 10), 'attr') ?>">
                </label>
            </div>
            <label class="rc-inter">
                <input type="checkbox" name="visto" <?= !empty($item['visto']) ? 'checked' : '' ?>>
                <span><b>Ya lo he visto</b></span>
            </label>
            <label class="rc-campo">
                <span>Notas <small>(opcional)</small></span>
                <textarea name="extra" class="fv-input" rows="2"><?= esc($item['extra'] ?? '') ?></textarea>
            </label>
            <div class="rc-campo">
                <span>Categorías <small>(opcional)</small></span>
                <div class="rc-cats">
                    <?php foreach ($categorias as $c): ?>
                        <label><input type="checkbox" name="categorias[]" value="<?= (int) $c['id'] ?>"><span><?= esc($c['nombre']) ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="rc-campo">
                <span>Etiquetas <small>(opcional, separadas por comas)</small></span>
                <div class="en-ia-fila">
                    <input type="text" name="etiquetas" id="enTags" class="fv-input" placeholder="Se rellenan solas al escribir el título…" autocomplete="off">
                    <button type="button" class="fv-btn en-ia-btn" id="enIa" title="Sugerir etiquetas con IA a partir del título, las categorías y las notas"><i class="bi bi-stars"></i><span>Sugerir</span></button>
                </div>
                <span class="en-ia-estado" id="enIaEstado"></span>
            </div>
        </div>

        <div class="rc-pie">
            <button type="submit" class="fv-btn fv-btn-peligro" formaction="<?= site_url('enlaces/revision/borrar/' . $id) ?>" formnovalidate data-action="borrar" onclick="return confirm('¿Eliminar este enlace?')" title="D"><i class="bi bi-trash"></i>Borrar</button>
            <div class="der">
                <button type="submit" class="fv-btn" formaction="<?= site_url('enlaces/revision/saltar/' . $id) ?>" formnovalidate data-action="saltar" title="N">Saltar <kbd>N</kbd></button>
                <button type="submit" class="fv-btn fv-btn-pri" data-action="guardar" title="S"><i class="bi bi-check-lg"></i>Guardar y siguiente <kbd>S</kbd></button>
            </div>
        </div>
    </form>
</div>

<script>
(() => {
    // Etiquetas con IA: se piden solas la primera vez que el título queda
    // escrito (si el campo está vacío); el botón vuelve a pedirlas cuando quieras
    // (por ejemplo, tras marcar categorías o escribir notas).
    const form = document.querySelector('form.rc-form');
    const tags = document.getElementById('enTags');
    const btn = document.getElementById('enIa');
    const estado = document.getElementById('enIaEstado');
    const titulo = form.querySelector('[name="titulo"]');
    let ultimaIa = null, pidiendo = false, autoHecho = false;

    async function sugerir() {
        if (pidiendo) return;
        if (!titulo.value.trim() && !form.querySelector('[name="extra"]').value.trim()) {
            estado.textContent = 'Escribe antes un título.';
            estado.className = 'en-ia-estado e-warn';
            titulo.focus();
            return;
        }
        pidiendo = true;
        btn.disabled = true;
        btn.classList.add('pensando');
        estado.textContent = 'Pensando etiquetas…';
        estado.className = 'en-ia-estado';
        const fd = new FormData();
        fd.append('titulo', titulo.value);
        fd.append('url', <?= json_encode((string) $item['url']) ?>);
        fd.append('notas', form.querySelector('[name="extra"]').value);
        form.querySelectorAll('[name="categorias[]"]:checked').forEach(c => fd.append('categorias[]', c.value));
        try {
            const r = await fetch('<?= site_url('enlaces/sugerir-etiquetas') ?>', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const d = await r.json().catch(() => ({}));
            if (!r.ok || !Array.isArray(d.etiquetas)) throw new Error(d.error || 'No se pudieron sugerir etiquetas ahora.');
            if (!d.etiquetas.length) { estado.textContent = 'No se le ocurre ninguna clara con tan poca información.'; return; }
            tags.value = d.etiquetas.join(', ');
            ultimaIa = tags.value;
            estado.innerHTML = '<i class="bi bi-stars"></i> Sugeridas por IA: cámbialas o bórralas si no encajan.';
            estado.className = 'en-ia-estado e-info';
        } catch (e) {
            estado.textContent = e.message;
            estado.className = 'en-ia-estado e-bad';
        } finally {
            pidiendo = false;
            btn.disabled = false;
            btn.classList.remove('pensando');
        }
    }

    btn.addEventListener('click', sugerir);
    // Automático una sola vez: al salir del título con algo escrito y sin etiquetas puestas.
    titulo.addEventListener('blur', () => {
        if (autoHecho || !titulo.value.trim() || tags.value.trim() !== '') return;
        autoHecho = true;
        sugerir();
    });
    // Si tocas las etiquetas a mano, el aviso de «sugeridas» deja de aplicar.
    tags.addEventListener('input', () => { if (ultimaIa !== null && tags.value !== ultimaIa) { estado.textContent = ''; ultimaIa = null; } });
})();

document.addEventListener('keydown', e => {
    if (e.ctrlKey || e.metaKey || e.altKey || e.target.closest('input, textarea, select')) return;
    const b = { s: 'guardar', d: 'borrar', n: 'saltar' }[e.key.toLowerCase()];
    const btn = b && document.querySelector(`button[data-action="${b}"]`);
    if (btn) { e.preventDefault(); btn.click(); }
});
</script>
<?php $this->endSection(); ?>
