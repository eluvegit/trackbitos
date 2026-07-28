<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
$datosSesiones = array_map(static function ($s) {
    return [
        'id'           => (int) $s['id'],
        'titulo'       => $s['titulo'],
        'pausada'      => (int) $s['pausada'] === 1,
        'entrega'      => $s['entrega_modelos'],
        'estado_foto'  => $s['estado_foto'],
        'estado_video' => $s['estado_video'],
    ];
}, $sesiones);
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h5 class="mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-camera text-primary"></i> Sesiones
    </h5>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <a href="<?= site_url('sesiones/crear') ?>?idea=1" class="btn btn-sm btn-outline-secondary rounded-pill">
            <i class="bi bi-lightbulb"></i> + Idea
        </a>
        <a href="<?= site_url('sesiones/crear') ?>" class="btn btn-sm btn-primary rounded-pill btn-nueva-desktop">
            <i class="bi bi-plus-lg"></i> Nueva sesión
        </a>
    </div>
</div>

<a href="<?= site_url('sesiones/crear') ?>" class="fab-nueva-sesion" title="Nueva sesión">
    <i class="bi bi-plus-lg"></i>
</a>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-danger py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<?php if (empty($sesiones)): ?>
    <p class="text-muted">Todavía no hay sesiones. <a href="<?= site_url('sesiones/crear') ?>">Crea la primera</a>.</p>
<?php else: ?>

<div class="ses-filtros d-flex flex-wrap align-items-center gap-2 mb-3">
    <div class="d-flex flex-wrap gap-1">
        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill filtro-etapa" data-estado="planificacion">Planificación</button>
        <button type="button" class="btn btn-sm btn-outline-warning rounded-pill filtro-etapa" data-estado="edicion">Edición</button>
        <button type="button" class="btn btn-sm btn-outline-info rounded-pill filtro-etapa" data-estado="subiendo">Subiendo</button>
        <button type="button" class="btn btn-sm btn-outline-success rounded-pill filtro-etapa" data-estado="completado">Completado</button>
        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill filtro-etapa" data-estado="idea"><i class="bi bi-lightbulb"></i> Ideas</button>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill active" id="toggleOcultarCompletadas" aria-pressed="true">
        <i class="bi bi-eye-slash"></i> Completadas
    </button>
    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="toggleDividir" aria-pressed="false">
        <i class="bi bi-arrows-collapse"></i> Dividir
    </button>
</div>

<div class="ses-card">
    <div class="table-responsive">
    <table class="table kanban-table align-middle mb-0" id="kanbanTabla">
        <thead>
            <tr>
                <th class="kanban-col-sesion">Sesión</th>
                <th>Progreso</th>
            </tr>
        </thead>
        <tbody id="kanbanBody"></tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<style>
.ses-card {
    background: var(--bs-tertiary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 1.25rem;
    box-shadow: 0 10px 30px -12px rgba(0, 0, 0, .45);
    overflow: hidden;
}
.kanban-table th, .kanban-table td {
    vertical-align: middle;
    padding: 1rem;
}
.kanban-table thead th {
    font-weight: 700;
    border-bottom-width: 1px;
}
.kanban-col-sesion {
    min-width: 200px;
}

.ses-filtros .btn {
    border-width: 2px;
    border-color: transparent;
    transition: border-color .15s;
    font-size: .75rem;
    padding: .25rem .65rem;
}
.ses-filtros .btn:hover,
.ses-filtros .btn:focus,
.ses-filtros .btn:active {
    box-shadow: none !important;
}
.ses-filtros .btn.btn-outline-secondary,
.ses-filtros .btn.btn-outline-secondary:hover,
.ses-filtros .btn.btn-outline-secondary:focus,
.ses-filtros .btn.btn-outline-secondary:active {
    background-color: var(--bs-secondary-bg-subtle) !important;
    color: var(--bs-secondary-text-emphasis) !important;
}
.ses-filtros .btn.btn-outline-warning,
.ses-filtros .btn.btn-outline-warning:hover,
.ses-filtros .btn.btn-outline-warning:focus,
.ses-filtros .btn.btn-outline-warning:active {
    background-color: var(--bs-warning-bg-subtle) !important;
    color: var(--bs-warning-text-emphasis) !important;
}
.ses-filtros .btn.btn-outline-info,
.ses-filtros .btn.btn-outline-info:hover,
.ses-filtros .btn.btn-outline-info:focus,
.ses-filtros .btn.btn-outline-info:active {
    background-color: var(--bs-info-bg-subtle) !important;
    color: var(--bs-info-text-emphasis) !important;
}
.ses-filtros .btn.btn-outline-success,
.ses-filtros .btn.btn-outline-success:hover,
.ses-filtros .btn.btn-outline-success:focus,
.ses-filtros .btn.btn-outline-success:active {
    background-color: var(--bs-success-bg-subtle) !important;
    color: var(--bs-success-text-emphasis) !important;
}
.ses-filtros .btn.btn-outline-primary,
.ses-filtros .btn.btn-outline-primary:hover,
.ses-filtros .btn.btn-outline-primary:focus,
.ses-filtros .btn.btn-outline-primary:active {
    background-color: var(--bs-primary-bg-subtle) !important;
    color: var(--bs-primary-text-emphasis) !important;
}
.ses-filtros .btn.active.btn-outline-secondary { border-color: var(--bs-secondary); }
.ses-filtros .btn.active.btn-outline-warning   { border-color: var(--bs-warning); }
.ses-filtros .btn.active.btn-outline-info      { border-color: var(--bs-info); }
.ses-filtros .btn.active.btn-outline-success   { border-color: var(--bs-success); }
.ses-filtros .btn.active.btn-outline-primary   { border-color: var(--bs-primary); }

.mini-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    font-size: .8rem;
    flex-shrink: 0;
}
.mini-badge.is-neutral {
    background: var(--bs-secondary-bg-subtle);
    color: var(--bs-secondary-text-emphasis);
}
.mini-badge.is-warning {
    background: var(--bs-warning-bg-subtle);
    color: var(--bs-warning-text-emphasis);
}
.mini-badge.is-success {
    background: var(--bs-success-bg-subtle);
    color: var(--bs-success-text-emphasis);
}

.progreso-fila {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}
.progreso-fila:last-child {
    margin-bottom: 0;
}
.progreso-fila > i {
    width: 18px;
    text-align: center;
    color: var(--bs-secondary-color);
    flex-shrink: 0;
}
.progreso-bar {
    display: flex;
    gap: 3px;
    width: 140px;
    flex-shrink: 0;
}
.progreso-seg {
    flex: 1;
    height: 9px;
    border-radius: 4px;
    background: var(--bs-border-color);
    cursor: pointer;
    transition: background .15s;
}
.progreso-seg:hover {
    filter: brightness(1.3);
}
.progreso-label {
    font-size: .8rem;
    color: var(--bs-secondary-color);
    white-space: nowrap;
    flex: 0 0 auto;
    width: 88px;
    text-align: left;
}

.fab-nueva-sesion {
    display: none;
}

@media (max-width: 640px) {
    .btn-nueva-desktop {
        display: none;
    }
    .fab-nueva-sesion {
        position: fixed;
        right: 18px;
        bottom: 24px;
        width: 58px;
        height: 58px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--bs-primary), #0b5ed7);
        color: #fff;
        font-size: 1.5rem;
        box-shadow: 0 6px 18px rgba(13, 110, 253, .45);
        z-index: 1050;
        display: grid;
        place-items: center;
        text-decoration: none;
    }
    .fab-nueva-sesion:hover,
    .fab-nueva-sesion:active {
        color: #fff;
        transform: scale(.96);
    }
    .kanban-table {
        font-size: .82rem;
    }
    .kanban-table thead {
        display: none;
    }
    .kanban-table, .kanban-table tbody, .kanban-table tr, .kanban-table td {
        display: block;
        width: 100%;
    }
    .kanban-table tr {
        border-bottom: 1px solid var(--bs-border-color);
    }
    .kanban-table tr:last-child {
        border-bottom: none;
    }
    .kanban-table td {
        padding: .35rem 1rem !important;
        border: none !important;
    }
    .kanban-col-sesion {
        min-width: 0;
        padding-top: .75rem !important;
        padding-bottom: 0 !important;
    }
    .kanban-table tr td:last-child {
        padding-bottom: .75rem !important;
    }
    .kanban-col-sesion a {
        font-size: .9rem;
    }
    .mini-badge {
        width: 20px;
        height: 20px;
        font-size: .7rem;
    }
    .progreso-fila {
        gap: 6px;
    }
    .progreso-fila > i {
        width: 14px;
        font-size: .8rem;
    }
    .progreso-bar {
        width: auto;
        flex: 1 1 auto;
        min-width: 0;
    }
    .progreso-label {
        font-size: .7rem;
        width: 62px;
    }
}
</style>

<script>
(() => {
    const base = '<?= site_url('sesiones') ?>';
    const csrf = '<?= csrf_hash() ?>';
    const colorBarra   = { idea: 'bg-primary', planificacion: 'bg-secondary', edicion: 'bg-warning', subiendo: 'bg-info', completado: 'bg-success' };
    const labelEstado  = { idea: 'Idea', planificacion: 'Planificación', edicion: 'Edición', subiendo: 'Subiendo', completado: 'Completado' };
    const ordenEstados = Object.keys(labelEstado);
    const iconoParte   = { foto: 'bi-camera', video: 'bi-camera-video' };
    const entregaInfo  = {
        no_aplica: { clave: 'neutral', titulo: 'No necesita entrega', icono: 'bi-envelope-fill' },
        pendiente: { clave: 'warning', titulo: 'Entrega pendiente', icono: 'bi-envelope-fill' },
        entregado: { clave: 'success', titulo: 'Entregado', icono: 'bi-envelope-check-fill' },
    };

    const sesiones = <?= json_encode($datosSesiones, JSON_UNESCAPED_UNICODE) ?>;

    const tbody = document.getElementById('kanbanBody');
    if (!tbody) return;

    const filtros = new Set();
    let ocultarCompletadas = true;
    let dividir = false;

    function crearBarra(parte, estado, id) {
        const idx = ordenEstados.indexOf(estado);
        const fila = document.createElement('div');
        fila.className = 'progreso-fila';
        fila.dataset.id = id;
        fila.dataset.parte = parte;

        const icono = document.createElement('i');
        icono.className = `bi ${iconoParte[parte]}`;
        icono.title = parte === 'foto' ? 'Fotografía' : 'Vídeo';
        fila.appendChild(icono);

        const bar = document.createElement('div');
        bar.className = 'progreso-bar';
        ordenEstados.forEach((est, i) => {
            const seg = document.createElement('div');
            seg.className = 'progreso-seg' + (i <= idx ? ` relleno ${colorBarra[estado]}` : '');
            seg.dataset.estado = est;
            seg.title = labelEstado[est];
            seg.addEventListener('click', () => cambiarEstado(id, parte, est));
            bar.appendChild(seg);
        });
        fila.appendChild(bar);

        const label = document.createElement('span');
        label.className = 'progreso-label';
        label.textContent = labelEstado[estado];
        fila.appendChild(label);

        return fila;
    }

    async function cambiarEstado(id, parte, estado) {
        try {
            const res = await fetch(`${base}/${id}/estado`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ parte, estado }),
            });
            if (!res.ok) throw new Error('estado change failed');
            const s = sesiones.find(x => x.id === id);
            if (s) s['estado_' + parte] = estado;
            render();
        } catch (e) {
            console.error(e);
        }
    }

    function crearBadges(s) {
        const wrap = document.createElement('div');
        wrap.className = 'd-flex align-items-center gap-1 mt-1';

        const bPausa = document.createElement('span');
        bPausa.className = 'mini-badge ' + (s.pausada ? 'is-warning' : 'is-neutral');
        bPausa.title = s.pausada ? 'Pausada' : 'En marcha';
        bPausa.innerHTML = `<i class="bi ${s.pausada ? 'bi-pause-fill' : 'bi-play-fill'}"></i>`;
        wrap.appendChild(bPausa);

        const info = entregaInfo[s.entrega] || entregaInfo.no_aplica;
        const bEntrega = document.createElement('span');
        bEntrega.className = 'mini-badge is-' + info.clave;
        bEntrega.title = info.titulo;
        bEntrega.innerHTML = `<i class="bi ${info.icono}"></i>`;
        wrap.appendChild(bEntrega);

        return wrap;
    }

    function partesDe(s) {
        return ['foto', 'video']
            .filter(p => s['estado_' + p] !== null)
            .map(p => ({ parte: p, estado: s['estado_' + p] }));
    }

    function estadosVisibles() {
        if (filtros.size > 0) return filtros;
        const set = new Set(ordenEstados);
        // 'idea' es un estado más, pero no se muestra por defecto: hace
        // falta activar el filtro "Ideas" a propósito para verlas.
        set.delete('idea');
        if (ocultarCompletadas) set.delete('completado');
        return set;
    }

    function render() {
        const visibles = estadosVisibles();
        tbody.innerHTML = '';

        let filas;
        if (dividir) {
            filas = [];
            sesiones.forEach(s => {
                partesDe(s).forEach(({ parte, estado }) => {
                    filas.push({ s, parte, estado, idx: ordenEstados.indexOf(estado) });
                });
            });
        } else {
            filas = sesiones.map(s => {
                const partes = partesDe(s);
                const idx = Math.min(...partes.map(p => ordenEstados.indexOf(p.estado)));
                return { s, partes, idx };
            });
        }

        filas = filas.filter(f => (dividir ? visibles.has(f.estado) : f.partes.some(p => visibles.has(p.estado))));
        filas.sort((a, b) => a.idx - b.idx || a.s.titulo.localeCompare(b.s.titulo));

        if (filas.length === 0) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 2;
            td.className = 'text-center text-muted py-4';
            td.textContent = 'No hay resultados que coincidan con el filtro.';
            tr.appendChild(td);
            tbody.appendChild(tr);
            return;
        }

        filas.forEach(f => {
            const tr = document.createElement('tr');

            const tdSesion = document.createElement('td');
            tdSesion.className = 'kanban-col-sesion';
            const a = document.createElement('a');
            a.href = `${base}/${f.s.id}`;
            a.className = 'fw-semibold text-decoration-none';
            a.textContent = f.s.titulo;
            tdSesion.appendChild(a);
            tdSesion.appendChild(crearBadges(f.s));
            tr.appendChild(tdSesion);

            const tdProgreso = document.createElement('td');
            if (dividir) {
                tdProgreso.appendChild(crearBarra(f.parte, f.estado, f.s.id));
            } else {
                f.partes.forEach(p => tdProgreso.appendChild(crearBarra(p.parte, p.estado, f.s.id)));
            }
            tr.appendChild(tdProgreso);

            tbody.appendChild(tr);
        });
    }

    document.querySelectorAll('.filtro-etapa').forEach(btn => {
        btn.addEventListener('click', () => {
            const estado = btn.dataset.estado;
            if (filtros.has(estado)) {
                filtros.delete(estado);
                btn.classList.remove('active');
            } else {
                filtros.add(estado);
                btn.classList.add('active');
            }
            btn.blur();
            render();
        });
    });

    const btnOcultar = document.getElementById('toggleOcultarCompletadas');
    btnOcultar.addEventListener('click', () => {
        ocultarCompletadas = !ocultarCompletadas;
        btnOcultar.setAttribute('aria-pressed', String(ocultarCompletadas));
        btnOcultar.classList.toggle('active', ocultarCompletadas);
        btnOcultar.blur();
        render();
    });

    const btnDividir = document.getElementById('toggleDividir');
    btnDividir.addEventListener('click', () => {
        dividir = !dividir;
        btnDividir.setAttribute('aria-pressed', String(dividir));
        btnDividir.classList.toggle('active', dividir);
        btnDividir.blur();
        render();
    });

    render();
})();
</script>

<?= $this->endSection() ?>
