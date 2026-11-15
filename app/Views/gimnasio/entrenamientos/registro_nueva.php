<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
helper('gimnasio');
$tipoActual = $entrenamiento['tipo_sesion'] ?? '';
$titulo = $esHoy ? 'Hoy' : ucfirst(gim_fecha_corta($fecha));
$sub = '<span>' . esc(ucfirst(gim_hace($fecha))) . ($esHoy ? ' · ' . esc(gim_fecha_corta($fecha)) : '') . '</span>';
if ($constancia['racha'] > 1) {
    $sub .= '<span class="fv-dot"></span><span><i class="bi bi-fire" style="color:var(--fv-accent)"></i> ' . $constancia['racha'] . ' semanas seguidas</span>';
}
$acciones = '<button type="button" class="fv-gbtn" id="gmDescanso" title="Arrancar descanso"><i class="bi bi-stopwatch"></i><span class="d-none d-sm-inline">Descanso</span></button>';
if (!empty($anteriores) || !empty($plantillas)) {
    $acciones .= '<button type="button" class="fv-gbtn" data-bs-toggle="modal" data-bs-target="#gmReutilizar"><i class="bi bi-arrow-repeat"></i><span class="d-none d-sm-inline">Reutilizar</span></button>';
}
$acciones .= '<div class="dropdown"><button type="button" class="fv-ibtn" data-bs-toggle="dropdown" title="Más"><i class="bi bi-three-dots"></i></button>'
    . '<ul class="dropdown-menu dropdown-menu-end">'
    . '<li><button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#gmPlantilla"><i class="bi bi-collection me-2"></i>Guardar como plantilla</button></li>'
    . '<li><a class="dropdown-item" href="' . site_url('gimnasio/entrenamientos') . '"><i class="bi bi-calendar3 me-2"></i>Todos los entrenos</a></li>'
    . '<li><hr class="dropdown-divider"></li>'
    . '<li><form method="post" action="' . site_url('gimnasio/entrenamientos/eliminar/' . $entrenamiento_id) . '" data-confirm="¿Eliminar este entrenamiento con todas sus series?" class="m-0"><button class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Eliminar entrenamiento</button></form></li>'
    . '</ul></div>';
?>
<div class="fv gm">
    <?= view('gimnasio/_cabecera', [
        'activa' => 'entrenos', 'sinNav' => true,
        'migas' => ['Entrenos' => site_url('gimnasio/entrenamientos'), gim_fecha_corta($fecha, false) => null],
        'titulo' => esc($titulo), 'sub' => $sub, 'acciones' => $acciones,
    ]) ?>

    <!-- Datos de la sesión: siempre a la vista, se guardan solos -->
    <form class="gm-sesion" id="gmDatos" autocomplete="off">
        <div class="gm-tipos" role="radiogroup" aria-label="Tipo de sesión">
            <?php
            // El tipo elegido va el primero: la fila se desplaza en horizontal y en el móvil quedaría oculto al final
            $tipos = gim_tipos();
            if (isset($tipos[$tipoActual])) {
                $tipos = [$tipoActual => $tipos[$tipoActual]] + $tipos;
            }
            ?>
            <?php foreach ($tipos as $t => $color): ?>
                <button type="button" class="gm-chip<?= $t === $tipoActual ? ' on' : '' ?>" style="--c:<?= $color ?>" data-tipo="<?= esc($t) ?>" role="radio" aria-checked="<?= $t === $tipoActual ? 'true' : 'false' ?>"><span class="pt"></span><?= esc($t) ?></button>
            <?php endforeach; ?>
        </div>
        <input type="hidden" name="tipo_sesion" value="<?= esc($tipoActual) ?>">
        <textarea name="notas_generales" class="gm-nota" rows="1" placeholder="¿Qué tal ha ido? (nota libre)"><?= esc($entrenamiento['notas_generales'] ?? '') ?></textarea>
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            <details class="gm-mas"<?= !empty($entrenamiento['lesiones']) ? ' open' : '' ?>>
                <summary><i class="bi bi-chevron-down"></i> Fecha, molestias</summary>
                <div class="gm-mas-c">
                    <div class="gm-fila2">
                        <div class="gm-campo"><label for="gmFecha">Fecha</label><input type="date" id="gmFecha" name="fecha" class="fv-input" value="<?= esc($entrenamiento['fecha']) ?>"></div>
                        <div class="gm-campo" style="align-content:end"><label class="gm-check"><input type="checkbox" name="sin_molestias" value="1" <?= !empty($entrenamiento['sin_molestias']) ? 'checked' : '' ?>> Sin molestias</label></div>
                    </div>
                    <textarea name="lesiones" class="gm-nota" rows="1" placeholder="Lesiones o molestias"><?= esc($entrenamiento['lesiones'] ?? '') ?></textarea>
                </div>
            </details>
            <span class="gm-guardado der" id="gmGuardado"></span>
        </div>
    </form>

    <?= view('gimnasio/_editor', [
        'ejerciciosAgrupados' => $ejerciciosAgrupados,
        'claveEe'    => 'ee_id',
        'recientes'  => $recientes,
        'grupos'     => $grupos,
        'todos'      => $todos,
        'cronometro' => true,
        'vacioTxt'   => 'Aún no hay ejercicios. Pulsa <b>+</b> para anotar la primera serie' . (!empty($plantillas) || !empty($anteriores) ? ', o <a href="#" data-bs-toggle="modal" data-bs-target="#gmReutilizar" style="color:var(--fv-accent)">reutiliza una rutina</a>.' : '.'),
        'config'     => [
            'id' => (int) $entrenamiento_id, 'campoId' => 'entrenamiento_id', 'claveEe' => 'ee_id',
            'campoReordenar' => 'entrenamiento_ejercicio_id', 'cronometro' => true, 'excluir' => (int) $entrenamiento_id,
            'urls' => [
                'guardar'    => site_url('gimnasio/entrenamientos/guardar-serie'),
                'actualizar' => site_url('gimnasio/entrenamientos/actualizar-serie') . '/',
                'eliminar'   => site_url('gimnasio/entrenamientos/eliminar-serie') . '/',
                'reordenar'  => site_url('gimnasio/entrenamientos/reordenar-ejercicio'),
                'porGrupo'   => site_url('gimnasio/ejercicios/por-grupo') . '/',
                'ultima'     => site_url('gimnasio/entrenamientos/ultima-sesion') . '/',
                'stats'      => site_url('gimnasio/ejercicios/estadisticas') . '/',
            ],
        ],
    ]) ?>
</div>

<?php if (!empty($anteriores) || !empty($plantillas)): ?>
<div class="modal fade" id="gmReutilizar" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-repeat"></i> Reutilizar rutina</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Copia los ejercicios con sus series a esta sesión, en el mismo orden. Luego ajustas lo que cambie. Los que ya estén no se duplican.</p>
                <?php if (!empty($plantillas)): ?>
                    <div class="form-label">Plantillas</div>
                    <div class="list-group mb-3">
                        <?php foreach ($plantillas as $p): ?>
                            <form method="post" action="<?= site_url('gimnasio/plantillas/aplicar/' . $p['id']) ?>" class="list-group-item d-flex align-items-center gap-2">
                                <input type="hidden" name="entrenamiento_id" value="<?= (int) $entrenamiento_id ?>">
                                <div class="flex-grow-1"><div class="fw-medium"><?= esc($p['nombre']) ?></div><div class="small text-muted"><?= (int) $p['num_ejercicios'] ?> ejercicios</div></div>
                                <button class="btn btn-sm btn-primary">Usar</button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($anteriores)): ?>
                    <div class="form-label">Sesiones anteriores</div>
                    <div class="list-group">
                        <?php foreach ($anteriores as $a): ?>
                            <form method="post" action="<?= site_url('gimnasio/entrenamientos/reutilizar/' . $a['id']) ?>" class="list-group-item d-flex align-items-center gap-2">
                                <input type="hidden" name="entrenamiento_id" value="<?= (int) $entrenamiento_id ?>">
                                <div class="flex-grow-1" style="min-width:0">
                                    <div class="fw-medium"><?= esc(ucfirst(gim_fecha_corta($a['fecha']))) ?><?php if (!empty($a['tipo_sesion'])): ?> <span class="gm-tipo" style="--c:<?= gim_tipo_color($a['tipo_sesion']) ?>;margin-left:6px"><?= esc($a['tipo_sesion']) ?></span><?php endif; ?></div>
                                    <div class="small text-muted text-truncate"><?= esc($a['ejercicios_resumen']) ?></div>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary">Usar</button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="gmPlantilla" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?= site_url('gimnasio/plantillas/guardar-desde-entrenamiento/' . $entrenamiento_id) ?>" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-collection"></i> Guardar como plantilla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="gmPlNombre">Nombre</label>
                <input type="text" name="nombre" id="gmPlNombre" class="form-control" placeholder="Empuje A, Full body…" required>
                <div class="form-text">Copia los ejercicios y series de esta sesión.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('gmDatos');
    const estado = document.getElementById('gmGuardado');
    const url = '<?= site_url('gimnasio/entrenamientos/actualizar-datos/' . $entrenamiento_id) ?>';
    const fechaInicial = '<?= esc($entrenamiento['fecha'], 'js') ?>';
    let t = null;

    function guardar() {
        clearTimeout(t);
        t = null;
        const fd = new FormData(form);
        estado.className = 'gm-guardado der';
        estado.textContent = 'Guardando…';
        fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(r => r.json())
            .then(d => {
                if (!d.ok) { estado.className = 'gm-guardado der err'; estado.textContent = d.error || 'No se pudo guardar'; return; }
                if (d.fecha !== fechaInicial) { location.reload(); return; }
                estado.className = 'gm-guardado der ok';
                estado.innerHTML = '<i class="bi bi-check2"></i> Guardado';
            })
            .catch(() => { estado.className = 'gm-guardado der err'; estado.textContent = 'Sin conexión'; });
    }
    const pronto = () => { clearTimeout(t); estado.textContent = ''; t = setTimeout(guardar, 900); };

    form.querySelectorAll('[data-tipo]').forEach(b => b.addEventListener('click', () => {
        const yaEra = b.classList.contains('on');
        form.querySelectorAll('[data-tipo]').forEach(x => { x.classList.remove('on'); x.setAttribute('aria-checked', 'false'); });
        if (!yaEra) {
            b.classList.add('on');
            b.setAttribute('aria-checked', 'true');
            // Pasa al principio de la fila para que siempre se vea cuál está elegido
            const fila = b.parentNode;
            fila.prepend(b);
            fila.scrollTo({ left: 0, behavior: 'smooth' });
        }
        form.tipo_sesion.value = yaEra ? '' : b.dataset.tipo;
        guardar();
    }));
    form.querySelectorAll('textarea').forEach(el => { el.addEventListener('input', pronto); el.addEventListener('blur', () => t && guardar()); });
    form.fecha.addEventListener('change', guardar);
    form.sin_molestias.addEventListener('change', guardar);
    form.addEventListener('submit', e => e.preventDefault());
})();
</script>
<?= $this->endSection() ?>
