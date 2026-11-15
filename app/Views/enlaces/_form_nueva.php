<?php
/**
 * Formulario de enlace (alta y edición) en estilo fv. Lo usan crear_nueva y
 * editar_nueva. Lo único obligatorio es URL y título; el resto es opcional.
 *
 * @var array|null $enlace
 * @var array      $categorias
 * @var array      $etiquetas    todas (para sugerir mientras se escribe)
 * @var array      $selCats
 * @var array      $selTagNames
 * @var string     $action
 */
$e = $enlace ?? ['titulo' => '', 'url' => '', 'visto' => 0, 'relevancia' => 0, 'fecha' => date('Y-m-d'), 'extra' => ''];
$editar = !empty($enlace['id']);
$selCats = array_map('intval', (array) old('categorias', $selCats ?? []));
$rel = (int) old('relevancia', $e['relevancia']);
$volver = previous_url() && str_contains(previous_url(), '/enlaces') && !str_contains(previous_url(), '/editar/') && !str_contains(previous_url(), '/crear') ? previous_url() : site_url('enlaces');
?>
<form class="rc-form" method="post" action="<?= $action ?>" id="enForm">
    <?= csrf_field() ?>
    <div class="fv-panel">
        <label class="rc-campo">
            <span>URL</span>
            <input class="fv-input rc-grande" type="text" name="url" id="enUrl" required value="<?= esc((string) old('url', $e['url']), 'attr') ?>" placeholder="https://…" <?= $editar ? '' : 'autofocus' ?> inputmode="url" autocomplete="off">
            <span class="en-url-ok" id="enUrlOk"></span>
        </label>
        <label class="rc-campo">
            <span>Título</span>
            <input class="fv-input" name="titulo" required maxlength="255" value="<?= esc((string) old('titulo', $e['titulo']), 'attr') ?>" placeholder="Qué es, en tus palabras">
        </label>
        <div class="rc-filas">
            <label class="rc-campo">
                <span>Fecha</span>
                <input class="fv-input" type="date" name="fecha" value="<?= esc(substr((string) old('fecha', $e['fecha']), 0, 10), 'attr') ?>">
            </label>
            <div class="rc-campo">
                <span>Relevancia <small>(opcional)</small></span>
                <div class="en-estrellas" id="enEstrellas">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="en-estrella<?= $i <= $rel ? ' on' : '' ?>" data-v="<?= $i ?>" aria-label="<?= $i ?> estrellas">★</button>
                    <?php endfor; ?>
                    <button type="button" class="en-quitar" id="enQuitarEst"<?= $rel ? '' : ' hidden' ?>>quitar</button>
                </div>
                <input type="hidden" name="relevancia" id="enRel" value="<?= $rel ?>">
            </div>
        </div>
        <label class="rc-inter">
            <input type="checkbox" name="visto" value="1" <?= old('visto', $e['visto']) ? 'checked' : '' ?>>
            <span><b>Ya lo he visto</b><small>Los no vistos son los que salen en el repaso diario.</small></span>
        </label>
    </div>

    <div class="fv-panel">
        <div class="rc-campo">
            <span>Categorías <small>(opcional)</small></span>
            <div class="rc-cats">
                <?php foreach ($categorias as $c): ?>
                    <label><input type="checkbox" name="categorias[]" value="<?= (int) $c['id'] ?>" <?= in_array((int) $c['id'], $selCats, true) ? 'checked' : '' ?>><span><?= esc($c['nombre']) ?></span></label>
                <?php endforeach; ?>
            </div>
        </div>
        <label class="rc-campo">
            <span>Etiquetas <small>(opcional, separadas por comas)</small></span>
            <input class="fv-input" name="etiquetas" id="enTags" autocomplete="off" value="<?= esc((string) old('etiquetas', implode(', ', $selTagNames ?? [])), 'attr') ?>" placeholder="blender, tutorial, luz…">
            <span class="en-pills" id="enTagsSug"></span>
        </label>
        <label class="rc-campo">
            <span>Notas <small>(opcional)</small></span>
            <textarea class="fv-input" name="extra" rows="4" placeholder="Por qué lo guardaste, más enlaces…"><?= esc((string) old('extra', $e['extra'] ?? '')) ?></textarea>
        </label>
    </div>

    <div class="rc-pie">
        <?php if ($editar): ?>
            <a class="fv-btn fv-btn-peligro" href="<?= site_url('enlaces/borrar/' . (int) $e['id']) ?>" onclick="return confirm('¿Eliminar este enlace?');"><i class="bi bi-trash"></i>Eliminar</a>
        <?php endif; ?>
        <div class="der">
            <a class="fv-btn" href="<?= esc($volver, 'attr') ?>">Cancelar</a>
            <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Guardar</button>
        </div>
    </div>
</form>

<script>
(() => {
    // Estrellas: tocar la marcada la deja como está; «quitar» vuelve a 0.
    const est = document.getElementById('enEstrellas');
    const rel = document.getElementById('enRel');
    const quitar = document.getElementById('enQuitarEst');
    const pintar = v => {
        rel.value = v;
        est.querySelectorAll('.en-estrella').forEach(s => s.classList.toggle('on', Number(s.dataset.v) <= v));
        quitar.hidden = !v;
    };
    est.addEventListener('click', e => {
        const s = e.target.closest('.en-estrella');
        if (s) pintar(Number(s.dataset.v));
    });
    quitar.addEventListener('click', () => pintar(0));

    // URL: dominio + favicon debajo, para ver de un vistazo que es la buena.
    const url = document.getElementById('enUrl');
    const ok = document.getElementById('enUrlOk');
    const verUrl = () => {
        try {
            const h = new URL(url.value.trim()).hostname.replace(/^www\./, '');
            ok.innerHTML = h ? `<img src="https://www.google.com/s2/favicons?domain=${encodeURIComponent(h)}&sz=32" width="14" height="14" alt="">${h.replace(/</g, '&lt;')}` : '';
        } catch { ok.textContent = ''; }
    };
    url.addEventListener('input', verUrl);
    verUrl();

    // Etiquetas: sugiere las que ya existen para la palabra que estás escribiendo
    // (así no nacen «blender» y «Blender 3D» por separado).
    const todas = <?= json_encode(array_values(array_column($etiquetas, 'nombre')), JSON_UNESCAPED_UNICODE) ?>;
    const tags = document.getElementById('enTags');
    const sug = document.getElementById('enTagsSug');
    const partes = () => tags.value.split(',');
    const sugerir = () => {
        const ps = partes();
        const frag = ps[ps.length - 1].trim().toLowerCase();
        const ya = new Set(ps.slice(0, -1).map(x => x.trim().toLowerCase()));
        if (frag.length < 2) { sug.innerHTML = ''; return; }
        const m = todas.filter(t => t.toLowerCase().includes(frag) && !ya.has(t.toLowerCase()))
            .sort((a, b) => (b.toLowerCase().startsWith(frag)) - (a.toLowerCase().startsWith(frag)) || a.length - b.length)
            .slice(0, 8);
        sug.innerHTML = m.map(t => `<button type="button" class="en-chip" data-t="${t.replace(/"/g, '&quot;')}">${t.replace(/</g, '&lt;')}</button>`).join('');
    };
    tags.addEventListener('input', sugerir);
    sug.addEventListener('click', e => {
        const b = e.target.closest('[data-t]');
        if (!b) return;
        const ps = partes();
        ps[ps.length - 1] = ' ' + b.dataset.t;
        tags.value = ps.map(x => x.trim()).filter(Boolean).join(', ') + ', ';
        sug.innerHTML = '';
        tags.focus();
    });
})();
</script>
