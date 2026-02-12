<?php
$e = $escena ? $escena : [];
$action = $escena
    ? site_url('rodajes/' . $proyecto['id'] . '/escenas/update/' . $escena['id'])
    : site_url('rodajes/' . $proyecto['id'] . '/escenas/store');
?>
<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<style>
    .form-ultra-compact .form-label {
        margin-bottom: 0.15rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #495057;
    }

    .form-ultra-compact .form-control,
    .form-ultra-compact .form-select {
        padding: 0.25rem 0.4rem;
        font-size: 0.8rem;
        min-height: 28px;
    }

    .form-ultra-compact textarea.form-control {
        font-size: 0.8rem;
        line-height: 1.2;
    }

    .form-ultra-compact h4 {
        font-size: 0.9rem;
        margin: 0;
    }

    .form-ultra-compact .small,
    .form-ultra-compact small {
        font-size: 0.7rem;
    }

    .section-header {
        padding: 0.35rem 0.5rem;
        border-left: 3px solid #0d6efd;
        margin-top: 1.75rem;
        border-radius: 2px;
    }

    .btn-xs {
        padding: 0.15rem 0.4rem;
        font-size: 0.75rem;
    }

    .img-thumb {
        height: 120px;
        object-fit: cover;
        border-radius: 3px;
    }

    .form-label{
        color:yellow !important;
    }
</style>

<div class="container-fluid py-2">
    <form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="form-ultra-compact">
        <?= csrf_field() ?>

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 ">
            <h1 class="h5 mb-0"><?= $escena ? 'Editar' : 'Nueva' ?> — <?= esc($proyecto['titulo']) ?></h1>
            <div class="d-flex gap-1">
                <button class="btn btn-xs btn-primary" type="submit">💾</button>
                <?php if ($escena): ?>
                    <a class="btn btn-xs btn-success" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/show/' . $escena['id']) ?>">👁️</a>
                <?php endif; ?>
                <a class="btn btn-xs btn-secondary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas') ?>">↩️</a>
                <?php if (!empty($prevId)): ?>
                    <a class="btn btn-xs btn-outline-secondary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/edit/' . $prevId) ?>">←</a>
                <?php else: ?>
                    <button class="btn btn-xs btn-outline-secondary" disabled>←</button>
                <?php endif; ?>
                <?php if (!empty($nextId)): ?>
                    <a class="btn btn-xs btn-outline-secondary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/edit/' . $nextId) ?>">→</a>
                <?php else: ?>
                    <button class="btn btn-xs btn-outline-secondary" disabled>→</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (session('errors')): ?>
            <div class="alert alert-danger alert-dismissible fade show py-1 mb-2" role="alert">
                <small><?= print_r(session('errors'), true) ?></small>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-2">

            <!-- ESCENA -->
            <div class="col-12">
                <div class="section-header"><strong>ESCENA</strong></div>
            </div>

            <div class="col-6 col-md-1">
                <label class="form-label">Orden</label>
                <input type="number" name="orden" class="form-control" value="<?= old('orden', $e['orden'] ?? 0) ?>" style="max-width: 70px;">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Bloque</label>
                <input type="text" name="escena_bloque" class="form-control" value="<?= old('escena_bloque', $e['escena_bloque'] ?? '') ?>">
            </div>

            <div class="col-6 col-md-5">
                <label class="form-label">Toma/s</label>
                <input type="text" name="escena_tomas" class="form-control" value="<?= old('escena_tomas', $e['escena_tomas'] ?? '') ?>">
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label">Efecto especial</label>
                <input type="text" name="escena_efecto_especial" class="form-control" value="<?= old('escena_efecto_especial', $e['escena_efecto_especial'] ?? '') ?>">
            </div>

            <div class="col-12 col-md-10">
                <label class="form-label">Descripción</label>
                <input type="text" name="escena_descripcion" class="form-control" value="<?= old('escena_descripcion', $e['escena_descripcion'] ?? '') ?>">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Ubicación</label>
                <input type="text" name="escena_ubicacion" class="form-control" value="<?= old('escena_ubicacion', $e['escena_ubicacion'] ?? '') ?>">
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Objetivo narrativo</label>
                <input type="text" name="escena_objetivo" class="form-control" value="<?= old('escena_objetivo', $e['escena_objetivo'] ?? '') ?>">
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Acción</label>
                <input type="text" name="escena_accion" class="form-control" value="<?= old('escena_accion', $e['escena_accion'] ?? '') ?>">
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Objetos en escena</label>
                <input type="text" name="plano_objetos" class="form-control" value="<?= old('plano_objetos', $e['plano_objetos'] ?? '') ?>">
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label">Continuidad previa</label>
                <input type="text" name="escena_cont_previa" class="form-control" value="<?= old('escena_cont_previa', $e['escena_cont_previa'] ?? '') ?>">
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label">Continuidad posterior</label>
                <input type="text" name="escena_cont_posterior" class="form-control" value="<?= old('escena_cont_posterior', $e['escena_cont_posterior'] ?? '') ?>">
            </div>

            <div class="col-12 col-md-12">
                <label class="form-label">Notas</label>
                <input type="text" name="plano_notas" class="form-control" value="<?= old('plano_notas', $e['plano_notas'] ?? '') ?>">
            </div>

            <!-- CÁMARA -->
            <div class="col-12">
                <div class="section-header"><strong>CÁMARA</strong></div>
            </div>

            <div class="col-6 col-md-1">
                <label class="form-label">Cámara</label>
                <input type="text" name="camara_modelo" class="form-control" value="<?= old('camara_modelo', $e['camara_modelo'] ?? 'Sony a7IV') ?>">
            </div>

            <div class="col-6 col-md-1">
                <label class="form-label">Óptica</label>
                <input type="text" name="camara_optica" class="form-control" placeholder="24mm" value="<?= old('camara_optica', $e['camara_optica'] ?? '') ?>">
            </div>

            <div class="col-4 col-md-1">
                <label class="form-label">Apertura</label>
                <input type="text" name="camara_apertura" class="form-control" placeholder="f/2.8" value="<?= old('camara_apertura', $e['camara_apertura'] ?? '') ?>">
            </div>

            <div class="col-2 col-md-1">
                <label class="form-label">FPS</label>
                <input type="text" name="camara_fps" class="form-control" placeholder="24" value="<?= old('camara_fps', $e['camara_fps'] ?? '') ?>">
            </div>

            <div class="col-3 col-md-1">
                <label class="form-label">ISO</label>
                <input type="text" name="camara_iso" class="form-control" placeholder="800" value="<?= old('camara_iso', $e['camara_iso'] ?? '') ?>">
            </div>

            <div class="col-3 col-md-1">
                <label class="form-label">Balance</label>
                <input type="text" name="camara_wb" class="form-control" placeholder="5600K" value="<?= old('camara_wb', $e['camara_wb'] ?? '') ?>">
            </div>

            <div class="col-4 col-md-1">
                <label class="form-label">Velocidad</label>
                <input type="text" name="camara_velocidad" class="form-control" placeholder="1/50" value="<?= old('camara_velocidad', $e['camara_velocidad'] ?? '') ?>">
            </div>

            <div class="col-4 col-md-1">
                <label class="form-label">Filtro ND</label>
                <input type="text" name="camara_nd" class="form-control" placeholder="ND8" value="<?= old('camara_nd', $e['camara_nd'] ?? '') ?>">
            </div>

            <div class="col-6 col-md-1">
                <label class="form-label">Tipo plano</label>
                <select name="camara_tipo_plano" class="form-select">
                    <option value="">--</option>
                    <?php
                    $opt = old('camara_tipo_plano', $e['camara_tipo_plano'] ?? '');
                    $planes = ['General', 'Medio', 'Detalle', 'Primer plano', 'Americano', 'Master'];
                    foreach ($planes as $p):
                    ?>
                        <option value="<?= esc($p) ?>" <?= $opt === $p ? 'selected' : '' ?>><?= esc($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-1">
                <label class="form-label">Ángulo</label>
                <select name="camara_angulo" class="form-select">
                    <option value="">--</option>
                    <?php
                    $opt = old('camara_angulo', $e['camara_angulo'] ?? '');
                    $ang = ['Frontal', 'Picado', 'Cenital', 'Lateral', 'Contrapicado', 'Nadir'];
                    foreach ($ang as $p):
                    ?>
                        <option value="<?= esc($p) ?>" <?= $opt === $p ? 'selected' : '' ?>><?= esc($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-1">
                <label class="form-label">Movimiento</label>
                <select name="camara_movimiento" class="form-select">
                    <option value="">--</option>
                    <?php
                    $opt = old('camara_movimiento', $e['camara_movimiento'] ?? '');
                    $mov = ['Fijo', 'Travelling', 'Paneo', 'Giro', 'Seguimiento', 'Dolly', 'Grúa'];
                    foreach ($mov as $p):
                    ?>
                        <option value="<?= esc($p) ?>" <?= $opt === $p ? 'selected' : '' ?>><?= esc($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-1">
                <label class="form-label">Soporte</label>
                <select name="camara_soporte" class="form-select">
                    <option value="">--</option>
                    <?php
                    $opt = old('camara_soporte', $e['camara_soporte'] ?? '');
                    $sup = ['Trípode', 'Mano', 'Gimbal', 'Otro'];
                    foreach ($sup as $p):
                    ?>
                        <option value="<?= esc($p) ?>" <?= $opt === $p ? 'selected' : '' ?>><?= esc($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- CONSTRUCCIÓN DEL PLANO -->
            <div class="col-12">
                <div class="section-header"><strong>CONSTRUCCIÓN DEL PLANO</strong></div>
            </div>

            <div class="col-12 col-md-12">
                <label class="form-label">Esquema de iluminación</label>
                <input type="text" name="plano_esquema_iluminacion" class="form-control" value="<?= old('plano_esquema_iluminacion', $e['plano_esquema_iluminacion'] ?? '') ?>">
            </div>

            <div class="col-10 col-md-8">
                <label class="form-label">Toma alternativa</label>
                <input type="text" name="plano_toma_alternativa" class="form-control" value="<?= old('plano_toma_alternativa', $e['plano_toma_alternativa'] ?? '') ?>">
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label">Clasificación de rodaje</label>
                <input type="text" name="plano_hora_dia" class="form-control" placeholder="con actores, atrezzo..." value="<?= old('plano_hora_dia', $e['plano_hora_dia'] ?? '') ?>">
            </div>

            <div class="col-2 col-md-1 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="plano_actores" id="plano_actores" value="S" <?= set_checkbox('plano_actores', 'S', ($e['plano_actores'] ?? 'N') === 'S') ?>>
                    <label for="plano_actores" class="form-check-label" style="font-size: 0.7rem;">Actores</label>
                </div>
            </div>

            <!-- SONIDO -->
            <div class="col-6">
                <div class="section-header"><strong>SONIDO</strong></div>
            </div>

            <!-- REFERENCIAS VISUALES -->
            <div class="col-6">
                <div class="section-header"><strong>REFERENCIAS VISUALES</strong></div>
            </div>

            <div class="col-12 col-md-12 d-flex align-items-end">
                <div class="form-check me-3">
                    <input class="form-check-input" type="checkbox" name="sonido_ambiente" id="sonido_ambiente" value="S" <?= set_checkbox('sonido_ambiente', 'S', ($e['sonido_ambiente'] ?? 'N') === 'S') ?>>
                    <label for="sonido_ambiente" class="form-check-label" style="font-size: 0.7rem;">Ambiente</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="sonido_antiviento" id="sonido_antiviento" value="S" <?= set_checkbox('sonido_antiviento', 'S', ($e['sonido_antiviento'] ?? 'N') === 'S') ?>>
                    <label for="sonido_antiviento" class="form-check-label" style="font-size: 0.7rem;">Antiviento</label>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Diálogo escrito</label>
                <textarea name="sonido_dialogo_escrito" class="form-control" rows="8"><?= old('sonido_dialogo_escrito', $e['sonido_dialogo_escrito'] ?? '') ?></textarea>
            </div>

            <div class="col-12 col-md-4">
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label">Lugar y objetos (imágenes)</label>
                        <input type="file" class="form-control" name="lugar_objetos[]" accept="image/*" multiple>
                        <?php if (!empty($imagenes_lugar ?? [])): ?>
                            <div class="d-flex gap-1 mt-1 flex-wrap">
                                <?php foreach ($imagenes_lugar as $img): ?>
                                    <div class="position-relative">
                                        <img class="img-thumb" src="<?= base_url('/' . $img['ruta']) ?>" alt="">
                                        <a class="position-absolute top-0 end-0 btn btn-xs btn-danger m-1"
                                            href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/' . $e['id'] . '/imagen/delete/' . $img['id']) ?>"
                                            onclick="return confirm('¿Eliminar?')"
                                            style="padding: 0.1rem 0.3rem; line-height: 1;">✕</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Inspiración (imágenes)</label>
                        <input type="file" class="form-control" name="inspiracion[]" accept="image/*" multiple>
                        <?php if (!empty($imagenes_insp ?? [])): ?>
                            <div class="d-flex gap-1 mt-1 flex-wrap">
                                <?php foreach ($imagenes_insp as $img): ?>
                                    <div class="position-relative">
                                        <img class="img-thumb" src="<?= base_url('/' . $img['ruta']) ?>" alt="">
                                        <a class="position-absolute top-0 end-0 btn btn-xs btn-danger m-1"
                                            href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/' . $e['id'] . '/imagen/delete/' . $img['id']) ?>"
                                            onclick="return confirm('¿Eliminar?')"
                                            style="padding: 0.1rem 0.3rem; line-height: 1;">✕</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-12 col-md-12">
                        <label class="form-label">Lugar y objetos (enlaces/notas)</label>
                        <textarea name="plano_ref_lugar_texto" class="form-control" rows="3" placeholder="Un enlace o nota por línea"><?= old('plano_ref_lugar_texto', $e['plano_ref_lugar_texto'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12 col-md-12">
                        <label class="form-label">Inspiración (enlaces/notas)</label>
                        <textarea name="plano_ref_inspiracion_texto" class="form-control" rows="3" placeholder="Paletas, planos, referencias de estilo (una por línea)"><?= old('plano_ref_inspiracion_texto', $e['plano_ref_inspiracion_texto'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>





        </div><!-- row -->

        <div class="d-flex gap-1 mt-2 pt-2 ">
            <button class="btn btn-xs btn-primary" type="submit">💾 Guardar</button>
            <?php if ($escena): ?>
                <a class="btn btn-xs btn-success" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/show/' . $escena['id']) ?>">👁️ Ver</a>
            <?php endif; ?>
            <a class="btn btn-xs btn-secondary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas') ?>">↩️ Volver</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>