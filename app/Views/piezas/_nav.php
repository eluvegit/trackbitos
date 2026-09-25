<div class="d-flex align-items-center gap-2 flex-wrap mb-3">
    <a href="<?= site_url('piezas') ?>" class="btn btn-sm btn-outline-secondary" title="Piezas"><i class="bi bi-box"></i></a>
    <?= $this->include('piezas/_barra') ?>
</div>
<script>
document.addEventListener('click', function (e) {
    var b = e.target.closest('#btnOrganizar, [data-bs-toggle="modal"]');
    if (!b) return;
    var destino = b.id === 'btnOrganizar' ? '#organizar' : b.getAttribute('data-bs-target');
    if (b.id !== 'btnOrganizar' && document.querySelector(destino)) return;
    e.preventDefault();
    e.stopPropagation();
    location.href = '<?= site_url('piezas') ?>' + destino;
}, true);
</script>
