<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<style>
    .silo-fila-unidades {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .silo-tarjeta {
        width: 11rem;
        height: 11rem;
        border-radius: 1.25rem;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-body-bg);
        padding: .85rem .6rem .6rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }

    .silo-tarjeta-unidad {
        cursor: pointer;
    }

    .silo-tarjeta-unidad:hover {
        transform: translateY(-3px);
        box-shadow: 0 .6rem 1.2rem rgba(0, 0, 0, .08);
        border-color: var(--silo-accent, var(--bs-primary));
    }

    /* El icono ocupa todo el hueco libre entre la capacidad y el nombre,
       en vez de un tamaño fijo pequeño — así se ajusta a la tarjeta. */
    .silo-tarjeta-icono {
        flex: 1;
        min-height: 0;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .silo-tarjeta-icono .silo-icono-unidad {
        width: 4.5rem;
        height: 4.5rem;
        font-size: 4.5rem;
    }

    .silo-tarjeta-capacidad {
        font-weight: 700;
        font-size: 1.15rem;
        line-height: 1;
        color: var(--bs-emphasis-color);
    }

    .silo-tarjeta-capacidad small {
        font-size: .65rem;
        font-weight: 600;
        color: var(--bs-secondary-color);
        margin-left: .15rem;
    }

    /* Color por defecto (fuera de un .silo-nivel, ej. el selector del
       modal); dentro de una tarjeta lo pisa .silo-nivel .silo-hdd de
       _estilos_nivel.php con el acento del nivel al que pertenece. */
    .silo-icono-unidad {
        color: var(--bs-primary);
    }

    .silo-tarjeta-info {
        width: 100%;
        margin-top: auto;
        line-height: 1.25;
    }

    .silo-tarjeta-nombre {
        font-size: .82rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .silo-tarjeta-ruta {
        font-size: .68rem;
        font-family: var(--bs-font-monospace);
        color: var(--bs-secondary-color);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .silo-tarjeta-detalle {
        font-size: .68rem;
        color: var(--bs-secondary-color);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .silo-tarjeta-anadir {
        border-radius: 50%;
        border: 2px dashed var(--bs-border-color);
        background: transparent;
        color: var(--bs-secondary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .silo-tarjeta-anadir:hover {
        border-color: var(--silo-accent, var(--bs-primary));
        color: var(--silo-accent, var(--bs-primary));
        background: var(--silo-tint, var(--bs-primary-bg-subtle));
        transform: translateY(-3px);
    }

    .silo-tarjeta-anadir i {
        font-size: 2.2rem;
    }

    .silo-selector-tipo .btn-check:checked + label {
        color: var(--bs-primary);
        border-color: var(--bs-primary);
        background: var(--bs-primary-bg-subtle);
    }

    .silo-selector-tipo label {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .25rem;
        padding: .6rem .4rem .4rem;
        border-radius: 1rem;
        border: 1px solid var(--bs-border-color);
        font-size: .68rem;
        color: var(--bs-secondary-color);
        cursor: pointer;
        flex: 1;
    }
</style>

<?= $this->include('silo/_estilos_nivel') ?>

<h5 class="mb-3 d-flex align-items-center gap-2">
    <i class="bi bi-hdd-stack text-primary"></i>
    <a href="<?= site_url('silo') ?>" class="text-decoration-none text-muted fw-normal">Silo</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Unidades</strong>
    <a href="<?= site_url('silo/mi-pc') ?>" class="text-decoration-none ms-1 text-muted" title="Mi PC">
        <i class="bi bi-pc-display"></i>
    </a>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-danger py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<?php $nivelLabel = [1 => 'Nivel 1 — Maestro', 2 => 'Nivel 2 — Año', 3 => 'Nivel 3 — Temática']; ?>

<?php foreach ([1, 2, 3] as $nivel): ?>
    <h6 class="mb-3 silo-nivel silo-n<?= $nivel ?>"><?= $nivelLabel[$nivel] ?></h6>
    <div class="silo-fila-unidades silo-nivel silo-n<?= $nivel ?>">
        <?php foreach ($porNivel[$nivel] as $u): ?>
            <?php
                $cap = silo_capacidad_partes($u['capacidad_bytes'] ? (int) $u['capacidad_bytes'] : null);
                $capacidadTbValor = $u['capacidad_bytes']
                    ? rtrim(rtrim(number_format(((int) $u['capacidad_bytes']) / 1_000_000_000_000, 3, '.', ''), '0'), '.')
                    : '';
                $detalle = $nivel !== 1
                    ? ($u['agrupador'] ?? '')
                    : trim((string) ($u['identificacion_fisica'] ?? ''));
            ?>
            <div class="silo-tarjeta silo-tarjeta-unidad"
                 data-id="<?= (int) $u['id'] ?>"
                 data-nivel="<?= (int) $nivel ?>"
                 data-numero="<?= (int) $u['numero'] ?>"
                 data-etiqueta="<?= esc($u['etiqueta'] ?? '', 'attr') ?>"
                 data-tipo-fisico="<?= esc($u['tipo_fisico'] ?? '', 'attr') ?>"
                 data-identificacion-fisica="<?= esc($u['identificacion_fisica'] ?? '', 'attr') ?>"
                 data-ruta-montaje="<?= esc($u['ruta_montaje'] ?? '', 'attr') ?>"
                 data-agrupador="<?= esc($u['agrupador'] ?? '', 'attr') ?>"
                 data-capacidad-tb="<?= esc($capacidadTbValor, 'attr') ?>"
                 data-piezas="<?= (int) ($piezasPorUnidad[$u['id']] ?? 0) ?>"
                 onclick="siloAbrirEdicion(this)">
                <div class="silo-tarjeta-capacidad"><?= esc($cap['valor']) ?><small><?= esc($cap['unidad']) ?></small></div>
                <div class="silo-tarjeta-icono"><?= silo_icono_unidad($u['tipo_fisico'] ?? null) ?></div>
                <div class="silo-tarjeta-info">
                    <div class="silo-tarjeta-nombre"><?= esc($u['etiqueta'] ?: 'Unidad #' . (int) $u['numero']) ?></div>
                    <?php if ($u['ruta_montaje']): ?>
                        <div class="silo-tarjeta-ruta"><?= esc($u['ruta_montaje']) ?></div>
                    <?php endif; ?>
                    <?php if ($detalle !== ''): ?>
                        <div class="silo-tarjeta-detalle"><?= esc($detalle) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="silo-tarjeta silo-tarjeta-anadir" title="Añadir unidad" onclick="siloAbrirAlta(<?= $nivel ?>)">
            <i class="bi bi-plus-lg"></i>
        </div>
    </div>
<?php endforeach; ?>

<!-- Modal único: alta y edición comparten formulario, cambia el action y qué secciones se ven. -->
<div class="modal fade" id="modalUnidad" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <form method="post" id="formUnidad" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="nivel" id="mu-nivel">

                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-semibold" id="mu-titulo">Nueva unidad</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body pt-2">
                    <div class="mb-3">
                        <label class="form-label small text-muted mb-1">Etiqueta</label>
                        <input type="text" name="etiqueta" id="mu-etiqueta" class="form-control"
                               placeholder="ej. Maestro #1, USB rojo...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted mb-1">Tipo físico</label>
                        <div class="d-flex gap-2 silo-selector-tipo">
                            <?php foreach (['usb' => 'USB', 'hdd_interno' => 'Interno', 'hdd_externo' => 'Externo'] as $valor => $texto): ?>
                                <input type="radio" class="btn-check" name="tipo_fisico" id="mu-tipo-<?= $valor ?>" value="<?= $valor ?>" autocomplete="off">
                                <label for="mu-tipo-<?= $valor ?>"><?= silo_icono_unidad($valor, 30) ?><span><?= $texto ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small text-muted mb-1">Capacidad (TB)</label>
                            <input type="number" name="capacidad_tb" id="mu-capacidad" min="0.5" step="0.5"
                                   class="form-control" placeholder="ej. 2">
                        </div>
                        <div class="col-6" id="mu-grupo-agrupador">
                            <label class="form-label small text-muted mb-1">Año o categoría</label>
                            <input type="text" name="agrupador" id="mu-agrupador" class="form-control" placeholder="ej. 2026">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted mb-1">Ruta de montaje en esta máquina</label>
                        <input type="text" name="ruta_montaje" id="mu-ruta" class="form-control font-monospace" placeholder="ej. D:\Maestro">
                    </div>

                    <div class="mb-1" id="mu-grupo-identificacion">
                        <label class="form-label small text-muted mb-1">Identificación física</label>
                        <textarea name="identificacion_fisica" id="mu-identificacion" rows="2" class="form-control"
                                  placeholder="nº de serie, etiqueta del volumen, marca/modelo, color, dónde está guardado..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0 flex-column align-items-stretch gap-2">
                    <button type="submit" class="btn btn-primary rounded-pill">Guardar</button>
                    <div class="d-flex justify-content-between align-items-center" id="mu-acciones-secundarias">
                        <a href="#" id="mu-descargar" class="btn btn-sm btn-link text-muted text-decoration-none px-0">
                            <i class="bi bi-file-earmark-code"></i> .silo_unit.json
                        </a>
                        <button type="button" id="mu-borrar" class="btn btn-sm btn-link text-danger text-decoration-none px-0">
                            <i class="bi bi-trash"></i> Borrar unidad
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Formulario aparte solo para el borrado: el modal no puede anidar dos <form>. -->
<form method="post" id="formBorrarUnidad" action="" class="d-none">
    <?= csrf_field() ?>
</form>

<script>
    // El layout carga bootstrap.bundle.min.js al final del <body>, DESPUÉS
    // de esta sección de contenido — sin esperar a DOMContentLoaded,
    // `bootstrap` todavía no existe aquí y el script entero moría en
    // silencio (siloAbrirAlta/siloAbrirEdicion nunca quedaban definidas).
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('modalUnidad');
        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('formUnidad');
        const campos = {
            etiqueta: document.getElementById('mu-etiqueta'),
            capacidad: document.getElementById('mu-capacidad'),
            agrupador: document.getElementById('mu-agrupador'),
            ruta: document.getElementById('mu-ruta'),
            identificacion: document.getElementById('mu-identificacion'),
            nivel: document.getElementById('mu-nivel'),
        };
        const grupoAgrupador = document.getElementById('mu-grupo-agrupador');
        const grupoIdentificacion = document.getElementById('mu-grupo-identificacion');
        const accionesSecundarias = document.getElementById('mu-acciones-secundarias');
        const titulo = document.getElementById('mu-titulo');
        const btnDescargar = document.getElementById('mu-descargar');
        const btnBorrar = document.getElementById('mu-borrar');
        const formBorrar = document.getElementById('formBorrarUnidad');

        function limpiarFormulario() {
            form.reset();
            document.querySelectorAll('#formUnidad input[name="tipo_fisico"]').forEach(r => r.checked = false);
        }

        window.siloAbrirAlta = function (nivel) {
            limpiarFormulario();
            titulo.textContent = 'Nueva unidad';
            form.action = "<?= site_url('silo/unidades/crear') ?>";
            campos.nivel.value = nivel;
            grupoAgrupador.style.display = nivel === 1 ? 'none' : '';
            grupoIdentificacion.style.display = 'none'; // sin disco delante todavía, no tiene sentido pedirla al alta
            accionesSecundarias.style.display = 'none';
            modal.show();
        };

        window.siloAbrirEdicion = function (tarjeta) {
            const d = tarjeta.dataset;
            limpiarFormulario();
            titulo.textContent = d.etiqueta || ('Unidad #' + d.numero);
            form.action = "<?= site_url('silo/unidades') ?>/" + d.id + "/actualizar";
            campos.nivel.value = d.nivel;
            campos.etiqueta.value = d.etiqueta || '';
            campos.capacidad.value = d.capacidadTb || '';
            campos.agrupador.value = d.agrupador || '';
            campos.ruta.value = d.rutaMontaje || '';
            campos.identificacion.value = d.identificacionFisica || '';
            if (d.tipoFisico) {
                const radio = document.getElementById('mu-tipo-' + d.tipoFisico);
                if (radio) radio.checked = true;
            }

            grupoAgrupador.style.display = d.nivel === '1' ? 'none' : '';
            grupoIdentificacion.style.display = '';
            accionesSecundarias.style.display = '';

            btnDescargar.href = "<?= site_url('silo/unidades') ?>/" + d.id + "/fichero-control";

            btnBorrar.onclick = function () {
                const piezas = parseInt(d.piezas || '0', 10);
                const aviso = piezas > 0
                    ? `Esta unidad tiene ${piezas} pieza(s) registrada(s). ¿Seguro que quieres borrarla?`
                    : '¿Borrar esta unidad vacía?';
                if (!confirm(aviso)) return;
                formBorrar.action = "<?= site_url('silo/unidades') ?>/" + d.id + "/borrar";
                formBorrar.submit();
            };

            modal.show();
        };
    });
</script>

<?= $this->endSection() ?>
