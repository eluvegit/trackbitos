<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
$s = $sesion;
$sesionId = (int) $s['id'];
$general = $moodboard_por_situacion['general'] ?? [];

$colorEstado = [
    'planificacion' => 'bg-secondary',
    'edicion'       => 'bg-warning text-dark',
    'subiendo'      => 'bg-info text-dark',
    'completado'    => 'bg-success',
];
$labelEstado = [
    'planificacion' => 'Planificación',
    'edicion'       => 'Edición',
    'subiendo'      => 'Subiendo',
    'completado'    => 'Completado',
];
$ordenEstados = array_keys($labelEstado);

$colorEntrega = [
    'no_aplica' => 'bg-secondary',
    'pendiente' => 'bg-warning text-dark',
    'entregado' => 'bg-success',
];
$labelEntrega = [
    'no_aplica' => 'No aplica',
    'pendiente' => 'Pendiente de entregar',
    'entregado' => 'Entregado',
];

$partes = ['foto' => ['icono' => 'bi-camera', 'nombre' => 'Fotografía'], 'video' => ['icono' => 'bi-camera-video', 'nombre' => 'Vídeo']];
$pausada = (int) $s['pausada'] === 1;

// El rodaje "arranca" en cuanto CUALQUIER parte aplicable pasa de
// planificación. Antes de eso, las secciones de preparación (notas,
// briefing, moodboard, situaciones, equipo) van abiertas y "model
// releases" cerrada; en cuanto arranca, se invierte: las de preparación
// quedan como consulta (plegadas) y toca ocuparse de la entrega.
$enMarcha = false;
foreach (['foto', 'video'] as $parte) {
    $valor = $s['estado_' . $parte];
    if ($valor !== null && $valor !== 'planificacion') {
        $enMarcha = true;
    }
}

$secciones = [
    'notas'       => !$enMarcha,
    'briefing'    => !$enMarcha,
    'moodboard'   => !$enMarcha,
    'situaciones' => !$enMarcha,
    'equipo'      => !$enMarcha,
    'releases'    => $enMarcha,
];
?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-camera text-primary"></i>
    <a href="<?= site_url('sesiones') ?>" class="text-decoration-none text-muted fw-normal">Sesiones</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold"><?= esc($s['titulo']) ?></strong>
</h5>

<div class="card mb-4" id="sesionHeader" data-id="<?= $sesionId ?>">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h4 class="mb-1"><?= esc($s['titulo']) ?></h4>
                <?php if ($s['fecha_sesion']): ?>
                    <span class="badge bg-light text-dark"><i class="bi bi-calendar-event"></i> <?= esc($s['fecha_sesion']) ?></span>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= site_url('sesiones/' . $sesionId . '/editar') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-pencil"></i> Editar
                </a>
                <form method="post" action="<?= site_url('sesiones/' . $sesionId . '/convertir-en-idea') ?>"
                      onsubmit="return confirm('¿Volver a convertir esta sesión en idea? Se perderán el equipo y los model releases asociados, y el moodboard se quedará como un único bloque sin situaciones.');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-lightbulb"></i> Convertir en idea</button>
                </form>
            </div>
        </div>

        <?php foreach ($partes as $parte => $info): ?>
            <?php $estadoActual = $s['estado_' . $parte]; ?>
            <?php if ($estadoActual === null): continue; endif; ?>
            <?php $indiceActual = array_search($estadoActual, $ordenEstados, true); ?>
            <div class="mt-3">
                <div class="linea-vida-titulo"><i class="bi <?= $info['icono'] ?>"></i> <?= esc($info['nombre']) ?></div>
                <div class="linea-vida" data-parte="<?= $parte ?>">
                    <?php foreach ($ordenEstados as $i => $estado): ?>
                        <?php if ($i > 0): ?>
                            <div class="linea-vida-segmento <?= $i <= $indiceActual ? 'completado' : '' ?>"></div>
                        <?php endif; ?>
                        <button type="button"
                                class="linea-vida-punto <?= $i < $indiceActual ? 'completado' : ($i === $indiceActual ? 'activo ' . $colorEstado[$estado] : '') ?>"
                                data-estado="<?= $estado ?>"
                                title="<?= $labelEstado[$estado] ?>">
                            <span class="linea-vida-label"><?= $labelEstado[$estado] ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="d-flex flex-wrap gap-3 align-items-center mt-4">
            <button type="button" id="btnPausada" class="btn btn-sm <?= $pausada ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                    title="<?= $pausada ? 'Pulsa para reanudar' : 'Pulsa para pausar' ?>">
                <i class="bi <?= $pausada ? 'bi-play-fill' : 'bi-pause-fill' ?>"></i>
                <span id="btnPausadaTexto"><?= $pausada ? 'Pausada' : 'En marcha' ?></span>
            </button>

            <div>
                <label class="form-label small mb-1 d-block">Entrega a la modelo</label>
                <div class="dropdown entrega-dropdown">
                    <button type="button" class="btn btn-sm dropdown-toggle <?= $colorEntrega[$s['entrega_modelos']] ?>" data-bs-toggle="dropdown">
                        <?= $labelEntrega[$s['entrega_modelos']] ?>
                    </button>
                    <ul class="dropdown-menu">
                        <?php foreach ($labelEntrega as $val => $label): ?>
                            <li><a class="dropdown-item entrega-opcion" href="#" data-valor="<?= $val ?>"><?= $label ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- NOTAS -->
<div class="card mb-3">
    <div class="card-header p-0">
        <button class="seccion-toggle <?= $secciones['notas'] ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#seccionNotas">
            <span><i class="bi bi-journal-text me-1"></i>Notas</span>
            <i class="bi bi-chevron-down"></i>
        </button>
    </div>
    <div class="collapse <?= $secciones['notas'] ? 'show' : '' ?>" id="seccionNotas">
        <div class="card-body">
            <form method="post" action="<?= site_url('sesiones/' . $sesionId . '/actualizar') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="titulo" value="<?= esc($s['titulo']) ?>">
                <input type="hidden" name="fecha_sesion" value="<?= esc($s['fecha_sesion'] ?? '') ?>">
                <textarea name="notas" class="form-control mb-2" rows="4"><?= esc($s['notas'] ?? '') ?></textarea>
                <button type="submit" class="btn btn-sm btn-outline-primary">Guardar notas</button>
            </form>
        </div>
    </div>
</div>

<!-- BRIEFING -->
<div class="card mb-3">
    <div class="card-header p-0">
        <button class="seccion-toggle <?= $secciones['briefing'] ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#seccionBriefing">
            <span><i class="bi bi-file-text me-1"></i>Briefing</span>
            <i class="bi bi-chevron-down"></i>
        </button>
    </div>
    <div class="collapse <?= $secciones['briefing'] ? 'show' : '' ?>" id="seccionBriefing">
        <div class="card-body">
            <form method="post" action="<?= site_url('sesiones/' . $sesionId . '/actualizar') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="titulo" value="<?= esc($s['titulo']) ?>">
                <input type="hidden" name="fecha_sesion" value="<?= esc($s['fecha_sesion'] ?? '') ?>">
                <textarea name="briefing" class="form-control mb-2" rows="6" placeholder="Desarrollo de la idea, descripción de la sesión, detalles a tener en cuenta..."><?= esc($s['briefing'] ?? '') ?></textarea>
                <div class="form-text mb-2">Se incluye en el informe exportable, por ejemplo para pasárselo a la modelo.</div>
                <button type="submit" class="btn btn-sm btn-outline-primary">Guardar briefing</button>
            </form>
        </div>
    </div>
</div>

<!-- MOODBOARD -->
<div class="card mb-3">
    <div class="card-header p-0">
        <button class="seccion-toggle <?= $secciones['moodboard'] ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#seccionMoodboard">
            <span><i class="bi bi-images me-1"></i>Moodboard</span>
            <i class="bi bi-chevron-down"></i>
        </button>
    </div>
    <div class="collapse <?= $secciones['moodboard'] ? 'show' : '' ?>" id="seccionMoodboard">
        <div class="card-body">
            <div class="d-flex justify-content-end mb-2">
                <a href="<?= site_url('sesiones/' . $sesionId . '/exportar') ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                    <i class="bi bi-printer"></i> Exportar todo
                </a>
            </div>
            <div class="gallery-grid moodboard-grid mb-2" id="moodboardGeneral">
                <?php foreach ($general as $item): ?>
                    <?= view('sesiones/_moodboard_item', ['item' => $item]) ?>
                <?php endforeach; ?>
            </div>
            <?= view('sesiones/_moodboard_form', ['sesionId' => $sesionId, 'situacionId' => null]) ?>
        </div>
    </div>
</div>

<!-- SITUACIONES -->
<div class="card mb-3">
    <div class="card-header p-0">
        <button class="seccion-toggle <?= $secciones['situaciones'] ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#seccionSituaciones">
            <span><i class="bi bi-collection me-1"></i>Situaciones</span>
            <i class="bi bi-chevron-down"></i>
        </button>
    </div>
    <div class="collapse <?= $secciones['situaciones'] ? 'show' : '' ?>" id="seccionSituaciones">
        <div class="card-body">
            <div class="accordion" id="situacionesAccordion">
                <?php foreach ($situaciones as $sit): ?>
                    <?php $items = $moodboard_por_situacion[$sit['id']] ?? []; ?>
                    <div class="accordion-item situacion-item" data-id="<?= (int) $sit['id'] ?>">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#situacion<?= $sit['id'] ?>">
                                <?= esc($sit['nombre']) ?>
                                <span class="badge bg-secondary ms-2 situacion-count"><?= count($items) ?></span>
                            </button>
                        </h2>
                        <div id="situacion<?= $sit['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#situacionesAccordion">
                            <div class="accordion-body">
                                <div class="d-flex justify-content-end mb-2">
                                    <a href="<?= site_url('sesiones/' . $sesionId . '/situaciones/' . $sit['id'] . '/exportar') ?>" class="btn btn-sm btn-outline-secondary me-2" target="_blank">
                                        <i class="bi bi-printer"></i> Exportar
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger situacion-borrar"><i class="bi bi-trash"></i> Borrar situación</button>
                                </div>
                                <div class="gallery-grid moodboard-grid mb-2">
                                    <?php foreach ($items as $item): ?>
                                        <?= view('sesiones/_moodboard_item', ['item' => $item]) ?>
                                    <?php endforeach; ?>
                                </div>
                                <?= view('sesiones/_moodboard_form', ['sesionId' => $sesionId, 'situacionId' => $sit['id']]) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <form id="situacionForm" class="d-flex gap-2 mt-3">
                <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Nueva situación..." required>
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i></button>
            </form>
        </div>
    </div>
</div>

<!-- EQUIPO -->
<div class="card mb-3">
    <div class="card-header p-0">
        <button class="seccion-toggle <?= $secciones['equipo'] ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#seccionEquipo">
            <span><i class="bi bi-bag-check me-1"></i>Equipo</span>
            <i class="bi bi-chevron-down"></i>
        </button>
    </div>
    <div class="collapse <?= $secciones['equipo'] ? 'show' : '' ?>" id="seccionEquipo">
        <div class="card-body">
            <ul class="list-unstyled equipo-list" id="equipoList">
                <?php foreach ($equipo as $item): ?>
                    <li class="equipo-item d-flex align-items-center gap-2 mb-2" data-id="<?= (int) $item['id'] ?>">
                        <input type="checkbox" class="form-check-input equipo-check" <?= (int) $item['marcado'] === 1 ? 'checked' : '' ?>>
                        <span class="equipo-nombre flex-grow-1 <?= (int) $item['marcado'] === 1 ? 'text-decoration-line-through text-muted' : '' ?>"><?= esc($item['item']) ?></span>
                        <button type="button" class="btn btn-sm btn-link text-danger p-0 equipo-borrar"><i class="bi bi-x-lg"></i></button>
                    </li>
                <?php endforeach; ?>
            </ul>
            <form id="equipoForm" class="d-flex gap-2 mt-2">
                <input type="text" name="item" class="form-control form-control-sm" placeholder="Añadir ítem..." required>
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i></button>
            </form>
        </div>
    </div>
</div>

<!-- MODEL RELEASES -->
<div class="card mb-3">
    <div class="card-header p-0">
        <button class="seccion-toggle <?= $secciones['releases'] ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#seccionReleases">
            <span><i class="bi bi-file-earmark-text me-1"></i>Model releases</span>
            <i class="bi bi-chevron-down"></i>
        </button>
    </div>
    <div class="collapse <?= $secciones['releases'] ? 'show' : '' ?>" id="seccionReleases">
        <div class="card-body">
            <ul class="list-unstyled releases-list" id="releasesList">
                <?php foreach ($model_releases as $r): ?>
                    <li class="d-flex align-items-center gap-2 mb-2" data-id="<?= (int) $r['id'] ?>">
                        <a href="<?= base_url($r['ruta_archivo']) ?>" target="_blank" rel="noopener" class="flex-grow-1">
                            <?= esc($r['nombre_modelo']) ?>
                            <?php if ($r['fecha']): ?><span class="text-muted small">(<?= esc($r['fecha']) ?>)</span><?php endif; ?>
                        </a>
                        <button type="button" class="btn btn-sm btn-link text-danger p-0 release-borrar"><i class="bi bi-x-lg"></i></button>
                    </li>
                <?php endforeach; ?>
            </ul>
            <form id="releaseForm" class="d-flex flex-column gap-2 mt-2" enctype="multipart/form-data">
                <input type="text" name="nombre_modelo" class="form-control form-control-sm" placeholder="Nombre del modelo" required>
                <input type="date" name="fecha" class="form-control form-control-sm">
                <input type="file" name="archivo" class="form-control form-control-sm" accept="image/*,application/pdf" required>
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-upload"></i> Subir</button>
            </form>

            <hr>

            <h6 class="fw-bold mb-2"><i class="bi bi-chat-left-text me-1"></i>Mensaje para la modelo</h6>
            <form method="post" action="<?= site_url('sesiones/' . $sesionId . '/actualizar') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="titulo" value="<?= esc($s['titulo']) ?>">
                <input type="hidden" name="fecha_sesion" value="<?= esc($s['fecha_sesion'] ?? '') ?>">
                <textarea name="mensaje_modelos" id="mensajeModelosTexto" class="form-control mb-2" rows="6" placeholder="Mensaje completo para enviar (enlaces de referencia, horario, ubicación...)"><?= esc($s['mensaje_modelos'] ?? '') ?></textarea>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-outline-primary">Guardar mensaje</button>
                    <button type="button" id="btnCopiarMensaje" class="btn btn-sm btn-outline-secondary"><i class="bi bi-clipboard"></i> Copiar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.linea-vida-titulo {
    font-size: .85rem;
    font-weight: 600;
    margin-bottom: 20px;
}
.linea-vida {
    display: flex;
    align-items: center;
    max-width: 480px;
}
.linea-vida-punto {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    flex-shrink: 0;
    padding: 0;
    cursor: pointer;
    position: relative;
}
.linea-vida-punto.completado {
    background: var(--bs-success);
    border-color: var(--bs-success);
}
.linea-vida-punto.activo {
    width: 26px;
    height: 26px;
    border-width: 3px;
    border-color: var(--bs-body-color);
}
.linea-vida-label {
    position: absolute;
    top: 26px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 11px;
    white-space: nowrap;
    color: var(--bs-secondary-color);
}
.linea-vida-segmento {
    flex: 1 1 auto;
    height: 3px;
    min-width: 16px;
    background: var(--bs-border-color);
}
.linea-vida-segmento.completado {
    background: var(--bs-success);
}

.seccion-toggle {
    width: 100%;
    background: transparent;
    border: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .6rem 1rem;
    font-weight: 600;
    color: var(--bs-body-color);
}
.seccion-toggle .bi-chevron-down {
    transition: transform .2s;
}
.seccion-toggle.collapsed .bi-chevron-down {
    transform: rotate(-90deg);
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
}
.gallery-item {
    aspect-ratio: 1 / 1;
    border-radius: 12px;
    overflow: hidden;
    background: var(--bs-tertiary-bg);
    display: block;
    border: 1px solid var(--bs-border-color);
    position: relative;
}
.gallery-item img { width: 100%; height: 100%; object-fit: cover; }
.gallery-item .item-borrar {
    position: absolute;
    top: 4px;
    right: 4px;
    background: rgba(0,0,0,.6);
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
}
.moodboard-form { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-top: 8px; }
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(() => {
    const sesionId = <?= $sesionId ?>;
    const base = '<?= site_url('sesiones') ?>/' + sesionId;
    const csrf = '<?= csrf_hash() ?>';

    async function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
                'Content-Type': 'application/json',
            },
            body: body ? JSON.stringify(body) : undefined,
        });
    }

    async function postForm(url, formData) {
        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
            body: formData,
        });
    }

    // ---- Línea de vida (foto / vídeo, independientes) ----
    document.querySelectorAll('.linea-vida').forEach(linea => {
        const parte = linea.dataset.parte;
        linea.querySelectorAll('.linea-vida-punto').forEach(punto => {
            punto.addEventListener('click', async () => {
                const res = await post(`${base}/estado`, { parte, estado: punto.dataset.estado });
                if (!res.ok) { console.error('No se pudo cambiar el estado'); return; }
                location.reload();
            });
        });
    });

    // ---- Pausada (play/pausa) ----
    const btnPausada = document.getElementById('btnPausada');
    btnPausada.addEventListener('click', async () => {
        const res = await post(`${base}/toggle-pausada`);
        if (!res.ok) { console.error('No se pudo cambiar pausada'); return; }
        const data = await res.json();
        const icono = btnPausada.querySelector('i');
        btnPausada.classList.toggle('btn-outline-warning', data.valor);
        btnPausada.classList.toggle('btn-outline-success', !data.valor);
        icono.classList.toggle('bi-play-fill', data.valor);
        icono.classList.toggle('bi-pause-fill', !data.valor);
        document.getElementById('btnPausadaTexto').textContent = data.valor ? 'Pausada' : 'En marcha';
        btnPausada.title = data.valor ? 'Pulsa para reanudar' : 'Pulsa para pausar';
    });

    // ---- Entrega a la modelo ----
    document.querySelectorAll('.entrega-opcion').forEach(opt => {
        opt.addEventListener('click', async (e) => {
            e.preventDefault();
            const res = await post(`${base}/entrega-modelos`, { valor: opt.dataset.valor });
            if (!res.ok) { console.error('No se pudo cambiar la entrega'); return; }
            location.reload();
        });
    });

    // ---- Mensaje para la modelo: copiar al portapapeles ----
    document.getElementById('btnCopiarMensaje').addEventListener('click', async () => {
        const texto = document.getElementById('mensajeModelosTexto').value;
        try {
            await navigator.clipboard.writeText(texto);
        } catch (e) {
            console.error('No se pudo copiar', e);
        }
    });

    // ---- Equipo ----
    const equipoList = document.getElementById('equipoList');

    equipoList.addEventListener('change', async (e) => {
        if (!e.target.classList.contains('equipo-check')) return;
        const li = e.target.closest('.equipo-item');
        const res = await post(`${base}/equipo/${li.dataset.id}/toggle`);
        if (!res.ok) { e.target.checked = !e.target.checked; return; }
        const data = await res.json();
        li.querySelector('.equipo-nombre').classList.toggle('text-decoration-line-through', data.marcado);
        li.querySelector('.equipo-nombre').classList.toggle('text-muted', data.marcado);
    });

    equipoList.addEventListener('click', async (e) => {
        const btn = e.target.closest('.equipo-borrar');
        if (!btn) return;
        const li = btn.closest('.equipo-item');
        const res = await post(`${base}/equipo/${li.dataset.id}/borrar`);
        if (res.ok) li.remove();
    });

    document.getElementById('equipoForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = e.target.item;
        const nombre = input.value.trim();
        if (!nombre) return;
        const res = await post(`${base}/equipo/agregar`, { item: nombre });
        if (!res.ok) return;
        const data = await res.json();
        const li = document.createElement('li');
        li.className = 'equipo-item d-flex align-items-center gap-2 mb-2';
        li.dataset.id = data.item.id;
        li.innerHTML = `<input type="checkbox" class="form-check-input equipo-check">
            <span class="equipo-nombre flex-grow-1">${data.item.item.replace(/[<>&]/g, c => ({'<':'&lt;','>':'&gt;','&':'&amp;'}[c]))}</span>
            <button type="button" class="btn btn-sm btn-link text-danger p-0 equipo-borrar"><i class="bi bi-x-lg"></i></button>`;
        equipoList.appendChild(li);
        input.value = '';
    });

    // ---- Situaciones ----
    document.getElementById('situacionForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = e.target.nombre;
        const nombre = input.value.trim();
        if (!nombre) return;
        const res = await post(`${base}/situaciones/crear`, { nombre });
        if (!res.ok) return;
        location.reload();
    });

    document.getElementById('situacionesAccordion').addEventListener('click', async (e) => {
        const btn = e.target.closest('.situacion-borrar');
        if (!btn) return;
        if (!confirm('¿Borrar esta situación? Su moodboard quedará como general.')) return;
        const item = btn.closest('.situacion-item');
        const res = await post(`${base}/situaciones/${item.dataset.id}/borrar`);
        if (res.ok) location.reload();
    });

    // ---- Moodboard (archivo / enlace / borrar) ----
    document.querySelectorAll('.moodboard-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const url = form.dataset.origen === 'archivo' ? `${base}/moodboard/subir` : `${base}/moodboard/enlace`;

            let res;
            if (form.dataset.origen === 'archivo') {
                res = await postForm(url, new FormData(form));
            } else {
                res = await post(url, Object.fromEntries(new FormData(form).entries()));
            }

            if (!res.ok) { console.error('No se pudo añadir al moodboard'); return; }
            location.reload();
        });
    });

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.item-borrar');
        if (!btn) return;
        const el = btn.closest('.gallery-item');
        const res = await post(`${base}/moodboard/${el.dataset.id}/borrar`);
        if (res.ok) el.remove();
    });

    // ---- Model releases ----
    document.getElementById('releaseForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await postForm(`${base}/releases/subir`, new FormData(e.target));
        if (!res.ok) { console.error('No se pudo subir el release'); return; }
        location.reload();
    });

    document.getElementById('releasesList').addEventListener('click', async (e) => {
        const btn = e.target.closest('.release-borrar');
        if (!btn) return;
        const li = btn.closest('li');
        const res = await post(`${base}/releases/${li.dataset.id}/borrar`);
        if (res.ok) li.remove();
    });
})();
</script>

<?= $this->endSection() ?>
