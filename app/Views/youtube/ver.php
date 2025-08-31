<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h2><?= esc($lista['nombre']) ?></h2>

<div class="mb-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('youtube/' . $lista['slug'] . '/importar-playlist') ?>">Importar Playlist</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('youtube/' . $lista['slug'] . '/importar-texto') ?>">Importar texto</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('youtube/' . $lista['slug'] . '/importar-html') ?>">Importar HTML</a>
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
            <th>Video</th>
            <th>Visto</th>
            <th>Relevante</th>
            <th>Acciones</th>
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
                data-relevante="<?= $isRel ? '1' : '0' ?>">
                <td class="cell-posicion"><?= (int)$v['posicion'] ?></td>
                <td class="cell-titulo">
                    <a href="<?= esc($v['url']) ?>" target="_blank" rel="noopener" class="text-decoration-none <?= $titleClass ?>">
                        <?= esc($v['titulo'] ?: 'Abrir') ?>
                    </a>
                </td>
                <td class="cell-visto">
                    <button class="btn btn-sm <?= $isVisto ? 'btn-success' : 'btn-outline-secondary' ?> js-toggle-visto" data-id="<?= (int)$v['id'] ?>">
                        <?= $isVisto ? '✓ Visto' : 'Marcar visto' ?>
                    </button>
                </td>
                <td class="cell-relevante">
                    <button class="btn btn-sm <?= $isRel ? 'btn-warning' : 'btn-outline-secondary' ?> js-toggle-relevante" data-id="<?= (int)$v['id'] ?>">
                        <?= $isRel ? '★ Relevante' : 'Marcar relevante' ?>
                    </button>
                </td>
                <td>
                    <!-- futuro: borrar/editar -->
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<style>
    /* Toque moderno */
    #filters .btn {
        border-radius: 999px;
    }

    #filters .btn-group .btn {
        white-space: nowrap;
    }

    tr.opacity-50 {
        transition: opacity .15s ease-in-out;
    }
</style>

<style>
    /* Contenedor */
    .filterbar {
        background: var(--bs-body-bg);
    }

    /* Grupo + etiqueta pequeña */
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: .35rem;
    }

    .filter-label {
        font-size: .72rem;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--bs-secondary-color);
    }

    /* Segmentos (píldoras) */
    .seg {
        display: inline-flex;
        background: rgba(99, 102, 241, .08);
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
        line-height: 1;
        border-radius: 999px;
        border: 1px solid transparent;
        color: var(--bs-emphasis-color);
        background: transparent;
        cursor: pointer;
        transition: all .15s ease;
    }

    .seg-btn:hover {
        background: rgba(99, 102, 241, .10);
    }

    .btn-check:checked+.seg-btn {
        background: #fff;
        border-color: rgba(99, 102, 241, .35);
        box-shadow: 0 3px 10px rgba(99, 102, 241, .15);
    }

    /* Chips de filtro */
    .chip {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .35rem .65rem;
        font-size: .85rem;
        line-height: 1;
        border-radius: 999px;
        border: 1px solid rgba(0, 0, 0, .08);
        background: rgba(0, 0, 0, .03);
        color: var(--bs-emphasis-color);
        cursor: pointer;
        transition: all .15s ease;
    }

    .chip:hover {
        background: rgba(0, 0, 0, .05);
    }

    .btn-check:checked+.chip {
        border-color: rgba(16, 185, 129, .35);
        background: rgba(16, 185, 129, .08);
        box-shadow: inset 0 0 0 1px rgba(16, 185, 129, .25);
    }

    /* Puntitos de color en chips */
    .dot {
        width: .55rem;
        height: .55rem;
        border-radius: 50%;
        display: inline-block;
    }

    .dot-gray {
        background: #9ca3af;
    }

    .dot-amber {
        background: #f59e0b;
    }

    /* Dark mode friendly */
    .text-bg-dark .seg {
        background: rgba(99, 102, 241, .18);
    }

    .text-bg-dark .seg-btn:hover {
        background: rgba(99, 102, 241, .22);
    }

    .text-bg-dark .btn-check:checked+.seg-btn {
        background: rgba(255, 255, 255, .06);
    }

    .text-bg-dark .chip {
        border-color: rgba(255, 255, 255, .12);
        background: rgba(255, 255, 255, .03);
    }

    .text-bg-dark .chip:hover {
        background: rgba(255, 255, 255, .06);
    }

    .text-bg-dark .btn-check:checked+.chip {
        border-color: rgba(16, 185, 129, .45);
        background: rgba(16, 185, 129, .15);
    }
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