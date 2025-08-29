<?php $this->extend('comidas/layout');
$this->section('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Alimentos</h1>
  <a class="btn btn-primary" href="<?= site_url('comidas/alimentos/create') ?>">Nuevo</a>
</div>

<form class="row g-2 mb-3" id="buscador-alimentos" action="<?= site_url('comidas/alimentos') ?>" method="get">
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
      <th>Nombre</th>
      <th class="text-end">kcal/100g</th>
      <th></th>
    </tr>
  </thead>
  <tbody id="alimentos-body">
    <?= view('comidas/alimentos/_rows', ['rows' => $rows]) ?>
  </tbody>
</table>

<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
  (() => {
    // --- NODOS ---
    const form = document.getElementById('buscador-alimentos');
    const input = form ? form.querySelector('input[name="q"]') : null;
    const tbody = document.getElementById('alimentos-body');
    const spinner = document.getElementById('spinner-busqueda');
    const baseUrl = form ? form.getAttribute('action') : null; // <?= site_url('comidas/alimentos') ?>
    const btnClear = document.getElementById('btn-clear-q'); // puede no existir

    // --- LOG HELPERS ---
    const log = (...a) => console.log('[Alimentos]', ...a);
    const warn = (...a) => console.warn('[Alimentos]', ...a);
    const error = (...a) => console.error('[Alimentos]', ...a);

    // Sanity inicial
    if (!form || !input || !tbody || !baseUrl) {
      warn('Faltan nodos/props:', {
        form: !!form,
        input: !!input,
        tbody: !!tbody,
        baseUrl
      });
      return;
    }

    // --- UI helpers ---
    function setLoading(on) {
      if (spinner) spinner.classList.toggle('d-none', !on);
      log('spinner', on ? 'ON' : 'OFF');
    }

    function countRows(html) {
      const temp = document.createElement('tbody');
      temp.innerHTML = html;
      return temp.querySelectorAll('tr').length;
    }

    function looksLikeFullLayout(html) {
      return /<html|<head|<body/i.test(html);
    }

    function toggleClear() {
      if (!btnClear) return; // si no hay botón, no hacemos nada
      const hasText = !!(input.value && input.value.trim());
      btnClear.disabled = !hasText;
      // Si prefieres ocultarlo:
      // btnClear.classList.toggle('d-none', !hasText);
    }

    // --- Eventos ---
    // Evitar submit tradicional
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      log('submit → fetch manual', input.value);
      fetchRows(input.value);
    });

    // Debounce de input
    let timer = null;
    let aborter = null;
    input.addEventListener('input', () => {
      const q = input.value;
      toggleClear();
      log('input:', q);
      clearTimeout(timer);
      timer = setTimeout(() => {
        log('debounce → fetch con q=', q);
        fetchRows(q);
      }, 250);
    });

    // CLR: limpiar, enfocar, refrescar
    if (btnClear) {
      // Estado inicial
      toggleClear();

      btnClear.addEventListener('click', () => {
        if (!input.value) return;
        log('CLR → limpiar y recargar completo');
        input.value = '';
        toggleClear();
        input.focus();
        fetchRows('');
      });
    }

    // ESC para limpiar
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && input.value) {
        log('ESC → limpiar y recargar completo');
        input.value = '';
        toggleClear();
        fetchRows('');
      }
    });

    // --- Fetch parcial ---
    async function fetchRows(q) {
      const t0 = performance.now();
      try {
        setLoading(true);

        // Cancelar petición anterior si existe
        if (aborter) {
          aborter.abort();
          log('abort → cancelada petición anterior');
        }
        aborter = new AbortController();

        const url = new URL(baseUrl, window.location.origin);
        if (q && q.trim() !== '') url.searchParams.set('q', q.trim());
        url.searchParams.set('partial', '1');

        const urlStr = url.toString();
        log('GET', urlStr);

        const res = await fetch(urlStr, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          },
          signal: aborter.signal
        });

        log('status:', res.status, res.statusText);

        const html = await res.text();
        const dur = (performance.now() - t0).toFixed(1);

        if (!res.ok) {
          error('respuesta no OK. body:', html);
          return;
        }

        if (looksLikeFullLayout(html)) {
          warn('Parece layout completo → revisa rama parcial en el controlador');
        }

        tbody.innerHTML = html;
        log('OK. filas:', countRows(html), '| bytes:', html.length, '| tiempo:', dur + 'ms');
      } catch (err) {
        if (err.name === 'AbortError') {
          warn('fetch abortado por nueva tecla');
        } else {
          error('fetch error:', err);
        }
      } finally {
        setLoading(false);
      }
    }

    // Estado inicial del CLR si existe
    toggleClear();
  })();
</script>



<?php $this->endSection(); ?>