<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?= $this->include('coche/_estilos') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-bell text-primary"></i>
    <a href="<?= site_url('coche') ?>" class="text-decoration-none text-muted fw-normal">Coche</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Recordatorios</strong>

    <a href="<?= site_url('coche/recordatorios/nuevo') ?>"
        class="text-decoration-none ms-1 text-success"
        title="Nuevo recordatorio">
        <i class="bi bi-plus-circle fs-5"></i>
    </a>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2 d-flex align-items-center gap-2">
        <i class="bi bi-check-circle"></i><div><?= esc(session('success')) ?></div>
    </div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-danger py-2 d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle"></i><div><?= esc(session('error')) ?></div>
    </div>
<?php endif; ?>

<?php if (empty($recordatorios)): ?>
    <p class="text-muted">Todavía no hay recordatorios. Crea el primero con el botón "+".</p>
<?php endif; ?>

<?php foreach ($recordatorios as $r): ?>
    <?php
    $info = $estado[$r['id']] ?? null;
    $nivel = $info['nivel'] ?? null;
    $texto = $info['texto'] ?? 'Sin registros';
    $ultimaFecha = $info['ultima_fecha'] ?? null;
    ?>
    <div class="coche-rec-card <?= $nivel ? 'coche-nivel-' . $nivel : '' ?>" data-id="<?= (int) $r['id'] ?>">
        <div class="coche-rec-icono">
            <i class="bi bi-bell"></i>
        </div>

        <div class="coche-rec-main">
            <div class="coche-rec-row-top">
                <div class="coche-rec-titulo"><?= esc($r['title']) ?></div>
                <span class="coche-badge <?= $nivel ? 'coche-badge-' . $nivel : 'coche-badge-neutro' ?>"><?= esc($texto) ?></span>
            </div>

            <div class="coche-rec-row-bottom">
                <div class="coche-rec-meta">
                    Cada <?= esc($r['interval_days']) ?> días / <?= esc($r['interval_km']) ?> km
                    <?php if ($ultimaFecha): ?>
                        · Última: <?= date('d/m/Y', strtotime($ultimaFecha)) ?>
                    <?php endif; ?>
                </div>

                <div class="coche-rec-actions">
                    <?php if ($r['interval_days']): ?>
                        <button type="button" class="coche-btn js-renovar"
                                data-id="<?= (int) $r['id'] ?>"
                                data-titulo="<?= esc($r['title']) ?>"
                                title="Renovar">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    <?php endif; ?>
                    <a href="<?= site_url('coche/recordatorios/editar/' . $r['id']) ?>" class="coche-btn" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form action="<?= site_url('coche/recordatorios/borrar/' . $r['id']) ?>" method="post" class="m-0"
                          onsubmit="return confirm('¿Borrar este recordatorio?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="coche-btn coche-btn-danger" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            <?php if (!empty($r['notes'])): ?>
                <div class="coche-rec-meta"><?= esc($r['notes']) ?></div>
            <?php endif; ?>
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

<script>
(() => {
    const modalEl = document.getElementById('modalRenovar');
    const inputFecha = document.getElementById('modalRenovarFecha');
    const tituloEl = document.getElementById('modalRenovarTitulo');
    const btnConfirmar = document.getElementById('modalRenovarConfirmar');

    // bootstrap.bundle.min.js se carga al final del layout, después de este
    // script, así que la instancia del modal se crea perezosamente (en el
    // primer clic) en vez de al cargar la página, para no depender del orden.
    let modal = null;
    let idActual = null;

    function hoyISO() {
        const d = new Date();
        const tz = d.getTimezoneOffset() * 60000;
        return new Date(d - tz).toISOString().slice(0, 10);
    }

    document.querySelectorAll('.js-renovar').forEach(btn => {
        btn.addEventListener('click', () => {
            modal ??= new bootstrap.Modal(modalEl);
            idActual = btn.dataset.id;
            tituloEl.textContent = btn.dataset.titulo;
            inputFecha.value = hoyISO();
            modal.show();
        });
    });

    btnConfirmar.addEventListener('click', async () => {
        if (!idActual) return;
        btnConfirmar.disabled = true;

        const res = await fetch('<?= site_url('coche/recordatorios') ?>/' + idActual + '/renovar', {
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

        // La agrupación/orden puede cambiar al renovar, recargamos para reflejarlo.
        window.location.reload();
    });
})();
</script>

<?= $this->endSection() ?>
