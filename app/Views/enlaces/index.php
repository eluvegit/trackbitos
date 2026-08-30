<!-- app/Views/enlaces/index.php -->
<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>
<div class="container py-3">

    <!-- Cabecera -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <?php $total = is_countable($enlaces) ? count($enlaces) : 0; ?>
        <h5 class="mb-0 d-flex align-items-center gap-2">
            Enlaces
            <span class="badge bg-light text-dark border" id="enlCount">
                <?php if (!empty($hayMas)): ?>
                    <?= (int) $topeResultados ?>+ resultados
                <?php elseif (!empty($modoReciente)): ?>
                    <?= $total ?> recientes
                <?php else: ?>
                    <?= $total ?> <?= $total === 1 ? 'resultado' : 'resultados' ?>
                <?php endif; ?>
            </span>
        </h5>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-primary" href="<?= site_url('enlaces/crear') ?>">
                <i class="bi bi-plus-lg"></i> Agregar
            </a>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-three-dots"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= site_url('enlaces/categorias') ?>"><i class="bi bi-tags me-2"></i>Categorías</a></li>
                    <li><a class="dropdown-item" href="<?= site_url('enlaces/etiquetas') ?>"><i class="bi bi-bookmark me-2"></i>Etiquetas</a></li>
                    <li><a class="dropdown-item" href="<?= site_url('enlaces/importar') ?>"><i class="bi bi-upload me-2"></i>Importar HTML</a></li>
                    <?php if ($pendientesRevision > 0): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex justify-content-between align-items-center" href="<?= site_url('enlaces/revision') ?>">
                                Revisar pendientes
                                <span class="badge bg-warning text-dark"><?= $pendientesRevision ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Buscador + filtros -->
    <form method="get" action="<?= site_url('enlaces') ?>" id="filtrosForm">
        <div class="enl-searchbar mb-2">
            <div class="enl-search-input">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="<?= esc($q) ?>" class="form-control" placeholder="Buscar por título, URL, nota, categoría o etiqueta…">
            </div>
            <button type="button" class="btn btn-outline-secondary enl-btn-filtros" id="btnToggleFiltros">
                <i class="bi bi-sliders"></i> Filtros
                <?php if ($panelActiveCount > 0): ?>
                    <span class="badge rounded-pill bg-primary"><?= $panelActiveCount ?></span>
                <?php endif; ?>
            </button>
            <button type="submit" class="btn btn-primary">Buscar</button>
        </div>

        <!-- Chips de filtros activos (se re-renderiza en cada búsqueda en vivo) -->
        <div class="enl-active-chips mb-2" id="enlChips"><?= trim($this->include('enlaces/_chips')) ?></div>

        <!-- Panel de filtros (colapsado salvo que ya haya alguno activo) -->
        <div class="enl-filtros-panel <?= $panelActiveCount ? '' : 'd-none' ?>" id="panelFiltros">
            <div class="row g-3">
                <div class="col-12">
                    <label class="enl-label">Categorías</label>
                    <?php // Todas visibles a la vez, en una fila que hace scroll: son pocas
                          // y son la forma principal de acotar. Mismo peso visual todas —
                          // el número al lado es la única pista de cuánto trae cada una. ?>
                    <div class="enl-cat-pills" id="catPills">
                        <?php foreach ($categorias as $c): ?>
                            <button type="button"
                                class="enl-cat-pill<?= in_array((int) $c['id'], $cats, true) ? ' active' : '' ?>"
                                data-cat="<?= (int) $c['id'] ?>">
                                <?= esc($c['nombre']) ?>
                                <span class="enl-cat-pill-count"><?= (int) ($catCount[$c['id']] ?? 0) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-12">
                    <label class="enl-label">Etiquetas</label>
                    <div class="chip-picker" id="pickerEtiquetas"></div>
                </div>
                <div class="col-12 col-sm-6">
                    <label class="enl-label">Estado</label>
                    <div class="enl-segmented" id="segEstado">
                        <button type="button" data-value="">Todos</button>
                        <button type="button" data-value="0">No vistos</button>
                        <button type="button" data-value="1">Vistos</button>
                    </div>
                    <input type="hidden" name="visto" id="inputVisto" value="<?= esc($visto) ?>">
                </div>
                <div class="col-12 col-sm-6" id="matchWrap" style="display:none;">
                    <label class="enl-label">Coincidencia</label>
                    <div class="enl-segmented" id="segMatch">
                        <button type="button" data-value="any">Cualquiera</button>
                        <button type="button" data-value="all">Todas</button>
                    </div>
                    <input type="hidden" name="match" id="inputMatch" value="<?= esc($match) ?>">
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary btn-sm">Aplicar filtros</button>
                <a href="<?= site_url('enlaces') ?>" class="btn btn-light btn-sm" data-enl-nav>Limpiar</a>
            </div>
        </div>
    </form>

    <!-- Lista (se re-renderiza en cada búsqueda en vivo) -->
    <div id="enlResultados"><?= $this->include('enlaces/_resultados') ?></div>
</div>

<style>
    /* ================= Buscador y filtros ================= */
    .enl-searchbar {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .enl-search-input {
        flex: 1 1 220px;
        position: relative;
        display: flex;
        align-items: center;
    }
    .enl-search-input i {
        position: absolute;
        left: 12px;
        color: var(--bs-secondary-color);
        pointer-events: none;
    }
    .enl-search-input .form-control {
        padding-left: 34px;
    }
    .enl-btn-filtros {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        white-space: nowrap;
    }

    .enl-active-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
    }
    .enl-active-chips:empty { display: none; }

    /* Mientras llega una búsqueda en vivo: se atenúa la lista, sin saltos */
    #enlResultados.enl-loading { opacity: .55; transition: opacity .12s ease; pointer-events: none; }
    .enl-active-chip {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .25rem .6rem;
        border-radius: 999px;
        background: rgba(124,58,237,.12);
        color: var(--bs-emphasis-color);
        border: 1px solid rgba(124,58,237,.3);
        font-size: .8rem;
        text-decoration: none;
    }
    .enl-active-chip:hover { background: rgba(124,58,237,.2); }
    .enl-active-chip-clear {
        background: transparent;
        border-color: var(--bs-border-color);
        color: var(--bs-secondary-color);
    }

    /* Aviso "últimos añadidos" cuando no hay búsqueda */
    .enl-hint {
        font-size: .8rem;
        color: var(--bs-secondary-color);
        display: flex;
        align-items: center;
        gap: .4rem;
        margin-bottom: 8px;
    }

    /* Aviso "hay más de N, afina" con atajos de etiqueta */
    .enl-narrow {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        font-size: .82rem;
        padding: 8px 12px;
        border-radius: 12px;
        border: 1px solid var(--bs-warning-border-subtle, rgba(255,193,7,.4));
        background: var(--bs-warning-bg-subtle, rgba(255,193,7,.12));
        color: var(--bs-emphasis-color);
        margin-bottom: 8px;
    }
    .enl-narrow-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .2rem .55rem;
        border-radius: 999px;
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        color: var(--bs-emphasis-color);
        font-size: .78rem;
        text-decoration: none;
    }
    .enl-narrow-chip:hover { background: var(--bs-tertiary-bg); }
    .enl-narrow-chip span { color: var(--bs-secondary-color); font-size: .72rem; }

    /* "Coincide en …" bajo la meta de cada resultado */
    .enl-item-match {
        font-size: .72rem;
        color: var(--bs-secondary-color);
        margin-top: 3px;
    }
    .enl-item-match i { opacity: .7; }

    .enl-filtros-panel {
        border: 1px solid var(--bs-border-color);
        border-radius: 14px;
        background: var(--bs-tertiary-bg);
        padding: 14px;
        margin-bottom: 8px;
    }
    .enl-label {
        display: block;
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: var(--bs-secondary-color);
        font-weight: 700;
        margin-bottom: 6px;
    }

    .enl-segmented {
        display: inline-flex;
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 999px;
        padding: 3px;
        gap: 3px;
    }
    .enl-segmented button {
        border: none;
        background: transparent;
        color: var(--bs-emphasis-color);
        font-size: .8rem;
        padding: .35rem .75rem;
        border-radius: 999px;
        cursor: pointer;
    }
    .enl-segmented button.active {
        background: #7c3aed;
        color: #fff;
    }

    /* ================= Chip-picker (categorías / etiquetas) ================= */
    .chip-picker {
        border: 1px solid var(--bs-border-color);
        border-radius: 10px;
        background: var(--bs-body-bg);
        padding: 8px;
    }
    .cp-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 6px;
    }
    .cp-chips:empty { display: none; }
    .cp-chip {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .25rem .5rem;
        border-radius: 999px;
        background: rgba(124,58,237,.15);
        color: var(--bs-emphasis-color);
        font-size: .8rem;
    }
    .cp-chip button {
        border: none;
        background: transparent;
        color: inherit;
        line-height: 1;
        font-size: 1rem;
        padding: 0;
        cursor: pointer;
    }
    .cp-input-wrap { position: relative; }
    .cp-input { font-size: .85rem; }
    .cp-dropdown {
        position: absolute;
        z-index: 20;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        max-height: 220px;
        overflow-y: auto;
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 10px;
        box-shadow: 0 8px 20px rgba(0,0,0,.15);
        padding: 4px;
    }
    .cp-option {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        border: none;
        background: transparent;
        text-align: left;
        padding: .4rem .5rem;
        border-radius: 8px;
        font-size: .85rem;
        color: var(--bs-emphasis-color);
        cursor: pointer;
    }
    .cp-option:hover { background: var(--bs-tertiary-bg); }
    .cp-option-count {
        font-size: .72rem;
        color: var(--bs-secondary-color);
    }
    .cp-empty {
        padding: .5rem;
        font-size: .82rem;
        color: var(--bs-secondary-color);
    }

    /* ================= Categorías: pills, en varias líneas si no caben ================= */
    .enl-cat-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .enl-cat-pill {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        white-space: nowrap;
        padding: .3rem .7rem;
        border-radius: 999px;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-body-bg);
        color: var(--bs-emphasis-color);
        font-size: .8rem;
        line-height: 1;
        cursor: pointer;
        transition: background-color .12s ease, border-color .12s ease;
    }
    .enl-cat-pill:hover { background: var(--bs-tertiary-bg); }
    .enl-cat-pill.active {
        background: #7c3aed;
        border-color: #7c3aed;
        color: #fff;
    }
    .enl-cat-pill-count {
        font-size: .68rem;
        opacity: .6;
        font-variant-numeric: tabular-nums;
    }
    .enl-cat-pill.active .enl-cat-pill-count { opacity: .85; }

    /* ================= Lista de resultados ================= */
    .enl-list { display: flex; flex-direction: column; gap: 8px; }

    .enl-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 10px;
        border-radius: 12px;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-body-bg);
        transition: background-color .15s ease;
    }
    .enl-item:hover { background: var(--bs-tertiary-bg); }
    .enl-item.is-visto { opacity: .55; }
    .enl-item.is-visto:hover { opacity: .85; }

    .enl-item-favicon {
        flex: 0 0 auto;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: var(--bs-tertiary-bg);
        display: grid;
        place-items: center;
        overflow: hidden;
        margin-top: 2px;
        text-decoration: none;
    }
    .enl-item-favicon:hover { background: var(--bs-border-color); }
    .enl-item-favicon img { width: 18px; height: 18px; }
    .enl-item-favicon i { color: var(--bs-secondary-color); font-size: 1rem; }

    .enl-item-body { flex: 1 1 auto; min-width: 0; }

    .enl-item-title-row { display: flex; align-items: baseline; gap: 6px; }
    .enl-item-title {
        font-weight: 600;
        font-size: .95rem;
        color: var(--bs-emphasis-color);
        text-decoration: none;
        overflow-wrap: anywhere;
    }
    .enl-item.is-visto .enl-item-title { font-weight: 400; }
    .enl-item-title:hover { text-decoration: underline; }

    .enl-item-meta { font-size: .78rem; color: var(--bs-secondary-color); margin-top: 2px; }
    .enl-item-domain { font-weight: 600; }
    .enl-item-relevancia { color: #f59e0b; font-weight: 600; }

    .enl-item-extra { font-size: .8rem; color: var(--bs-secondary-color); margin-top: 4px; }

    .enl-item-badges { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 6px; }
    .enl-badge-cat, .enl-badge-tag {
        display: inline-block;
        padding: .15rem .5rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 600;
    }
    .enl-badge-tag { background: var(--bs-tertiary-bg); color: var(--bs-secondary-color); }

    .enl-item-actions { flex: 0 0 auto; display: flex; align-items: center; gap: 2px; margin-top: 2px; }
    .enl-icon-btn {
        width: 34px;
        height: 34px;
        display: grid;
        place-items: center;
        border-radius: 50%;
        border: none;
        background: transparent;
        color: var(--bs-secondary-color);
        text-decoration: none;
        cursor: pointer;
    }
    .enl-icon-btn:hover { background: var(--bs-body-bg); color: var(--bs-emphasis-color); }
    .enl-icon-btn-danger:hover { color: #dc3545; }

    @media (max-width: 575.98px) {
        .enl-item { padding: 8px; gap: 8px; }
    }
</style>

<script>
(() => {
    // ============= ChipPicker: selector con buscador + chips =============
    class ChipPicker {
        constructor(root, options, selectedIds, fieldName, onChange) {
            this.root = root;
            this.options = options;
            this.selected = new Set(selectedIds.map(String));
            // Guarda el nombre/contador con el que se seleccionó cada chip, para
            // que no desaparezca visualmente si luego se refrescan las opciones
            // (p. ej. al cambiar de categoría) y ese id deja de estar en la lista.
            this.selectedMeta = new Map();
            options.forEach(o => {
                if (this.selected.has(String(o.id))) this.selectedMeta.set(String(o.id), o);
            });
            this.fieldName = fieldName;
            this.onChange = onChange || (() => {});
            this.build();
        }

        // Sustituye las opciones disponibles (p. ej. tras un refresco en vivo)
        // sin tocar la selección actual.
        setOptions(newOptions) {
            this.options = newOptions;
            newOptions.forEach(o => {
                if (this.selected.has(String(o.id))) this.selectedMeta.set(String(o.id), o);
            });
            this.renderChips();
            if (!this.dropdownEl.hidden) this.renderDropdown();
        }

        build() {
            this.root.innerHTML = `
                <div class="cp-chips"></div>
                <div class="cp-input-wrap">
                    <input type="text" class="form-control form-control-sm cp-input" placeholder="Buscar…" autocomplete="off">
                    <div class="cp-dropdown" hidden></div>
                </div>
            `;
            this.chipsEl = this.root.querySelector('.cp-chips');
            this.inputEl = this.root.querySelector('.cp-input');
            this.dropdownEl = this.root.querySelector('.cp-dropdown');

            this.inputEl.addEventListener('input', () => this.renderDropdown());
            this.inputEl.addEventListener('focus', () => this.renderDropdown());
            document.addEventListener('click', (e) => {
                if (!this.root.contains(e.target)) this.dropdownEl.hidden = true;
            });

            this.renderChips();
        }

        renderChips() {
            this.chipsEl.innerHTML = '';
            this.selected.forEach(id => {
                const opt = this.selectedMeta.get(id) || this.options.find(o => String(o.id) === id);
                if (!opt) return;
                const chip = document.createElement('span');
                chip.className = 'cp-chip';
                const label = document.createElement('span');
                label.textContent = opt.nombre;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.innerHTML = '&times;';
                btn.setAttribute('aria-label', 'Quitar ' + opt.nombre);
                btn.addEventListener('click', () => {
                    this.selected.delete(id);
                    this.renderChips();
                    this.onChange();
                });
                chip.append(label, btn);
                this.chipsEl.appendChild(chip);
            });
        }

        renderDropdown() {
            const q = this.inputEl.value.trim().toLowerCase();
            const matches = this.options
                .filter(o => !this.selected.has(String(o.id)))
                .filter(o => !q || o.nombre.toLowerCase().includes(q))
                .slice(0, 30);

            this.dropdownEl.innerHTML = '';
            if (!matches.length) {
                this.dropdownEl.innerHTML = '<div class="cp-empty">Sin resultados</div>';
            } else {
                matches.forEach(o => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'cp-option';
                    item.innerHTML = `<span>${o.nombre}</span><span class="cp-option-count">${o.count}</span>`;
                    item.addEventListener('click', () => {
                        this.selected.add(String(o.id));
                        this.selectedMeta.set(String(o.id), o);
                        this.inputEl.value = '';
                        this.renderChips();
                        this.dropdownEl.hidden = true;
                        this.onChange();
                    });
                    this.dropdownEl.appendChild(item);
                });
            }
            this.dropdownEl.hidden = false;
        }

        injectHiddenInputs(form) {
            form.querySelectorAll(`input[type="hidden"][data-picker="${this.fieldName}"]`).forEach(el => el.remove());
            this.selected.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = this.fieldName;
                input.value = id;
                input.dataset.picker = this.fieldName;
                form.appendChild(input);
            });
        }
    }

    const tagOptions = <?= json_encode(array_map(fn($t) => [
        'id' => (int) $t['etiqueta_id'],
        'nombre' => $t['nombre'],
        'count' => (int) $t['total'],
    ], $tagsDisp), JSON_UNESCAPED_UNICODE) ?>;
    const tagSelected = <?= json_encode($tagIdsSel) ?>;

    // Categorías: pills siempre visibles (no chip-picker). Selección = un Set
    // de ids como string, con la misma interfaz `.size`/`.has` que usa el resto.
    const catSel = new Set(<?= json_encode(array_map('strval', $cats)) ?>);
    const catPillsEl = document.getElementById('catPills');
    function renderCatPills() {
        catPillsEl.querySelectorAll('.enl-cat-pill').forEach(btn => {
            btn.classList.toggle('active', catSel.has(btn.dataset.cat));
        });
    }

    function updateMatchVisibility() {
        const total = catSel.size + tagPicker.selected.size;
        document.getElementById('matchWrap').style.display = total >= 2 ? '' : 'none';
    }

    // Cualquier cambio de filtro (categoría, etiqueta) dispara una búsqueda en vivo.
    function onFilterChange() {
        updateMatchVisibility();
        liveSearch(true);
    }

    const tagPicker = new ChipPicker(document.getElementById('pickerEtiquetas'), tagOptions, tagSelected, 'tag_ids[]', onFilterChange);

    catPillsEl.addEventListener('click', (ev) => {
        const btn = ev.target.closest('.enl-cat-pill');
        if (!btn) return;
        const id = btn.dataset.cat;
        catSel.has(id) ? catSel.delete(id) : catSel.add(id);
        renderCatPills();
        onFilterChange();
    });

    updateMatchVisibility();

    const form = document.getElementById('filtrosForm');
    const inputQ = form.querySelector('input[name="q"]');
    const inputVisto = document.getElementById('inputVisto');
    const inputMatch = document.getElementById('inputMatch');

    // ============= Panel de filtros: mostrar/ocultar =============
    const btnToggleFiltros = document.getElementById('btnToggleFiltros');
    const panelFiltros = document.getElementById('panelFiltros');
    btnToggleFiltros.addEventListener('click', () => {
        panelFiltros.classList.toggle('d-none');
    });

    // ============= Segmentados: Estado / Coincidencia =============
    function initSegmented(containerId, hiddenInputId) {
        const container = document.getElementById(containerId);
        const hidden = document.getElementById(hiddenInputId);
        container.querySelectorAll('button').forEach(btn => {
            if (btn.dataset.value === hidden.value) btn.classList.add('active');
            btn.addEventListener('click', () => {
                container.querySelectorAll('button').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                hidden.value = btn.dataset.value;
                liveSearch(true);
            });
        });
    }
    function syncSegmented() {
        [['segEstado', inputVisto], ['segMatch', inputMatch]].forEach(([cid, hidden]) => {
            document.getElementById(cid).querySelectorAll('button').forEach(b => {
                b.classList.toggle('active', b.dataset.value === hidden.value);
            });
        });
    }
    initSegmented('segEstado', 'inputVisto');
    initSegmented('segMatch', 'inputMatch');

    // ============= Búsqueda en vivo =============
    const RES_URL  = '<?= site_url('enlaces/buscar-resultados') ?>';
    const BASE_URL = '<?= site_url('enlaces') ?>';
    const elResultados = document.getElementById('enlResultados');
    const elChips = document.getElementById('enlChips');
    const elCount = document.getElementById('enlCount');
    let liveSeq = 0;
    let liveTimer = null;

    // Reúne el estado actual de buscador + filtros en un querystring.
    function currentParams() {
        const p = new URLSearchParams();
        const q = inputQ.value.trim();
        if (q) p.set('q', q);
        if (inputVisto.value === '0' || inputVisto.value === '1') p.set('visto', inputVisto.value);
        catSel.forEach(id => p.append('cats[]', id));
        tagPicker.selected.forEach(id => p.append('tag_ids[]', id));
        if ((catSel.size + tagPicker.selected.size) >= 2 && inputMatch.value === 'all') {
            p.set('match', 'all');
        }
        return p;
    }

    function pintarContador(data) {
        if (!elCount) return;
        elCount.textContent = data.hayMas
            ? (data.topeResultados + '+ resultados')
            : data.modoReciente
                ? (data.total + ' recientes')
                : (data.total + (data.total === 1 ? ' resultado' : ' resultados'));
    }

    function pintarBadgeFiltros(n) {
        let badge = btnToggleFiltros.querySelector('.badge');
        if (n > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'badge rounded-pill bg-primary';
                btnToggleFiltros.appendChild(badge);
            }
            badge.textContent = n;
        } else if (badge) {
            badge.remove();
        }
    }

    async function liveSearch(push) {
        const seq = ++liveSeq;
        const params = currentParams();
        const qs = params.toString();

        // La URL sigue al estado: recargar o Atrás reproducen la misma vista.
        const url = qs ? (BASE_URL + '?' + qs) : BASE_URL;
        if (push) history.pushState(null, '', url); else history.replaceState(null, '', url);
        document.title = params.get('q') ? ('Enlaces · ' + params.get('q')) : 'Enlaces';

        elResultados.classList.add('enl-loading');
        try {
            const res = await fetch(RES_URL + (qs ? ('?' + qs) : ''), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok || seq !== liveSeq) return;
            const data = await res.json();
            if (seq !== liveSeq) return;

            elResultados.innerHTML = data.resultados;
            elChips.innerHTML = (data.chips || '').trim();
            pintarContador(data);
            pintarBadgeFiltros(data.panelActiveCount);
            // El picker de etiquetas refleja las presentes en estos resultados.
            tagPicker.setOptions(data.tagsDisp || []);
        } catch (err) {
            // fallo puntual de red: se reintenta en el siguiente cambio
        } finally {
            elResultados.classList.remove('enl-loading');
        }
    }

    // Aplica una URL de chip/atajo al estado de los controles y busca.
    function applyUrl(href, push) {
        const p = new URL(href, location.origin).searchParams;
        inputQ.value = p.get('q') || '';
        inputVisto.value = (p.get('visto') === '0' || p.get('visto') === '1') ? p.get('visto') : '';
        inputMatch.value = p.get('match') === 'all' ? 'all' : 'any';
        const cats = p.getAll('cats[]').concat(p.getAll('cats'));
        const tags = p.getAll('tag_ids[]').concat(p.getAll('tag_ids'));
        catSel.clear();
        cats.map(String).filter(Boolean).forEach(id => catSel.add(id));
        tagPicker.selected = new Set(tags.map(String).filter(Boolean));
        renderCatPills();
        tagPicker.renderChips();
        syncSegmented();
        updateMatchVisibility();
        liveSearch(push);
    }

    // Enter en el buscador → búsqueda inmediata, sin recargar.
    form.addEventListener('submit', (ev) => {
        ev.preventDefault();
        clearTimeout(liveTimer);
        liveSearch(true);
    });

    // Al teclear: debounce corto.
    inputQ.addEventListener('input', () => {
        clearTimeout(liveTimer);
        liveTimer = setTimeout(() => liveSearch(false), 300);
    });

    // Chips de filtro activo, "Limpiar todo", atajos de "afina": navegan sin recargar.
    document.addEventListener('click', (ev) => {
        const a = ev.target.closest('a[data-enl-nav]');
        if (!a || ev.metaKey || ev.ctrlKey) return;
        ev.preventDefault();
        applyUrl(a.getAttribute('href'), true);
    });

    // Botón Atrás/Adelante del navegador.
    window.addEventListener('popstate', () => applyUrl(location.href, false));

    // ============= Toggle visto (delegado: la lista se re-renderiza) =============
    elResultados.addEventListener('click', async (ev) => {
        const b = ev.target.closest('.btn-toggle-visto');
        if (!b) return;
        b.disabled = true;
        try {
            const res = await fetch('<?= site_url('enlaces/toggle-visto') ?>/' + b.getAttribute('data-id'), { method: 'POST' });
            if (res.ok) liveSearch(false);
        } catch (err) {
            b.disabled = false;
        }
    });
})();
</script>
<?php $this->endSection(); ?>
