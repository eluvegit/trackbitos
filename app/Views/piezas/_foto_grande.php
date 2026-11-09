<div class="modal fade" id="modalFotoGrande" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-black border-0" data-bs-dismiss="modal" style="cursor: zoom-out;">
            <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" style="z-index: 1;" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            <div class="d-flex align-items-center justify-content-center h-100 p-2">
                <img data-foto-grande-img alt="" style="max-width: 100%; max-height: 100%; object-fit: contain;">
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('modalFotoGrande');
    var img = modal.querySelector('[data-foto-grande-img]');
    modal.addEventListener('hidden.bs.modal', function () { img.removeAttribute('src'); });
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a[data-foto-grande]');
        if (!a || e.ctrlKey || e.metaKey || e.shiftKey) return;
        e.preventDefault();
        img.src = a.href;
        bootstrap.Modal.getOrCreateInstance(modal).show();
    });
})();
</script>
