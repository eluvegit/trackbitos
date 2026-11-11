<?php $enIndex ??= uri_string() === 'piezas'; ?>
<?= $this->include('piezas/_menu') ?>
<?php // Selector de huecos: convierte los <select data-hueco> en un panel visual. ?>
<script>window.PZ_HUECOS_URL = '<?= site_url('piezas/ubicaciones/huecos/mapa') ?>';</script>
<script src="<?= base_url('assets/js/piezas-hueco-picker.js') ?>?v=2" defer></script>
<?php if (!$enIndex): ?>
<script src="<?= base_url('assets/js/piezas-modales.js') ?>?v=1"></script>
<script>
(function () {
    var BARRA = ['#modalUltimoPedido', '#modalPendientes', '#modalCategorias', '#modalPautas', '#modalResina', '#modalCalcTiempo', '#modalFamilia'];
    var cargando = null;

    function cargarModales() {
        if (!cargando) {
            cargando = fetch('<?= site_url('piezas/modales-barra') ?>', { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { if (!r.ok) throw r; return r.text(); })
                .then(function (html) {
                    var caja = document.createElement('div');
                    caja.innerHTML = html;
                    caja.querySelectorAll('input[name="volver"]').forEach(function (i) { i.value = location.href.split('#')[0]; });
                    while (caja.firstElementChild) document.body.appendChild(caja.firstElementChild);
                    if (window.pzInitCalc) window.pzInitCalc();
                })
                .catch(function (e) { cargando = null; throw e; });
        }
        return cargando;
    }

    window.addEventListener('click', function (e) {
        var b = e.target.closest && e.target.closest('#btnOrganizar, [data-bs-toggle="modal"]');
        if (!b) return;
        var destino = b.id === 'btnOrganizar' ? '#organizar' : b.getAttribute('data-bs-target');
        if (b.id !== 'btnOrganizar' && document.querySelector(destino)) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        if (BARRA.indexOf(destino) === -1) {
            location.href = '<?= site_url('piezas') ?>' + destino;
            return;
        }
        b.disabled = true;
        cargarModales()
            .then(function () { bootstrap.Modal.getOrCreateInstance(document.querySelector(destino)).show(); })
            .catch(function () { location.href = '<?= site_url('piezas') ?>' + destino; })
            .finally(function () { b.disabled = false; });
    }, true);
})();
</script>
<?php endif; ?>
