<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2>LISTA: <?= esc($lista['nombre']) ?></h2>

<?php if (session('warn')): ?>
    <div class="alert alert-warning"><?= esc(session('warn')) ?></div>
<?php endif ?>


<div class="mb-2">
    <!--<a class="btn btn-outline-secondary btn-sm" href="<?= site_url('youtube/' . $lista['slug'] . '/importar-playlist') ?>">Importar Playlist</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('youtube/' . $lista['slug'] . '/importar-texto') ?>">Importar texto</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('youtube/' . $lista['slug'] . '/importar-html') ?>">Importar HTML</a>-->
    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('youtube/' . $lista['slug'] . '/importar') ?>">Importar JSON</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('youtube/' . $lista['slug'] . '/editar') ?>">Editar</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('youtube') ?>">← Volver</a>
</div>

<?php
// Leemos los valores por si llegan con ?sort_...&no_vistos=1&relevantes=1
$req = service('request');
$sv  = (string)$req->getGet('sort_vistos');        // '' | 'no_vistos_primero' | 'vistos_primero'
$sr  = (string)$req->getGet('sort_relevantes');    // '' | 'primero'
$nv  = $req->getGet('no_vistos') ? 1 : 0;
$rel = $req->getGet('relevantes') ? 1 : 0;
?>

<!-- Toolbar de filtros (estilo moderno, aplica al instante) -->
<form id="filters" class="filterbar card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">

        <!-- Segmento: Orden por vistos -->
        <div class="filter-group">
            <div class="filter-label">Orden Vistos</div>
            <div class="seg">
                <input type="radio" class="btn-check" name="sort_vistos" id="sv-none" value="" <?= ($sv === '' ? 'checked' : '') ?>>
                <label class="seg-btn" for="sv-none" title="Sin ordenar">—</label>

                <input type="radio" class="btn-check" name="sort_vistos" id="sv-no" value="no_vistos_primero" <?= ($sv === 'no_vistos_primero' ? 'checked' : '') ?>>
                <label class="seg-btn" for="sv-no">
                    <!-- ojo “no visto” -->
                    <svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5C21.27 7.61 17 4.5 12 4.5Zm0 12a4.5 4.5 0 1 1 0-9a4.5 4.5 0 0 1 0 9Z" />
                    </svg>
                    No vistos primero
                </label>

                <input type="radio" class="btn-check" name="sort_vistos" id="sv-si" value="vistos_primero" <?= ($sv === 'vistos_primero' ? 'checked' : '') ?>>
                <label class="seg-btn" for="sv-si">
                    <!-- check “visto” -->
                    <svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="currentColor" d="M9.55 17.45L4.8 12.7l1.4-1.4l3.35 3.35l7.2-7.2l1.4 1.4Z" />
                    </svg>
                    Vistos primero
                </label>
            </div>
        </div>

        <!-- Segmento: Relevantes -->
        <div class="filter-group">
            <div class="filter-label">Relevantes</div>
            <div class="seg">
                <input type="radio" class="btn-check" name="sort_relevantes" id="sr-none" value="" <?= ($sr === '' ? 'checked' : '') ?>>
                <label class="seg-btn" for="sr-none">—</label>

                <input type="radio" class="btn-check" name="sort_relevantes" id="sr-primero" value="primero" <?= ($sr === 'primero' ? 'checked' : '') ?>>
                <label class="seg-btn" for="sr-primero">
                    <!-- estrella -->
                    <svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="currentColor" d="m12 17.27l6.18 3.73l-1.64-7.03L21 9.24l-7.19-.61L12 2L10.19 8.63L3 9.24l4.46 4.73L5.82 21z" />
                    </svg>
                    Primero
                </label>
            </div>
        </div>

        <!-- Chips: Solo no vistos / Solo relevantes -->
        <div class="d-flex align-items-center gap-2 mt-4">
            <input class="btn-check" type="checkbox" id="f-nv" name="no_vistos" value="1" <?= ($nv ? 'checked' : '') ?>>
            <label class="chip" for="f-nv">
                <span class="dot dot-gray"></span> Solo no vistos
            </label>

            <input class="btn-check" type="checkbox" id="f-rel" name="relevantes" value="1" <?= ($rel ? 'checked' : '') ?>>
            <label class="chip" for="f-rel">
                <span class="dot dot-amber"></span> Solo relevantes
            </label>
        </div>

        <!-- Lado derecho: contador + limpiar -->
        <div class="ms-auto d-flex align-items-center gap-2 mt-4">
            <span class="small text-muted" id="f-counter">
                Mostrando <?= count($videos) ?> de <?= (int)$stats['total'] ?>
            </span>

            <?php if ($sv || $sr || $nv || $rel): ?>
                <a class="btn btn-sm btn-light border" href="<?= site_url('youtube/' . $lista['slug']) ?>">
                    Limpiar
                </a>
            <?php endif; ?>
        </div>
    </div>
</form>


<div class="alert alert-secondary">
    Total: <strong><?= $stats['total'] ?></strong> ·
    Vistos: <strong><?= $stats['vistos'] ?></strong> (<?= $stats['pct_vistos'] ?>%) ·
    Relevantes: <strong><?= $stats['relevantes'] ?></strong> (<?= $stats['pct_relev'] ?>%) ·
    Pendientes: <strong><?= $stats['total'] - $stats['vistos'] ?></strong> (<?= $stats['total'] ? round(($stats['total'] - $stats['vistos']) * 100 / $stats['total'], 1) : 0 ?>%)
</div>

<table class="table table-sm table-hover align-middle" id="videos-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Titulo</th>
            <th>✓</th>
            <th>★</th>
            <th>🎬</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($videos as $v): ?>
            <?php
            $isVisto = !empty($v['visto']);
            $isRel   = !empty($v['relevante']);

            // Clases para la fila
            $rowClasses = [];
            if ($isRel) {
                $rowClasses[] = 'table-warning';
            } elseif (!$isVisto) {
                $rowClasses[] = 'table-light';
            }
            if ($isVisto) {
                $rowClasses[] = 'opacity-50';
            }
            $rowClassStr = implode(' ', $rowClasses);

            // Estilo del título (negrita si NO visto)
            $titleClass = $isVisto ? '' : 'fw-bold';
            ?>
            <tr
                class="<?= $rowClassStr ?>"
                data-id="<?= (int)$v['id'] ?>"
                data-posicion="<?= (int)$v['posicion'] ?>"
                data-visto="<?= $isVisto ? '1' : '0' ?>"
                data-relevante="<?= $isRel ? '1' : '0' ?>"
                data-largo="<?= !empty($v['largo']) ? '1' : '0' ?>">
                <td class="cell-posicion"><?= (int)$v['posicion'] ?></td>
                <td class="cell-titulo">
                    <a href="<?= esc($v['url']) ?>" target="_blank" rel="noopener" class="text-decoration-none <?= $titleClass ?>">
                        <?= esc($v['titulo'] ?: 'Abrir') ?>
                    </a>
                </td>
                <td class="cell-visto">
                    <button class="btn btn-sm <?= $isVisto ? 'btn-success' : 'btn-outline-secondary' ?> js-toggle-visto" data-id="<?= (int)$v['id'] ?>">
                        <?= $isVisto ? '✓' : '✓' ?>
                    </button>
                </td>
                <td class="cell-relevante">
                    <button class="btn btn-sm <?= $isRel ? 'btn-warning' : 'btn-outline-secondary' ?> js-toggle-relevante" data-id="<?= (int)$v['id'] ?>">
                        <?= $isRel ? '★' : '★' ?>
                    </button>
                </td>
                <td class="cell-largo">
                    <button class="btn btn-sm <?= !empty($v['largo']) ? 'btn-info' : 'btn-outline-secondary' ?> js-toggle-largo" data-id="<?= (int)$v['id'] ?>">
                        <?= !empty($v['largo']) ? '🎬 Largo' : 'Largo' ?>
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<style>
   /* ================================
   General Cards y Anillos
   ================================ */

.trk-card {
    --trk-border: rgba(0,0,0,.08);
    --trk-bg: var(--bs-body-bg);
    display: grid;
    grid-template-columns: 56px 1fr 18px;
    gap: .9rem;
    align-items: center;
    padding: 14px 16px;
    border-radius: 16px;
    border: 1px solid var(--trk-border);
    background: var(--trk-bg);
    box-shadow: 0 6px 20px rgba(0,0,0,.06);
    transition: transform .15s ease, box-shadow .2s ease, border-color .15s ease;
}

.trk-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(0,0,0,.12);
    border-color: #7c3aed; /* morado suave */
}

.trk-body { min-width: 0; }

.trk-title {
    font-weight: 600;
    font-size: 1rem;
    line-height: 1.15;
    color: var(--bs-emphasis-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.trk-sub {
    margin-top: .25rem;
    font-size: .9rem;
    color: var(--bs-secondary-color);
}

.trk-chevron {
    font-size: 26px;
    color: rgba(0,0,0,.35);
    transition: transform .15s ease, color .15s ease;
}
.trk-card:hover .trk-chevron { transform: translateX(2px); color: #7c3aed; }

.trk-ring {
    --trk-accent: #6366f1; /* default */
    --trk-track: rgba(0,0,0,.08);
    position: relative;
    width: 48px;
    height: 48px;
}

.trk-ring::before {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: conic-gradient(var(--trk-accent) var(--p), var(--trk-track) 0);
}

.trk-ring::after {
    content: "";
    position: absolute;
    inset: 6px;
    border-radius: 50%;
    background: var(--trk-bg);
    box-shadow: inset 0 0 0 1px var(--trk-border);
}

.trk-ring__label {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    font-size: .75rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
}

/* Estados de progreso */
.trk-low { --trk-accent: #9ca3af; } /* gris */
.trk-mid { --trk-accent: #facc15; } /* amarillo suave */
.trk-good { --trk-accent: #34d399; } /* verde suave */

/* ================================
   Dark Mode Correcciones
   ================================ */
.text-bg-dark .trk-card,
.bg-dark .trk-card {
    --trk-border: rgba(255,255,255,.12);
    --trk-bg: #1f1f2e; /* gris oscuro azulado */
    box-shadow: inset 0 1px 0 rgba(255,255,255,.05);
}

.text-bg-dark .trk-card:hover {
    border-color: #a78bfa; /* morado suave */
    box-shadow: 0 10px 28px rgba(0,0,0,.4);
}

.text-bg-dark .trk-chevron {
    color: rgba(255,255,255,.5);
}
.text-bg-dark .trk-card:hover .trk-chevron {
    color: #a78bfa;
}

.text-bg-dark .trk-ring {
    --trk-track: rgba(255,255,255,.12);
}
.text-bg-dark .trk-ring__label {
    color: #d1d5db; /* gris claro */
}

/* Acabado de los colores de progreso en dark */
.text-bg-dark .trk-low { --trk-accent: #6b7280; }   /* gris suave */
.text-bg-dark .trk-mid { --trk-accent: #fbbf24; }   /* amarillo cálido */
.text-bg-dark .trk-good { --trk-accent: #34d399; }  /* verde aqua */

/* ================================
   Filtros, Segments y Chips
   ================================ */

.filterbar { background: var(--bs-body-bg); }

.filter-group { display: flex; flex-direction: column; gap: .35rem; }
.filter-label {
    font-size: .72rem;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--bs-secondary-color);
}

.seg {
    display: inline-flex;
    background: rgba(124,58,237,.1); /* morado suave */
    padding: 4px;
    border-radius: 999px;
    gap: 4px;
}

.seg-btn {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .35rem .6rem;
    font-size: .85rem;
    border-radius: 999px;
    border: 1px solid transparent;
    color: var(--bs-emphasis-color);
    background: transparent;
    cursor: pointer;
    transition: all .15s ease;
}
.seg-btn:hover { background: rgba(124,58,237,.15); }
.btn-check:checked + .seg-btn {
    background: #7c3aed;
    color: #fff;
    border-color: #7c3aed;
    box-shadow: 0 2px 6px rgba(124,58,237,.35);
}

/* Chips */
.chip {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .35rem .65rem;
    font-size: .85rem;
    border-radius: 999px;
    border: 1px solid rgba(0,0,0,.08);
    background: rgba(0,0,0,.03);
    color: var(--bs-emphasis-color);
    cursor: pointer;
    transition: all .15s ease;
}
.chip:hover { background: rgba(0,0,0,.05); }
.btn-check:checked + .chip {
    border-color: #34d399;
    background: rgba(52,211,153,.15);
    box-shadow: inset 0 0 0 1px rgba(52,211,153,.25);
}

.dot { width:.55rem; height:.55rem; border-radius:50%; display:inline-block; }
.dot-gray { background:#9ca3af; }
.dot-amber { background:#fbbf24; }

#f-counter { color: var(--bs-secondary-color); }

/* Dark Mode Filters */
.text-bg-dark .filterbar { background: #1f1f2e; }
.text-bg-dark .seg { background: rgba(124,58,237,.2); }
.text-bg-dark .seg-btn { color: #e0e0e0; }
.text-bg-dark .seg-btn:hover { background: rgba(124,58,237,.3); }
.text-bg-dark .btn-check:checked + .seg-btn { background: #a78bfa; color: #fff; border-color: #a78bfa; box-shadow: 0 2px 6px rgba(167,139,250,.4); }

.text-bg-dark .chip { border-color: rgba(255,255,255,.12); background: rgba(255,255,255,.03); color: #e0e0e0; }
.text-bg-dark .chip:hover { background: rgba(255,255,255,.06); }
.text-bg-dark .btn-check:checked + .chip { border-color: #34d399; background: rgba(52,211,153,.2); box-shadow: inset 0 0 0 1px rgba(52,211,153,.25); }

.text-bg-dark .dot-gray { background: #6b7280; }
.text-bg-dark .dot-amber { background: #fbbf24; }
.text-bg-dark .filter-label { color: #b0b0b0; }
.text-bg-dark #f-counter { color: #aaa; }

</style>


<script>
    (() => {
        const table = document.getElementById('videos-table');
        const tbody = table.querySelector('tbody');

        const inputs = {
            sortVistos: document.querySelectorAll('input[name="sort_vistos"]'),
            sortRel: document.querySelectorAll('input[name="sort_relevantes"]'),
            noVistos: document.getElementById('f-nv'),
            relev: document.getElementById('f-rel'),
        };
        const counter = document.getElementById('f-counter');

        function currentOptions() {
            const sv = [...inputs.sortVistos].find(r => r.checked)?.value || '';
            const sr = [...inputs.sortRel].find(r => r.checked)?.value || '';
            const nv = inputs.noVistos.checked ? 1 : 0;
            const rl = inputs.relev.checked ? 1 : 0;
            return {
                sv,
                sr,
                nv,
                rl
            };
        }

        function applyRowClasses(tr) {
            const visto = tr.dataset.visto === '1';
            const rel = tr.dataset.relevante === '1';

            tr.classList.remove('opacity-50', 'table-warning', 'table-light');

            if (rel) tr.classList.add('table-warning');
            else if (!visto) tr.classList.add('table-light');
            if (visto) tr.classList.add('opacity-50');

            // título bold si no visto
            const a = tr.querySelector('.cell-titulo a');
            if (a) a.classList.toggle('fw-bold', !visto);
        }

        function filterRows() {
            const {
                nv,
                rl
            } = currentOptions();
            let shown = 0,
                total = 0;

            tbody.querySelectorAll('tr').forEach(tr => {
                total++;
                const visto = tr.dataset.visto === '1';
                const rel = tr.dataset.relevante === '1';

                let visible = true;
                if (nv && visto) visible = false;
                if (rl && !rel) visible = false;

                tr.style.display = visible ? '' : 'none';
                if (visible) shown++;
            });

            if (counter) {
                const baseTotal = <?= (int)$stats['total'] ?>;
                counter.textContent = `Mostrando ${shown} de ${baseTotal}`;
            }
        }

        function sortRows() {
            const {
                sv,
                sr
            } = currentOptions();
            const rows = [...tbody.querySelectorAll('tr')].filter(tr => tr.style.display !== 'none');

            rows.sort((a, b) => {
                const av = Number(a.dataset.visto),
                    bv = Number(b.dataset.visto); // 0 / 1
                const ar = Number(a.dataset.relevante),
                    br = Number(b.dataset.relevante); // 0 / 1
                const ap = Number(a.dataset.posicion),
                    bp = Number(b.dataset.posicion); // int

                // 1) Relevantes primero (desc)
                if (sr === 'primero' && ar !== br) return br - ar;

                // 2) Orden por vistos
                if (sv === 'no_vistos_primero' && av !== bv) return av - bv; // 0 < 1
                if (sv === 'vistos_primero' && av !== bv) return bv - av; // 1 < 0

                // 3) Desempate por posición asc
                return ap - bp;
            });

            // Reinsertar en orden
            rows.forEach(tr => tbody.appendChild(tr));
        }

        function updateView() {
            filterRows();
            sortRows();
        }

        // Eventos en filtros
        [...inputs.sortVistos, ...inputs.sortRel].forEach(r => r.addEventListener('change', updateView));
        inputs.noVistos.addEventListener('change', updateView);
        inputs.relev.addEventListener('change', updateView);

        // Inicial
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

        // Toggle Visto
        tbody.addEventListener('click', async (e) => {
            const btn = e.target.closest('.js-toggle-visto');
            if (!btn) return;
            const tr = btn.closest('tr');
            const id = btn.dataset.id;
            const url = '<?= site_url('youtube/toggle-visto') ?>/' + id;

            const res = await postToggle(url);
            if (!res.ok) return;

            // Optimista: invertimos estado
            const nuevo = tr.dataset.visto === '1' ? '0' : '1';
            tr.dataset.visto = nuevo;

            // Botón
            btn.classList.toggle('btn-success', nuevo === '1');
            btn.classList.toggle('btn-outline-secondary', nuevo === '0');
            btn.textContent = (nuevo === '1') ? '✓ Visto' : 'Marcar visto';

            // Clases y reordenado/filtro
            applyRowClasses(tr);
            updateView();
        });

        // Toggle Relevante
        tbody.addEventListener('click', async (e) => {
            const btn = e.target.closest('.js-toggle-relevante');
            if (!btn) return;
            const tr = btn.closest('tr');
            const id = btn.dataset.id;
            const url = '<?= site_url('youtube/toggle-relevante') ?>/' + id;

            const res = await postToggle(url);
            if (!res.ok) return;

            // Optimista: invertimos estado
            const nuevo = tr.dataset.relevante === '1' ? '0' : '1';
            tr.dataset.relevante = nuevo;

            // Botón
            btn.classList.toggle('btn-warning', nuevo === '1');
            btn.classList.toggle('btn-outline-secondary', nuevo === '0');
            btn.textContent = (nuevo === '1') ? '★ Relevante' : 'Marcar relevante';

            // Clases y reordenado/filtro
            applyRowClasses(tr);
            updateView();
        });

        // Toggle Largo
        tbody.addEventListener('click', async (e) => {
            const btn = e.target.closest('.js-toggle-largo');
            if (!btn) return;
            const tr = btn.closest('tr');
            const id = btn.dataset.id;
            const url = '<?= site_url('youtube/toggle-largo') ?>/' + id;

            const res = await postToggle(url);
            if (!res.ok) return;

            // Optimista: invertimos estado
            const nuevo = tr.dataset.largo === '1' ? '0' : '1';
            tr.dataset.largo = nuevo;

            // Botón
            btn.classList.toggle('btn-info', nuevo === '1');
            btn.classList.toggle('btn-outline-secondary', nuevo === '0');
            btn.textContent = (nuevo === '1') ? '🎬 Largo' : 'Largo';
        });

    })();
</script>

<script>
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