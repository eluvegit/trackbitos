<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<style>
    .silo-tarjeta-unidad {
        cursor: pointer;
    }

    .silo-tarjeta-unidad:hover {
        transform: translateY(-3px);
        box-shadow: 0 .6rem 1.2rem rgba(0, 0, 0, .18);
        border-color: var(--silo-accent, var(--bs-primary));
    }

    .silo-tarjeta-excede,
    .silo-tarjeta-excede:hover {
        border-color: var(--bs-danger);
    }

    .silo-tarjeta-exceso-tag {
        display: block;
        font-family: var(--bs-font-monospace);
        font-size: .6rem;
        font-weight: 700;
        color: var(--bs-danger);
        margin-top: .1rem;
    }

    /* Color por defecto (fuera de un .silo-nivel, ej. el selector del
       modal); dentro de una tarjeta lo pisa .silo-nivel .silo-hdd de
       _estilos_nivel.php con el acento del nivel al que pertenece. */
    .silo-icono-unidad {
        color: var(--bs-primary);
    }

    .silo-tarjeta-uso {
        width: 100%;
        margin-bottom: .5rem;
    }

    .silo-tarjeta-uso .progress {
        height: 4px;
        border-radius: 2px;
        background: var(--bs-secondary-bg);
    }

    .silo-tarjeta-uso-texto {
        display: flex;
        justify-content: space-between;
        gap: .4rem;
        margin-top: .1rem;
        font-size: .66rem;
        color: var(--bs-secondary-color);
        white-space: nowrap;
    }

    .silo-tarjeta-uso-texto .silo-libre {
        color: var(--bs-success);
    }

    .silo-tarjeta-uso-texto.excede .silo-libre {
        color: var(--bs-danger);
    }

    /* Tarjeta-resumen: lo que hay en el Maestro y todavía no cabe en
       ninguna unidad de este nivel. No es una unidad — es lo que falta
       por repartir. */
    .silo-tarjeta-pendiente {
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: .35rem;
        border-style: dashed;
        border-color: var(--bs-warning-border-subtle);
        background: var(--bs-warning-bg-subtle);
        color: var(--bs-warning-text-emphasis);
    }

    .silo-tarjeta-pendiente .bi {
        font-size: 1.4rem;
        opacity: .75;
    }

    .silo-tarjeta-pendiente-gb {
        font-family: var(--bs-font-monospace);
        font-weight: 700;
        font-size: 1.7rem;
        line-height: 1;
    }

    .silo-tarjeta-pendiente-txt {
        font-size: .72rem;
        line-height: 1.25;
    }

    .silo-tarjeta-anadir {
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: .5rem;
        border: 2px dashed var(--bs-border-color);
        background: transparent;
        color: var(--bs-secondary-color);
        cursor: pointer;
    }

    .silo-tarjeta-anadir i {
        font-size: 1.6rem;
    }

    .silo-tarjeta-anadir span {
        font-size: .66rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .silo-tarjeta-anadir:hover {
        border-color: var(--silo-accent, var(--bs-primary));
        color: var(--silo-accent, var(--bs-primary));
        background: var(--silo-tint, var(--bs-primary-bg-subtle));
        transform: translateY(-3px);
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

<?= $this->include('silo/_estilos_control') ?>
<?= $this->include('silo/_estilos_nivel') ?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <div class="silo-control-breadcrumb">
            <span class="silo-control-dot"></span>
            <a href="<?= site_url('silo') ?>">Silo</a> / Inventario de unidades
        </div>
        <h1 class="silo-control-titulo">
            Control de <strong>Unidades</strong>
            <a href="<?= site_url('silo/mi-pc') ?>" class="text-decoration-none ms-1 text-muted fs-6" title="Mi PC">
                <i class="bi bi-pc-display"></i>
            </a>
        </h1>
    </div>
    <!-- "Recalcular reparto" recoloca las copias automáticas entre las
         unidades YA dadas de alta: Nivel 2 agrupa años consecutivos según
         la capacidad de cada USB (sin fragmentar ninguno) y Nivel 3 coloca
         cada carpeta en la unidad de su categoría. NUNCA crea ni borra
         unidades — lo que no cabe en ninguna se queda en la tarjeta
         "pendiente de almacenar" hasta que se dé de alta una unidad donde
         quepa y se vuelva a pulsar aquí. (SiloPropagacionService::
         aplicarPlanNivel2() + ::repartirCopia3().) -->
    <form method="post" action="<?= site_url('silo/unidades/nivel2/recalcular') ?>"
          onsubmit="return confirm('Reparte de nuevo las copias automáticas (años en Nivel 2, categorías en Nivel 3) entre las unidades ya dadas de alta. No crea ni borra unidades: lo que no quepa queda pendiente de almacenar. ¿Continuar?');">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline-secondary silo-btn-accent">
            <i class="bi bi-arrow-repeat"></i> Recalcular reparto
        </button>
    </form>
</div>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>
<?php if (session('error')): ?>
    <div class="alert alert-danger py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<?php
    $nivelInfo = [
        1 => ['titulo' => 'Nivel Maestro',  'sub' => 'Archivo principal'],
        2 => ['titulo' => 'Nivel Año',      'sub' => 'Archivo cronológico'],
        3 => ['titulo' => 'Nivel Temática', 'sub' => 'Archivo temático'],
    ];
    $totalUnidades = array_sum(array_map('count', $porNivel));
?>

<?php foreach ([1, 2, 3] as $nivel): ?>
    <div class="silo-seccion-header-row silo-nivel silo-n<?= $nivel ?>">
        <span class="silo-seccion-num"><?= sprintf('%02d', $nivel) ?></span>
        <h6 class="silo-seccion-titulo"><?= esc(mb_strtoupper($nivelInfo[$nivel]['titulo'])) ?></h6>
        <span class="silo-seccion-sub"><?= esc(mb_strtoupper($nivelInfo[$nivel]['sub'])) ?></span>
        <div class="silo-seccion-linea"></div>
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

                // Ocupado / libre reales: el "reparto" llena las unidades
                // pero no decía cuánto pesa ya cada una — $usado es la suma
                // de silo_piezas.tamano_bytes de las piezas ubicadas aquí.
                $usado    = (int) ($usadoPorUnidad[$u['id']] ?? 0);
                $capBytes = $u['capacidad_bytes'] !== null ? (int) $u['capacidad_bytes'] : null;
                $libre    = $capBytes !== null ? max(0, $capBytes - $usado) : null;
                $pctUso   = $capBytes ? min(100, (int) round($usado / $capBytes * 100)) : 0;
                $exceso   = $excede && $capBytes !== null ? silo_tamano_corto($usado - $capBytes) : null;
            ?>
            <div class="silo-tarjeta silo-tarjeta-unidad<?= $excede ? ' silo-tarjeta-excede' : '' ?>"
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
                <div class="silo-tarjeta-top">
                    <div class="silo-tarjeta-capacidad<?= $excede ? ' text-danger' : '' ?>">
                        <?= esc($cap['valor']) ?><small><?= esc($cap['unidad']) ?></small>
                        <?php if ($exceso !== null): ?>
                            <span class="silo-tarjeta-exceso-tag"><?= esc($exceso) ?> exceso</span>
                        <?php endif; ?>
                    </div>
                    <span class="silo-tarjeta-idbadge">#<?= (int) $u['id'] ?></span>
                </div>

                <?php if ($capBytes !== null || $usado > 0): ?>
                    <div class="silo-tarjeta-uso"
                         title="<?= esc(silo_formatear_tamano($usado ?: null)) ?> ocupado<?= $capBytes !== null ? ' de ' . esc(silo_formatear_tamano($capBytes)) . ' — ' . esc(silo_formatear_tamano($libre ?: null)) . ' libre' : '' ?>">
                        <?php if ($capBytes !== null): ?>
                            <div class="progress" role="presentation">
                                <div class="progress-bar<?= $excede ? ' bg-danger' : ' silo-progress-fill' ?>" style="width: <?= $pctUso ?>%;"></div>
                            </div>
                            <div class="silo-tarjeta-uso-texto<?= $excede ? ' excede' : '' ?>">
                                <span><?= esc(silo_tamano_corto($usado)) ?></span>
                                <span class="silo-libre"><?= esc(silo_tamano_corto($libre)) ?> libre</span>
                            </div>
                        <?php else: ?>
                            <div class="silo-tarjeta-uso-texto">
                                <span><?= esc(silo_tamano_corto($usado)) ?> en uso</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="silo-tarjeta-nombre-linea">
                    <?= silo_icono_unidad($u['tipo_fisico'] ?? null) ?>
                    <?php $etq = $u['etiqueta'] ?: 'Unidad #' . (int) $u['numero']; ?>
                    <span class="silo-tarjeta-nombre"><?= esc(silo_nombre_sin_contenido($etq)) ?></span>
                </div>
                <?php $badgesEtq = silo_badges_contenido($etq); ?>
                <?php if ($badgesEtq !== ''): ?>
                    <div class="silo-tarjeta-contenido"><?= $badgesEtq ?></div>
                <?php endif; ?>
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
        <?php endforeach; ?>

        <?php $pend = $pendientePorCopia[$nivel] ?? null; ?>
        <?php if ($nivel !== 1 && $pend !== null && $pend['bytes'] > 0): ?>
            <div class="silo-tarjeta silo-tarjeta-pendiente"
                 title="<?= esc(silo_formatear_tamano($pend['bytes'])) ?> en <?= (int) $pend['piezas'] ?> carpeta(s) que ya están en el Maestro pero aún no caben en ninguna unidad de este nivel. Da de alta una unidad y pulsa «Recalcular reparto».">
                <div class="silo-tarjeta-pendiente-gb"><?= esc(silo_tamano_corto($pend['bytes'])) ?></div>
                <i class="bi bi-hourglass-split"></i>
                <div class="silo-tarjeta-pendiente-txt text-center">
                    pendiente de almacenar
                    <span class="d-block opacity-75"><?= (int) $pend['piezas'] ?> carpeta<?= $pend['piezas'] === 1 ? '' : 's' ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="silo-tarjeta silo-tarjeta-anadir" title="Añadir unidad" onclick="siloAbrirAlta(<?= $nivel ?>)">
            <i class="bi bi-plus-lg"></i>
            <span>Añadir unidad</span>
        </div>
    </div>
<?php endforeach; ?>

<div class="silo-control-footer d-flex justify-content-between align-items-center mb-4">
    <span>Silo / Control de almacenamiento</span>
    <span><?= (int) $totalUnidades ?> unidad<?= $totalUnidades === 1 ? '' : 'es' ?> registrada<?= $totalUnidades === 1 ? '' : 's' ?></span>
</div>

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
