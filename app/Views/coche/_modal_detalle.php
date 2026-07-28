<!-- Modal de detalle de una acción del historial (compartido entre coche/index y coche/acciones/index) -->
<div class="modal fade" id="modalDetalleAccion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="bi" id="detalleIcono"></i>
                    <span id="detalleTitulo"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0 coche-detalle-dl">
                    <dt class="col-4">Fecha</dt>
                    <dd class="col-8" id="detalleFecha"></dd>

                    <dt class="col-4">Kilómetros</dt>
                    <dd class="col-8" id="detalleKm"></dd>

                    <dt class="col-4">Notas</dt>
                    <dd class="col-8" id="detalleNotas"></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <a href="#" id="detalleEditar" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Editar</a>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const modalEl = document.getElementById('modalDetalleAccion');
    // bootstrap.bundle.min.js se carga al final del layout, después de este
    // script, así que la instancia del modal se crea perezosamente (en el
    // primer clic) en vez de al cargar la página, para no depender del orden.
    let modal = null;
    const iconoEl = document.getElementById('detalleIcono');
    const tituloEl = document.getElementById('detalleTitulo');
    const fechaEl = document.getElementById('detalleFecha');
    const kmEl = document.getElementById('detalleKm');
    const notasEl = document.getElementById('detalleNotas');
    const editarEl = document.getElementById('detalleEditar');

    document.querySelectorAll('.js-detalle-accion').forEach(card => {
        const abrir = () => {
            modal ??= new bootstrap.Modal(modalEl);
            iconoEl.className = 'bi ' + card.dataset.icono + (card.dataset.color ? ' text-' + card.dataset.color : '');
            tituloEl.textContent = card.dataset.titulo;
            fechaEl.textContent = card.dataset.fecha + ' (' + card.dataset.hace + ')';

            const km = card.dataset.km;
            kmEl.textContent = km ? km + ' km' : 'Sin especificar';

            const notas = card.dataset.notas;
            notasEl.textContent = notas || 'Sin notas';

            editarEl.href = card.dataset.editar;
            modal.show();
        };

        card.addEventListener('click', (e) => {
            if (e.target.closest('.coche-rec-actions')) return;
            abrir();
        });
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                abrir();
            }
        });
    });
})();
</script>
