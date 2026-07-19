<!-- app/Views/enlaces/index.php -->
<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>
<div class="container py-3">

    <!-- Cabecera -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <?php $total = is_countable($enlaces) ? count($enlaces) : 0; ?>
        <h5 class="mb-0 d-flex align-items-center gap-2">
            Enlaces
            <span class="badge bg-light text-dark border">
                <?= $total ?> <?= $total === 1 ? 'resultado' : 'resultados' ?>
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
                <input type="text" name="q" value="<?= esc($q) ?>" class="form-control" placeholder="Buscar por título, URL, notas…">
            </div>
            <button type="button" class="btn btn-outline-secondary enl-btn-filtros" id="btnToggleFiltros">
                <i class="bi bi-sliders"></i> Filtros
                <?php if ($panelActiveCount > 0): ?>
                    <span class="badge rounded-pill bg-primary"><?= $panelActiveCount ?></span>
                <?php endif; ?>
            </button>
            <button type="submit" class="btn btn-primary">Buscar</button>
        </div>

        <!-- Chips de filtros activos -->
        <?php if (!empty($chipsActivos)): ?>
            <div class="enl-active-chips mb-2">
                <?php foreach ($chipsActivos as $chip): ?>
                    <a href="<?= esc($chip['url'], 'attr') ?>" class="enl-active-chip">
                        <?= esc($chip['texto']) ?> <i class="bi bi-x"></i>
                    </a>
                <?php endforeach; ?>
                <a href="<?= site_url('enlaces') ?>" class="enl-active-chip enl-active-chip-clear">Limpiar todo</a>
            </div>
        <?php endif; ?>

        <!-- Panel de filtros (colapsado salvo que ya haya alguno activo) -->
        <div class="enl-filtros-panel <?= $panelActiveCount ? '' : 'd-none' ?>" id="panelFiltros">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="enl-label">Categorías</label>
                    <div class="chip-picker" id="pickerCategorias"></div>
                </div>
                <div class="col-12 col-md-6">
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
                <a href="<?= site_url('enlaces') ?>" class="btn btn-light btn-sm">Limpiar</a>
            </div>
        </div>
    </form>

    <!-- Lista -->
    <?php
    if (!function_exists('enl_color_from_string')) {
        function enl_color_from_string(string $str): array
        {
            $hue = crc32($str) % 360;
            return [
                "hsl({$hue}, 65%, 70%)",
                "hsla({$hue}, 65%, 55%, .16)",
            ];
        }
    }
    ?>
    <div class="enl-list">
        <?php foreach ($enlaces as $e): ?>
            <?php
            $isVisto = (bool) $e['visto'];
            $relevancia = (int) $e['relevancia'];
            $host = parse_url($e['url'], PHP_URL_HOST);
            $dominio = $host ? preg_replace('/^www\./', '', $host) : '';
            $tieneBadges = !empty($catsPorEnlace[$e['id']]) || !empty($tagsPorEnlace[$e['id']]);
            ?>
            <div class="enl-item <?= $isVisto ? 'is-visto' : '' ?>">
                <div class="enl-item-favicon">
                    <?php if ($dominio): ?>
                        <img src="https://www.google.com/s2/favicons?domain=<?= urlencode($dominio) ?>&sz=32"
                             alt="" loading="lazy" onerror="this.style.visibility='hidden'">
                    <?php else: ?>
                        <i class="bi bi-link-45deg"></i>
                    <?php endif; ?>
                </div>

                <div class="enl-item-body">
                    <div class="enl-item-title-row">
                        <a href="<?= site_url('enlaces/pagina/' . $e['id']) ?>" class="enl-item-title">
                            <?= esc($e['titulo']) ?>
                        </a>
                        <a href="<?= esc($e['url']) ?>" target="_blank" rel="noopener" class="enl-item-open" title="Abrir enlace externo">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    </div>

                    <div class="enl-item-meta">
                        <?php if ($dominio): ?><span class="enl-item-domain"><?= esc($dominio) ?></span> · <?php endif; ?>
                        <?= date('d/m/Y', strtotime($e['fecha'])) ?>
                        <?php if ($relevancia > 0): ?>
                            · <span class="enl-item-relevancia"><i class="bi bi-star-fill"></i> <?= $relevancia ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($e['extra'])): ?>
                        <div class="enl-item-extra"><?= esc(mb_strimwidth(strip_tags($e['extra']), 0, 160, '…')) ?></div>
                    <?php endif; ?>

                    <?php if ($tieneBadges): ?>
                        <div class="enl-item-badges">
                            <?php foreach (($catsPorEnlace[$e['id']] ?? []) as $c): if (!$c) continue;
                                [$colorTxt, $colorBg] = enl_color_from_string($c['nombre']);
                            ?>
                                <span class="enl-badge-cat" style="color: <?= $colorTxt ?>; background: <?= $colorBg ?>;">
                                    #<?= esc($c['nombre']) ?>
                                </span>
                            <?php endforeach; ?>
                            <?php foreach (($tagsPorEnlace[$e['id']] ?? []) as $t): if (!$t) continue; ?>
                                <span class="enl-badge-tag"><?= esc($t['nombre']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="enl-item-actions">
                    <button data-id="<?= $e['id'] ?>" class="enl-icon-btn btn-toggle-visto" title="Marcar visto/no visto">
                        <i class="bi <?= $isVisto ? 'bi-check-square-fill' : 'bi-square' ?>"></i>
                    </button>
                    <a class="enl-icon-btn" href="<?= site_url('enlaces/editar/' . $e['id']) ?>" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <a class="enl-icon-btn enl-icon-btn-danger" href="<?= site_url('enlaces/borrar/' . $e['id']) ?>"
                       onclick="return confirm('¿Eliminar enlace?');" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
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
    }
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

    .enl-item-open {
        flex: 0 0 auto;
        color: var(--bs-secondary-color);
        display: inline-flex;
        align-items: center;
        font-size: .8rem;
    }
    .enl-item-open:hover { color: var(--bs-emphasis-color); }

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
            this.fieldName = fieldName;
            this.onChange = onChange || (() => {});
            this.build();
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
                const opt = this.options.find(o => String(o.id) === id);
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

    const catOptions = <?= json_encode(array_map(fn($c) => [
        'id' => (int) $c['id'],
        'nombre' => $c['nombre'],
        'count' => $catCount[$c['id']] ?? 0,
    ], $categorias), JSON_UNESCAPED_UNICODE) ?>;
    const catSelected = <?= json_encode($cats) ?>;

    const tagOptions = <?= json_encode(array_map(fn($t) => [
        'id' => (int) $t['etiqueta_id'],
        'nombre' => $t['nombre'],
        'count' => (int) $t['total'],
    ], $tagsDisp), JSON_UNESCAPED_UNICODE) ?>;
    const tagSelected = <?= json_encode($tagIdsSel) ?>;

    function updateMatchVisibility() {
        const total = catPicker.selected.size + tagPicker.selected.size;
        document.getElementById('matchWrap').style.display = total >= 2 ? '' : 'none';
    }

    const catPicker = new ChipPicker(document.getElementById('pickerCategorias'), catOptions, catSelected, 'cats[]', updateMatchVisibility);
    const tagPicker = new ChipPicker(document.getElementById('pickerEtiquetas'), tagOptions, tagSelected, 'tag_ids[]', updateMatchVisibility);
    updateMatchVisibility();

    const form = document.getElementById('filtrosForm');
    form.addEventListener('submit', () => {
        catPicker.injectHiddenInputs(form);
        tagPicker.injectHiddenInputs(form);
    });

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
            });
        });
    }
    initSegmented('segEstado', 'inputVisto');
    initSegmented('segMatch', 'inputMatch');

    // ============= Toggle visto (fila de resultados) =============
    for (const b of document.querySelectorAll('.btn-toggle-visto')) {
        b.addEventListener('click', async () => {
            const id = b.getAttribute('data-id');
            const res = await fetch('<?= site_url('enlaces/toggle-visto') ?>/' + id, { method: 'POST' });
            if (res.ok) location.reload();
        });
    }
})();
</script>
<?php $this->endSection(); ?>
