<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-calendar-heart text-primary"></i>
    <a href="<?= site_url('dashboard') ?>" class="text-decoration-none text-muted fw-normal">Dashboard</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Recordatorios</strong>

    <a href="<?= site_url('recordatorios/nuevo') ?>"
        class="text-decoration-none ms-1 text-success"
        title="Nuevo recordatorio">
        <i class="bi bi-plus-circle fs-5"></i>
    </a>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<?php
$grupoActual = null;
$etiquetasGrupo = [
    'caducado' => ['Caducados', 'bi-exclamation-octagon-fill'],
    'urgente'  => ['Próximos 30 días', 'bi-alarm-fill'],
    'proximo'  => ['De 1 a 3 meses', 'bi-calendar3'],
    'lejano'   => ['Más adelante', 'bi-calendar-check'],
];
?>

<?php if (empty($recordatorios)): ?>
    <p class="text-muted">Todavía no hay recordatorios. Crea el primero con el botón "+".</p>
<?php endif; ?>

<?php foreach ($recordatorios as $r): ?>
    <?php if ($r['nivel'] !== $grupoActual): ?>
        <?php $grupoActual = $r['nivel']; [$etiqueta, $icono] = $etiquetasGrupo[$grupoActual]; ?>
        <div class="rec-grupo-titulo">
            <i class="bi <?= $icono ?>"></i> <?= $etiqueta ?>
        </div>
    <?php endif; ?>

    <div class="rec-card rec-nivel-<?= $r['nivel'] ?>" data-id="<?= (int)$r['id'] ?>">
        <div class="rec-icono">
            <?php if (recordatorio_es_icono_bootstrap($r['icono'] ?: 'calendar-event')): ?>
                <i class="bi bi-<?= esc($r['icono'] ?: 'calendar-event') ?>"></i>
            <?php else: ?>
                <span><?= esc($r['icono']) ?></span>
            <?php endif; ?>
        </div>

        <div class="rec-main">
            <div class="rec-row-top">
                <div class="rec-titulo"><?= esc($r['titulo']) ?></div>
                <span class="rec-badge rec-badge-<?= $r['nivel'] ?>"><?= esc($r['texto']) ?></span>
            </div>

            <div class="rec-row-bottom">
                <div class="rec-meta">
                    <?= esc($r['categoria_label']) ?> · <?= date('d/m/Y', strtotime($r['fecha_mostrar'])) ?>
                    <?php if ($r['periodo_meses']): ?>
                        · <?= (int)$r['periodo_meses'] ?>m
                    <?php endif; ?>
                    <?php if ($r['recalculada']): ?>
                        <i class="bi bi-arrow-repeat rec-recalculada"
                           title="Fecha guardada: <?= date('d/m/Y', strtotime($r['fecha_evento'])) ?>. Se muestra el siguiente ciclo calculado automáticamente."></i>
                    <?php endif; ?>
                </div>

                <div class="rec-actions">
                    <?php if ($r['periodo_meses']): ?>
                        <button type="button" class="rec-btn js-renovar"
                                data-id="<?= (int)$r['id'] ?>"
                                data-titulo="<?= esc($r['titulo']) ?>"
                                data-periodo="<?= (int)$r['periodo_meses'] ?>"
                                title="Renovar">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    <?php endif; ?>
                    <a href="<?= site_url('recordatorios/editar/' . $r['id']) ?>" class="rec-btn" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form action="<?= site_url('recordatorios/borrar/' . $r['id']) ?>" method="post" class="m-0"
                          onsubmit="return confirm('¿Eliminar este recordatorio?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="rec-btn rec-btn-danger" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- Modal: Renovar -->
<div class="modal fade" id="modalRenovar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Renovar «<span id="modalRenovarTitulo"></span>»</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <label for="modalRenovarFecha" class="form-label">¿Qué día se hizo?</label>
                <input type="date" id="modalRenovarFecha" class="form-control">
                <div class="form-text" id="modalRenovarAyuda"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="modalRenovarConfirmar">
                    <i class="bi bi-arrow-clockwise"></i> Renovar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.rec-grupo-titulo {
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--bs-secondary-color);
    margin: 1.25rem 0 .5rem;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.rec-grupo-titulo:first-of-type { margin-top: 0; }

.rec-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 7px 10px;
    margin-bottom: 6px;
    border-radius: 12px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    transition: background-color .15s ease;
}
.rec-card:hover { background: var(--bs-tertiary-bg); }

.rec-nivel-caducado { border-color: rgba(220,53,69,.4); background: rgba(220,53,69,.06); }
.rec-nivel-urgente  { border-color: rgba(245,158,11,.4); background: rgba(245,158,11,.06); }

.rec-icono {
    flex: 0 0 auto;
    width: 32px;
    height: 32px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: var(--bs-tertiary-bg);
    color: var(--bs-emphasis-color);
    font-size: .95rem;
    line-height: 1;
}

.rec-main { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 1px; }

.rec-row-top { display: flex; align-items: center; gap: 8px; }
.rec-titulo {
    flex: 1 1 auto;
    min-width: 0;
    font-weight: 700;
    font-size: .92rem;
    color: var(--bs-emphasis-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.rec-badge {
    flex: 0 0 auto;
    display: inline-block;
    padding: .18rem .55rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 700;
    white-space: nowrap;
}
.rec-badge-caducado { background: rgba(220,53,69,.15); color: #dc3545; }
.rec-badge-urgente  { background: rgba(245,158,11,.18); color: #f59e0b; }
.rec-badge-proximo  { background: rgba(99,102,241,.15); color: #818cf8; }
.rec-badge-lejano   { background: rgba(16,185,129,.15); color: #10b981; }

.rec-row-bottom { display: flex; align-items: center; justify-content: space-between; gap: 8px; }

.rec-meta {
    flex: 1 1 auto;
    min-width: 0;
    font-size: .72rem;
    color: var(--bs-secondary-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.rec-recalculada { margin-left: .25rem; color: #818cf8; cursor: help; }

.rec-actions { flex: 0 0 auto; display: flex; align-items: center; gap: 0; }
.rec-btn {
    width: 26px;
    height: 26px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: var(--bs-secondary-color);
    text-decoration: none;
    cursor: pointer;
    font-size: .82rem;
}
.rec-btn:hover { background: var(--bs-tertiary-bg); color: var(--bs-emphasis-color); }
.rec-btn-danger:hover { color: #dc3545; }
</style>

<script>
(() => {
    const modalEl = document.getElementById('modalRenovar');
    // bootstrap.bundle.min.js se carga al final del layout, después de este
    // script, así que la instancia del modal se crea perezosamente (en el
    // primer clic) en vez de al cargar la página, para no depender del orden.
    let modal = null;
    const inputFecha = document.getElementById('modalRenovarFecha');
    const tituloEl = document.getElementById('modalRenovarTitulo');
    const ayudaEl = document.getElementById('modalRenovarAyuda');
    const btnConfirmar = document.getElementById('modalRenovarConfirmar');

    let idActual = null;
    let periodoActual = null;

    function hoyISO() {
        const d = new Date();
        const tz = d.getTimezoneOffset() * 60000;
        return new Date(d - tz).toISOString().slice(0, 10);
    }

    function actualizarAyuda() {
        if (!periodoActual) return;
        const fecha = inputFecha.value ? new Date(inputFecha.value + 'T00:00:00') : new Date();
        const nueva = new Date(fecha);
        nueva.setMonth(nueva.getMonth() + periodoActual);
        ayudaEl.textContent = 'La próxima fecha quedará en ' + nueva.toLocaleDateString('es-ES') +
            ' (ese día + ' + periodoActual + ' mes' + (periodoActual === 1 ? '' : 'es') + ').';
    }

    document.querySelectorAll('.js-renovar').forEach(btn => {
        btn.addEventListener('click', () => {
            modal ??= new bootstrap.Modal(modalEl);
            idActual = btn.dataset.id;
            periodoActual = parseInt(btn.dataset.periodo, 10);
            tituloEl.textContent = btn.dataset.titulo;
            inputFecha.value = hoyISO();
            actualizarAyuda();
            modal.show();
        });
    });

    inputFecha.addEventListener('change', actualizarAyuda);

    btnConfirmar.addEventListener('click', async () => {
        if (!idActual) return;
        btnConfirmar.disabled = true;

        const res = await fetch('<?= site_url('recordatorios') ?>/' + idActual + '/renovar', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ fecha_realizado: inputFecha.value }),
        });

        if (!res.ok) {
            btnConfirmar.disabled = false;
            alert('No se pudo renovar. Revisa la fecha.');
            return;
        }

        // La posición/agrupación de la tarjeta puede cambiar al renovar,
        // así que recargamos para reordenar correctamente.
        window.location.reload();
    });
})();
</script>

<?= $this->endSection() ?>
