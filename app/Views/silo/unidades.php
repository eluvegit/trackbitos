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

    .silo-tarjeta-escaneo {
        font-size: .68rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .silo-tarjeta-escaneo.text-warning,
    .silo-tarjeta-escaneo.text-danger {
        font-weight: 600;
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
    <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
        <h6 class="mb-0 silo-nivel silo-n<?= $nivel ?>"><?= $nivelLabel[$nivel] ?></h6>
        <?php if ($nivel === 2): ?>
            <!-- "Recalcular reparto" agrupa años consecutivos entre las
                 unidades de Nivel 2 YA dadas de alta, cada una con su
                 capacidad real (SiloPropagacionService::aplicarPlanNivel2())
                 — no borra ni crea unidades, así que no toca identificación
                 física/ruta de montaje/etiqueta puestas a mano; solo
                 reconstruye qué años tiene cada una. -->
            <form method="post" action="<?= site_url('silo/unidades/nivel2/recalcular') ?>" class="ms-auto"
                  onsubmit="return confirm('Esto reparte de nuevo los años entre las unidades de Nivel 2 ya dadas de alta (agrupa consecutivos según la capacidad de cada una, sin fragmentar ninguno). No borra unidades ni sus datos, solo qué años tiene cada una. ¿Continuar?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-arrow-repeat"></i> Recalcular reparto
                </button>
            </form>
        <?php endif; ?>
    </div>
    <div class="silo-fila-unidades silo-nivel silo-n<?= $nivel ?>">
        <?php foreach ($porNivel[$nivel] as $u): ?>
            <?php
                $cap = silo_capacidad_partes($u['capacidad_bytes'] ? (int) $u['capacidad_bytes'] : null);
                $capForm = silo_capacidad_formulario($u['capacidad_bytes'] ? (int) $u['capacidad_bytes'] : null);
                // Una unidad de Nivel 2 puede agrupar varios años
                // consecutivos (planificación por capacidad de USB) — ya
                // vienen comprimidos en rango ("2010-2018"), no uno a uno.
                $bucketsTexto = $bucketsPorUnidad[$u['id']] ?? '';
                $detalle = $nivel !== 1
                    ? ($bucketsTexto !== '' ? $bucketsTexto : ($u['agrupador'] ?? ''))
                    : trim((string) ($u['identificacion_fisica'] ?? ''));
                $excede = $excedePorUnidad[$u['id']] ?? false;
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
                 data-capacidad-valor="<?= esc($capForm['valor'], 'attr') ?>"
                 data-capacidad-unidad="<?= esc($capForm['unidad'], 'attr') ?>"
                 data-piezas="<?= (int) ($piezasPorUnidad[$u['id']] ?? 0) ?>"
                 onclick="siloAbrirEdicion(this)">
                <div class="silo-tarjeta-capacidad<?= $excede ? ' text-danger' : '' ?>">
                    <?php if ($excede): ?>
                        <i class="bi bi-exclamation-triangle-fill" title="Excede la capacidad declarada de la unidad"></i>
                    <?php endif; ?>
                    <?= esc($cap['valor']) ?><small><?= esc($cap['unidad']) ?></small>
                </div>
                <div class="silo-tarjeta-icono"><?= silo_icono_unidad($u['tipo_fisico'] ?? null) ?></div>
                <div class="silo-tarjeta-info">
                    <div class="silo-tarjeta-nombre"><?= esc($u['etiqueta'] ?: 'Unidad #' . (int) $u['numero']) ?></div>
                    <?php if ($u['ruta_montaje']): ?>
                        <div class="silo-tarjeta-ruta"><?= esc($u['ruta_montaje']) ?></div>
                    <?php endif; ?>
                    <?php if ($detalle !== ''): ?>
                        <div class="silo-tarjeta-detalle"><?= esc($detalle) ?></div>
                    <?php endif; ?>
                    <?php if ($excede): ?>
                        <div class="silo-tarjeta-escaneo text-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i> excede su capacidad
                        </div>
                    <?php endif; ?>
                    <?php if ($nivel === 1): $tarea = $tareasPorUnidad[$u['id']] ?? null; ?>
                        <?php if ($tarea && in_array($tarea['estado'], ['pendiente', 'en_curso'], true)): ?>
                            <div class="silo-tarjeta-escaneo text-warning">
                                <i class="bi bi-hourglass-split"></i> esperando agente
                            </div>
                        <?php elseif ($tarea && $tarea['estado'] === 'error'): ?>
                            <div class="silo-tarjeta-escaneo text-danger">
                                <i class="bi bi-exclamation-triangle"></i> error en el escaneo
                            </div>
                        <?php elseif ($tarea && $tarea['estado'] === 'hecha'): ?>
                            <div class="silo-tarjeta-escaneo text-success">
                                <i class="bi bi-check-circle"></i> escaneado <?= esc(silo_fecha_humana($tarea['actualizado_en'] ?? $tarea['creado_en'])) ?>
                            </div>
                        <?php endif; ?>
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
                            <label class="form-label small text-muted mb-1">Capacidad</label>
                            <div class="input-group">
                                <input type="number" name="capacidad_valor" id="mu-capacidad" min="0.01" step="0.01"
                                       class="form-control" placeholder="ej. 64">
                                <select name="capacidad_unidad" id="mu-capacidad-unidad" class="form-select" style="max-width: 5.5rem;">
                                    <option value="gb">GB</option>
                                    <option value="tb">TB</option>
                                </select>
                            </div>
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
                    <div id="mu-grupo-escaneo" style="display:none;">
                        <button type="button" id="mu-escanear" class="btn btn-outline-primary btn-sm rounded-pill w-100">
                            <i class="bi bi-arrow-repeat"></i> Solicitar escaneo
                        </button>
                    </div>
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

<!-- Formularios aparte para borrado y solicitud de escaneo: el modal no puede anidar dos <form>. -->
<form method="post" id="formBorrarUnidad" action="" class="d-none">
    <?= csrf_field() ?>
</form>
<form method="post" id="formSolicitarEscaneo" action="" class="d-none">
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
            capacidadUnidad: document.getElementById('mu-capacidad-unidad'),
            agrupador: document.getElementById('mu-agrupador'),
            ruta: document.getElementById('mu-ruta'),
            identificacion: document.getElementById('mu-identificacion'),
            nivel: document.getElementById('mu-nivel'),
        };
        const grupoAgrupador = document.getElementById('mu-grupo-agrupador');
        const grupoIdentificacion = document.getElementById('mu-grupo-identificacion');
        const grupoEscaneo = document.getElementById('mu-grupo-escaneo');
        const accionesSecundarias = document.getElementById('mu-acciones-secundarias');
        const titulo = document.getElementById('mu-titulo');
        const btnDescargar = document.getElementById('mu-descargar');
        const btnBorrar = document.getElementById('mu-borrar');
        const btnEscanear = document.getElementById('mu-escanear');
        const formBorrar = document.getElementById('formBorrarUnidad');
        const formEscanear = document.getElementById('formSolicitarEscaneo');

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
            grupoEscaneo.style.display = 'none'; // unidad todavía sin crear, nada que escanear
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
            campos.capacidad.value = d.capacidadValor || '';
            campos.capacidadUnidad.value = d.capacidadUnidad || 'gb';
            campos.agrupador.value = d.agrupador || '';
            campos.ruta.value = d.rutaMontaje || '';
            campos.identificacion.value = d.identificacionFisica || '';
            if (d.tipoFisico) {
                const radio = document.getElementById('mu-tipo-' + d.tipoFisico);
                if (radio) radio.checked = true;
            }

            grupoAgrupador.style.display = d.nivel === '1' ? 'none' : '';
            grupoIdentificacion.style.display = '';
            grupoEscaneo.style.display = d.nivel === '1' ? '' : 'none'; // solo el Maestro se escanea (plan Silo §2)
            accionesSecundarias.style.display = '';

            btnDescargar.href = "<?= site_url('silo/unidades') ?>/" + d.id + "/fichero-control";

            btnEscanear.onclick = function () {
                formEscanear.action = "<?= site_url('silo/unidades') ?>/" + d.id + "/solicitar-escaneo";
                formEscanear.submit();
            };

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
