<?php $this->extend('comidas/layout');
$this->section('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Recetas</h1>
  <a class="btn btn-primary" href="<?= site_url('comidas/recetas/create') ?>">Nueva</a>
</div>

<form class="row g-2 mb-3" id="buscador-recetas" action="<?= site_url('comidas/recetas') ?>" method="get">
  <div class="col">
    <input class="form-control" name="q" value="<?= esc($q ?? '') ?>" placeholder="Buscar por nombre..." autocomplete="off">
  </div>
  <div class="col-auto d-flex gap-2">
    <button class="btn btn-outline-secondary">Buscar</button>
    <button type="button"
      id="btnClear"
      class="btn btn-outline-danger"
      title="Limpiar búsqueda"
      aria-label="Limpiar búsqueda">
      CLR
    </button>
  </div>
</form>

<div class="position-relative">
  <div id="spinner-busqueda" class="position-absolute top-0 end-0 me-1 mt-1 d-none">
    <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
    <span class="visually-hidden">Cargando…</span>
  </div>
</div>

<table class="table table-striped align-middle">
  <thead>
    <tr>
      <th>Receta</th>
      <th class="text-end">Acciones</th>
    </tr>
  </thead>
  <tbody id="recetas-body">
    <?= view('comidas/recetas/_rows', ['rows' => $rows]) ?>
  </tbody>
</table>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
  (() => {
    const form = document.getElementById('buscador-recetas');
    const input = form ? form.querySelector('input[name="q"]') : null;
    const tbody = document.getElementById('recetas-body');
    const spinner = document.getElementById('spinner-busqueda');
    const baseUrl = form ? form.getAttribute('action') : null;
    const btnClear = document.getElementById('btnClear');

    if (!form || !input || !tbody || !baseUrl) return;

    function setLoading(on) {
      if (spinner) spinner.classList.toggle('d-none', !on);
    }

    function toggleClear() {
      if (!btnClear) return;
      const hasText = !!(input.value && input.value.trim());
      btnClear.disabled = !hasText;
    }

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      fetchRows(input.value);
    });

    let timer = null;
    let aborter = null;
    input.addEventListener('input', () => {
      toggleClear();
      clearTimeout(timer);
      timer = setTimeout(() => fetchRows(input.value), 250);
    });

    if (btnClear) {
      toggleClear();
      btnClear.addEventListener('click', () => {
        if (!input.value) return;
        input.value = '';
        toggleClear();
        input.focus();
        fetchRows('');
      });
    }

    input.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && input.value) {
        input.value = '';
        toggleClear();
        fetchRows('');
      }
    });

    async function fetchRows(q) {
      try {
        setLoading(true);

        if (aborter) aborter.abort();
        aborter = new AbortController();

        const url = new URL(baseUrl, window.location.origin);
        if (q && q.trim() !== '') url.searchParams.set('q', q.trim());
        url.searchParams.set('partial', '1');

        const res = await fetch(url.toString(), {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          signal: aborter.signal,
        });

        if (!res.ok) return;
        tbody.innerHTML = await res.text();
      } catch (err) {
        if (err.name !== 'AbortError') console.error('[Recetas] fetch error:', err);
      } finally {
        setLoading(false);
      }
    }

    toggleClear();
  })();
</script>
<?php $this->endSection(); ?>
