<?php
/**
 * Formulario de ejercicio (alta y edición).
 * @var array|null $ejercicio  null = alta
 * @var string     $grupo      grupo preseleccionado en el alta
 */
helper('gimnasio');
$editar = !empty($ejercicio);
$grupoSel = $editar ? $ejercicio['grupo_muscular'] : ($grupo ?? '');
?>
<div class="fv gm estrecho">
    <?= view('gimnasio/_cabecera', ['activa' => 'ejercicios',
        'migas' => ['Ejercicios' => site_url('gimnasio/ejercicios'), ($editar ? 'Editar' : 'Nuevo') => null],
        'titulo' => $editar ? esc($ejercicio['nombre']) : 'Nuevo ejercicio']) ?>

    <form class="fv-panel gm-form" method="post" action="<?= site_url($editar ? 'gimnasio/ejercicios/update/' . $ejercicio['id'] : 'gimnasio/ejercicios/store') ?>">
        <div class="gm-campo">
            <label for="gmNombre">Nombre</label>
            <input type="text" id="gmNombre" name="nombre" class="fv-input" value="<?= esc($editar ? $ejercicio['nombre'] : '') ?>" required autocomplete="off">
        </div>
        <div class="gm-campo">
            <span class="gm-lbl">Grupo muscular</span>
            <input type="hidden" name="grupo_muscular" id="gmGrupo" value="<?= esc($grupoSel) ?>" required>
            <div class="gm-chips" id="gmGrupos">
                <?php foreach (gim_grupos() as $k => $n): ?>
                    <button type="button" class="gm-chip<?= $k === $grupoSel ? ' on' : '' ?>" data-grupo="<?= esc($k) ?>"><?= esc($n) ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="gm-campo" id="gmYaHay" hidden>
            <span class="gm-lbl">Ya tienes en este grupo</span>
            <div class="gm-chips" id="gmYaHayLista"></div>
        </div>
        <div class="gm-form-pie">
            <a class="fv-btn" href="<?= site_url($editar ? 'gimnasio/ejercicios/estadisticas/' . $ejercicio['id'] : 'gimnasio/ejercicios') ?>">Cancelar</a>
            <button class="fv-btn fv-btn-pri" id="gmGuardar" <?= $grupoSel ? '' : 'disabled' ?>><i class="bi bi-check-lg"></i> <?= $editar ? 'Guardar' : 'Crear ejercicio' ?></button>
        </div>
        <?php if (!$editar): ?>
            <p class="gm-ayuda">¿Buscas uno con animación e instrucciones? Mira el <a href="<?= site_url('gimnasio/catalogo') ?>" style="color:var(--fv-accent)">catálogo</a> y añádelo desde allí.</p>
        <?php endif; ?>
    </form>
</div>
<script>
(function () {
    const input = document.getElementById('gmGrupo');
    const btn = document.getElementById('gmGuardar');
    const caja = document.getElementById('gmYaHay'), listaEl = document.getElementById('gmYaHayLista');
    const editandoId = <?= $editar ? (int) $ejercicio['id'] : 0 ?>;
    function cargar(g) {
        if (!g) { caja.hidden = true; return; }
        fetch('<?= site_url('gimnasio/ejercicios/por-grupo') ?>/' + encodeURIComponent(g)).then(r => r.json()).then(d => {
            d = d.filter(e => +e.id !== editandoId);
            listaEl.innerHTML = '';
            d.forEach(e => { const s = document.createElement('span'); s.className = 'gm-chip'; s.textContent = e.nombre; listaEl.append(s); });
            caja.hidden = d.length === 0;
        });
    }
    document.querySelectorAll('#gmGrupos [data-grupo]').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('#gmGrupos [data-grupo]').forEach(x => x.classList.toggle('on', x === b));
        input.value = b.dataset.grupo;
        btn.disabled = false;
        cargar(b.dataset.grupo);
    }));
    if (!editandoId && input.value) cargar(input.value);
    // Foco solo con ratón: en el móvil sacaría el teclado nada más entrar
    if (!editandoId && window.matchMedia('(hover: hover) and (pointer: fine)').matches) document.getElementById('gmNombre').focus();
})();
</script>
