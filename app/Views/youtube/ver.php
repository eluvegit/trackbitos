<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
// Leemos los valores por si llegan con ?orden=...&no_vistos=1&relevantes=1
$req = service('request');
$orden = $orden ?? (string)$req->getGet('orden'); // '' | recientes | antiguos | no_vistos | vistos | relevantes
$nv  = $req->getGet('no_vistos') ? 1 : 0;
$rel = $req->getGet('relevantes') ? 1 : 0;
$hasFilters = $orden || $nv || $rel;
$pendientes = $stats['total'] - $stats['vistos'];

$ordenOptions = [
    ''           => 'Orden: posición',
    'recientes'  => 'Más recientes',
    'antiguos'   => 'Más antiguos',
    'no_vistos'  => 'No vistos primero',
    'vistos'     => 'Vistos primero',
    'relevantes' => 'Relevantes primero',
];
?>

<!-- Cabecera -->
<div class="yt-header mb-3">
    <a href="<?= site_url('youtube') ?>" class="yt-back"><i class="bi bi-chevron-left"></i> Listas</a>
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mt-1">
        <h2 class="yt-title mb-0"><?= esc($lista['nombre']) ?></h2>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= site_url('youtube/' . $lista['slug'] . '/importar') ?>">
                <i class="bi bi-upload"></i> <span class="d-none d-sm-inline">Importar</span>
            </a>
            <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= site_url('youtube/' . $lista['slug'] . '/editar') ?>">
                <i class="bi bi-pencil"></i> <span class="d-none d-sm-inline">Editar</span>
            </a>
        </div>
    </div>
</div>

<?php if (session('warn')): ?>
    <div class="alert alert-warning"><?= esc(session('warn')) ?></div>
<?php endif ?>

<!-- Resumen -->
<div class="yt-stats mb-3">
    <div class="yt-stats-row">
        <div class="yt-stat">
            <span class="yt-stat-num"><?= (int)$stats['total'] ?></span>
            <span class="yt-stat-label">Total</span>
        </div>
        <div class="yt-stat">
            <span class="yt-stat-num"><?= (int)$stats['vistos'] ?></span>
            <span class="yt-stat-label">Vistos</span>
        </div>
        <div class="yt-stat">
            <span class="yt-stat-num"><?= (int)$stats['relevantes'] ?></span>
            <span class="yt-stat-label">Relevantes</span>
        </div>
        <div class="yt-stat">
            <span class="yt-stat-num"><?= (int)$pendientes ?></span>
            <span class="yt-stat-label">Pendientes</span>
        </div>
    </div>
    <div class="yt-progress" title="<?= $stats['pct_vistos'] ?>% visto">
        <div class="yt-progress-bar" style="width: <?= $stats['pct_vistos'] ?>%"></div>
    </div>
</div>

<!-- Filtros -->
<form id="filters" class="filterbar mb-3">
    <div class="filters-row">
        <select name="orden" id="f-orden" class="form-select form-select-sm yt-select">
            <?php foreach ($ordenOptions as $value => $label): ?>
                <option value="<?= esc($value) ?>" <?= $orden === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
            <?php endforeach; ?>
        </select>

        <input class="btn-check" type="checkbox" id="f-nv" name="no_vistos" value="1" <?= ($nv ? 'checked' : '') ?>>
        <label class="chip" for="f-nv"><i class="bi bi-circle"></i> No vistos</label>

        <input class="btn-check" type="checkbox" id="f-rel" name="relevantes" value="1" <?= ($rel ? 'checked' : '') ?>>
        <label class="chip" for="f-rel"><i class="bi bi-star-fill"></i> Relevantes</label>

        <span class="small text-muted ms-auto" id="f-counter">
            <?= count($videos) ?>/<?= (int)$stats['total'] ?>
        </span>

        <?php if ($hasFilters): ?>
            <a class="yt-clear" href="<?= site_url('youtube/' . $lista['slug']) ?>" title="Limpiar filtros">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </div>
</form>

<!-- Listado de vídeos -->
<div class="video-list" id="videos-list">
    <?php foreach ($videos as $v): ?>
        <?php
        $isVisto = !empty($v['visto']);
        $isRel   = !empty($v['relevante']);
        $isLargo = !empty($v['largo']);
        ?>
        <div
            class="video-item <?= $isVisto ? 'is-visto' : '' ?> <?= $isRel ? 'is-relevante' : '' ?>"
            data-id="<?= (int)$v['id'] ?>"
            data-posicion="<?= (int)$v['posicion'] ?>"
            data-visto="<?= $isVisto ? '1' : '0' ?>"
            data-relevante="<?= $isRel ? '1' : '0' ?>"
            data-largo="<?= $isLargo ? '1' : '0' ?>">

            <button type="button" class="video-btn video-check js-toggle-visto" data-id="<?= (int)$v['id'] ?>" aria-pressed="<?= $isVisto ? 'true' : 'false' ?>" aria-label="Marcar como visto">
                <i class="bi <?= $isVisto ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i>
            </button>

            <a href="<?= esc($v['url']) ?>" target="_blank" rel="noopener" class="video-link">
                <span class="video-title"><?= esc($v['titulo'] ?: 'Abrir') ?></span>
                <span class="video-meta">#<?= (int)$v['posicion'] ?></span>
            </a>

            <div class="video-actions">
                <button type="button" class="video-btn video-star js-toggle-relevante" data-id="<?= (int)$v['id'] ?>" aria-pressed="<?= $isRel ? 'true' : 'false' ?>" aria-label="Marcar relevante">
                    <i class="bi <?= $isRel ? 'bi-star-fill' : 'bi-star' ?>"></i>
                </button>

                <button type="button" class="video-btn video-largo js-toggle-largo <?= $isLargo ? 'is-active' : '' ?>" data-id="<?= (int)$v['id'] ?>" aria-pressed="<?= $isLargo ? 'true' : 'false' ?>" aria-label="Marcar como vídeo largo">
                    <i class="bi bi-camera-reels"></i>
                </button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="video-empty d-none" id="videos-empty">
    <i class="bi bi-inbox"></i>
    <p>No hay vídeos que coincidan con estos filtros.</p>
</div>

<style>
/* ================================
   Cabecera
   ================================ */
.yt-back {
    display: inline-flex;
    align-items: center;
    font-size: .85rem;
    color: var(--bs-secondary-color);
    text-decoration: none;
}
.yt-back:hover { color: var(--bs-emphasis-color); }

.yt-title {
    font-size: 1.35rem;
    font-weight: 700;
}

/* ================================
   Resumen / stats
   ================================ */
.yt-stats {
    background: var(--bs-tertiary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 16px;
    padding: 12px 14px;
}

.yt-stats-row {
    display: flex;
    gap: 1.25rem;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.yt-stat {
    display: flex;
    flex-direction: column;
    flex: 0 0 auto;
}

.yt-stat-num {
    font-size: 1.1rem;
    font-weight: 700;
    line-height: 1.1;
    color: var(--bs-emphasis-color);
}

.yt-stat-label {
    font-size: .72rem;
    color: var(--bs-secondary-color);
    text-transform: uppercase;
    letter-spacing: .04em;
}

.yt-progress {
    margin-top: 10px;
    height: 6px;
    border-radius: 999px;
    background: rgba(124, 58, 237, .12);
    overflow: hidden;
}

.yt-progress-bar {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #7c3aed, #a78bfa);
    transition: width .2s ease;
}

/* ================================
   Filtros — barra minimalista de una línea
   ================================ */
.filters-row {
    display: flex;
    align-items: center;
    gap: .5rem;
    flex-wrap: wrap;
}

.yt-select {
    width: auto;
    max-width: 170px;
    border-radius: 999px;
    font-size: .8rem;
}

.chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .32rem .6rem;
    font-size: .78rem;
    white-space: nowrap;
    border-radius: 999px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
    color: var(--bs-secondary-color);
    cursor: pointer;
    transition: all .15s ease;
}
.chip:hover { filter: brightness(1.1); }
.btn-check:checked + .chip {
    border-color: #34d399;
    background: rgba(52,211,153,.15);
    box-shadow: inset 0 0 0 1px rgba(52,211,153,.3);
    color: #34d399;
}

#f-counter { color: var(--bs-secondary-color); font-size: .78rem; white-space: nowrap; }

.yt-clear {
    display: grid;
    place-items: center;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    color: var(--bs-secondary-color);
    background: var(--bs-tertiary-bg);
    border: 1px solid var(--bs-border-color);
    font-size: .75rem;
    flex: 0 0 auto;
}
.yt-clear:hover { color: var(--bs-emphasis-color); }

/* ================================
   Listado de vídeos
   ================================ */
.video-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.video-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 14px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    transition: background-color .15s ease, border-color .15s ease;
}

.video-item.is-relevante {
    border-color: rgba(245, 158, 11, .4);
    background: rgba(245, 158, 11, .06);
}

.video-item.is-visto {
    opacity: .55;
}

.video-item.is-visto:hover,
.video-item.is-visto:focus-within {
    opacity: .9;
}

.video-btn {
    flex: 0 0 auto;
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
    color: var(--bs-secondary-color);
    font-size: 1.05rem;
    line-height: 1;
    cursor: pointer;
    transition: all .15s ease;
}
.video-btn:hover { filter: brightness(1.15); }
.video-btn:active { transform: scale(.92); }

.video-check[aria-pressed="true"] {
    color: #10b981;
    border-color: rgba(16,185,129,.4);
    background: rgba(16,185,129,.12);
}

.video-star[aria-pressed="true"] {
    color: #f59e0b;
    border-color: rgba(245,158,11,.4);
    background: rgba(245,158,11,.12);
}

.video-largo.is-active {
    color: #6366f1;
    border-color: rgba(99,102,241,.4);
    background: rgba(99,102,241,.12);
}

.video-actions {
    display: flex;
    align-items: center;
    gap: 6px;
    flex: 0 0 auto;
}

.video-link {
    flex: 1 1 auto;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
    text-decoration: none;
    padding: 4px 0;
}

.video-title {
    font-weight: 600;
    font-size: .95rem;
    line-height: 1.3;
    color: var(--bs-emphasis-color);
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
.video-item.is-visto .video-title {
    font-weight: 400;
    color: var(--bs-secondary-color);
}

.video-meta {
    font-size: .72rem;
    color: var(--bs-secondary-color);
}

.video-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--bs-secondary-color);
}
.video-empty i { font-size: 2rem; opacity: .5; }
.video-empty p { margin-top: .5rem; margin-bottom: 0; }

/* ================================
   Mobile
   ================================ */
@media (max-width: 575.98px) {
    .yt-title { font-size: 1.15rem; }
    .yt-select { max-width: none; flex: 1 1 100%; }
    .video-item { padding: 8px; gap: 8px; }
    .video-btn { width: 36px; height: 36px; font-size: 1rem; }
    .video-title { font-size: .9rem; }
}
</style>

<script>
    (() => {
        const list  = document.getElementById('videos-list');
        const empty = document.getElementById('videos-empty');

        const inputs = {
            orden: document.getElementById('f-orden'),
            noVistos: document.getElementById('f-nv'),
            relev: document.getElementById('f-rel'),
        };
        const counter = document.getElementById('f-counter');

        function currentOptions() {
            const orden = inputs.orden.value || '';
            const nv = inputs.noVistos.checked ? 1 : 0;
            const rl = inputs.relev.checked ? 1 : 0;
            return { orden, nv, rl };
        }

        function applyItemState(item) {
            const visto = item.dataset.visto === '1';
            const rel   = item.dataset.relevante === '1';
            const largo = item.dataset.largo === '1';

            item.classList.toggle('is-visto', visto);
            item.classList.toggle('is-relevante', rel);

            const checkBtn = item.querySelector('.video-check');
            checkBtn.setAttribute('aria-pressed', visto ? 'true' : 'false');
            checkBtn.querySelector('i').className = visto ? 'bi bi-check-circle-fill' : 'bi bi-circle';

            const starBtn = item.querySelector('.video-star');
            starBtn.setAttribute('aria-pressed', rel ? 'true' : 'false');
            starBtn.querySelector('i').className = rel ? 'bi bi-star-fill' : 'bi bi-star';

            const largoBtn = item.querySelector('.video-largo');
            largoBtn.setAttribute('aria-pressed', largo ? 'true' : 'false');
            largoBtn.classList.toggle('is-active', largo);
        }

        function filterRows() {
            const { nv, rl } = currentOptions();
            let shown = 0;

            list.querySelectorAll('.video-item').forEach(item => {
                const visto = item.dataset.visto === '1';
                const rel   = item.dataset.relevante === '1';

                let visible = true;
                if (nv && visto) visible = false;
                if (rl && !rel) visible = false;

                item.style.display = visible ? '' : 'none';
                if (visible) shown++;
            });

            if (counter) {
                const baseTotal = <?= (int)$stats['total'] ?>;
                counter.textContent = `${shown}/${baseTotal}`;
            }

            empty.classList.toggle('d-none', shown !== 0);
        }

        function sortRows() {
            const { orden } = currentOptions();
            const items = [...list.querySelectorAll('.video-item')].filter(item => item.style.display !== 'none');

            items.sort((a, b) => {
                const av = Number(a.dataset.visto), bv = Number(b.dataset.visto);
                const ar = Number(a.dataset.relevante), br = Number(b.dataset.relevante);
                const ap = Number(a.dataset.posicion), bp = Number(b.dataset.posicion);

                switch (orden) {
                    case 'recientes':   return bp - ap;
                    case 'no_vistos':   return (av !== bv) ? av - bv : ap - bp;
                    case 'vistos':      return (av !== bv) ? bv - av : ap - bp;
                    case 'relevantes':  return (ar !== br) ? br - ar : ap - bp;
                    case 'antiguos':
                    default:            return ap - bp;
                }
            });

            items.forEach(item => list.appendChild(item));
        }

        function updateView() {
            filterRows();
            sortRows();
        }

        inputs.orden.addEventListener('change', updateView);
        inputs.noVistos.addEventListener('change', updateView);
        inputs.relev.addEventListener('change', updateView);

        updateView();

        // === Toggles AJAX con actualización instantánea ===
        async function postToggle(url) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '<?= csrf_hash() ?>'
                }
            });
        }

        const toggleBase = {
            visto: '<?= site_url('youtube/toggle-visto') ?>',
            relevante: '<?= site_url('youtube/toggle-relevante') ?>',
            largo: '<?= site_url('youtube/toggle-largo') ?>',
        };

        list.addEventListener('click', async (e) => {
            const btn = e.target.closest('.video-btn');
            if (!btn) return;
            const item = btn.closest('.video-item');
            const id = btn.dataset.id;

            let field;
            if (btn.classList.contains('js-toggle-visto')) field = 'visto';
            else if (btn.classList.contains('js-toggle-relevante')) field = 'relevante';
            else if (btn.classList.contains('js-toggle-largo')) field = 'largo';
            else return;

            const url = toggleBase[field] + '/' + id;
            const res = await postToggle(url);
            if (!res.ok) return;

            item.dataset[field] = item.dataset[field] === '1' ? '0' : '1';
            applyItemState(item);
            updateView();
        });
    })();

    (() => {
        const form = document.getElementById('filters');
        if (!form) return;

        const apply = () => {
            const fd = new FormData(form);
            const params = new URLSearchParams();
            for (const [k, v] of fd.entries()) {
                if (v !== '') params.set(k, v);
            }
            const url = new URL(window.location.href);
            url.search = params.toString();
            window.location.assign(url.toString());
        };

        form.addEventListener('change', apply);
    })();
</script>

<?= $this->endSection() ?>
