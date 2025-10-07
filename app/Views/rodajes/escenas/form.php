<?php
$e = $escena ? $escena : [];
$action = $escena
    ? site_url('rodajes/' . $proyecto['id'] . '/escenas/update/' . $escena['id'])
    : site_url('rodajes/' . $proyecto['id'] . '/escenas/store');
?>
<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="container py-4">
    <form method="post" action="<?= $action ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1><?= $escena ? 'Editar escena' : 'Nueva escena' ?> — <?= esc($proyecto['titulo']) ?></h1>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Guardar escena</button>
                <?php if ($escena): ?>
                    <a class="btn btn-success" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/show/' . $escena['id']) ?>">Ver</a>
                <?php endif; ?>
                <a class="btn btn-secondary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas') ?>">Volver</a>

                <?php if (!empty($prevId)): ?>
                    <a class="btn btn-outline-secondary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/edit/' . $prevId) ?>">← Anterior</a>
                <?php else: ?>
                    <button class="btn btn-outline-secondary" type="button" disabled>← Anterior</button>
                <?php endif; ?>

                <?php if (!empty($nextId)): ?>
                    <a class="btn btn-outline-secondary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/edit/' . $nextId) ?>">Siguiente →</a>
                <?php else: ?>
                    <button class="btn btn-outline-secondary" type="button" disabled>Siguiente →</button>
                <?php endif; ?>

            </div>
        </div>

        <?php if (session('errors')): ?>
            <div class="alert alert-danger">
                <pre class="mb-0"><?= print_r(session('errors'), true) ?></pre>
            </div>
        <?php endif; ?>



        <div class="row g-3">
            <!-- ORDEN -->
            <div class="col-12 col-md-2">
                <label class="form-label">Orden</label>
                <input type="number" name="orden" class="form-control" value="<?= old('orden', $e['orden'] ?? 0) ?>">
            </div>

            <!-- ESCENA -->
            <div class="col-12">
                <h4 class="mt-3">ESCENA</h4>
                <hr>
            </div>

            <div class="col-md-4">
                <label class="form-label">Bloque</label>
                <input type="text" name="escena_bloque" class="form-control" value="<?= old('escena_bloque', $e['escena_bloque'] ?? '') ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Toma/s</label>
                <input type="text" name="escena_tomas" class="form-control" value="<?= old('escena_tomas', $e['escena_tomas'] ?? '') ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Ubicación</label>
                <input type="text" name="escena_ubicacion" class="form-control" value="<?= old('escena_ubicacion', $e['escena_ubicacion'] ?? '') ?>">
            </div>

            <div class="col-12">
                <label class="form-label">Notas</label>
                <textarea name="plano_notas" class="form-control" rows="2"><?= old('plano_notas', $e['plano_notas'] ?? '') ?></textarea>
            </div>

            <div class="col-12">
                <label class="form-label">Descripción</label>
                <textarea name="escena_descripcion" class="form-control" rows="2"><?= old('escena_descripcion', $e['escena_descripcion'] ?? '') ?></textarea>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Objetivo narrativo</label>
                <textarea name="escena_objetivo" class="form-control" rows="2"><?= old('escena_objetivo', $e['escena_objetivo'] ?? '') ?></textarea>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label">Acción</label>
                <textarea name="escena_accion" class="form-control" rows="2"><?= old('escena_accion', $e['escena_accion'] ?? '') ?></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label">Efecto especial</label>
                <input type="text" name="escena_efecto_especial" class="form-control" value="<?= old('escena_efecto_especial', $e['escena_efecto_especial'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Continuidad con escena previa</label>
                <textarea name="escena_cont_previa" class="form-control" rows="2"><?= old('escena_cont_previa', $e['escena_cont_previa'] ?? '') ?></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label">Continuidad con escena posterior</label>
                <textarea name="escena_cont_posterior" class="form-control" rows="2"><?= old('escena_cont_posterior', $e['escena_cont_posterior'] ?? '') ?></textarea>
            </div>

            <!-- CÁMARA -->
            <div class="col-12">
                <h4 class="mt-4">CÁMARA</h4>
                <hr>
            </div>

            <div class="col-md-4">
                <label class="form-label">Cámara</label>
                <input type="text" name="camara_modelo" class="form-control" value="<?= old('camara_modelo', $e['camara_modelo'] ?? '') ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Óptica</label>
                <input type="text" name="camara_optica" class="form-control" value="<?= old('camara_optica', $e['camara_optica'] ?? '') ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Apertura / Prof. de campo</label>
                <input type="text" name="camara_apertura" class="form-control" value="<?= old('camara_apertura', $e['camara_apertura'] ?? '') ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Framerate (FPS)</label>
                <input type="text" name="camara_fps" class="form-control" value="<?= old('camara_fps', $e['camara_fps'] ?? '') ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Velocidad</label>
                <input type="text" name="camara_velocidad" class="form-control" value="<?= old('camara_velocidad', $e['camara_velocidad'] ?? '') ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">ISO</label>
                <input type="text" name="camara_iso" class="form-control" value="<?= old('camara_iso', $e['camara_iso'] ?? '') ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Balance de blancos</label>
                <input type="text" name="camara_wb" class="form-control" value="<?= old('camara_wb', $e['camara_wb'] ?? '') ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Filtro ND</label>
                <input type="text" name="camara_nd" class="form-control" value="<?= old('camara_nd', $e['camara_nd'] ?? '') ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Tipo de plano</label>
                <select name="camara_tipo_plano" class="form-select">
                    <?php
                    $opt = old('camara_tipo_plano', $e['camara_tipo_plano'] ?? '');
                    $planes = ['General', 'Medio', 'Detalle', 'Primer plano', 'Americano', 'Master'];
                    foreach ($planes as $p) {
                        $sel = ($opt === $p) ? 'selected' : '';
                        echo "<option $sel>$p</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Ángulo</label>
                <select name="camara_angulo" class="form-select">
                    <?php
                    $opt = old('camara_angulo', $e['camara_angulo'] ?? '');
                    $ang = ['Frontal', 'Picado', 'Cenital', 'Lateral', 'Contrapicado', 'Nadir'];
                    foreach ($ang as $p) {
                        $sel = ($opt === $p) ? 'selected' : '';
                        echo "<option $sel>$p</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Movimiento</label>
                <select name="camara_movimiento" class="form-select">
                    <?php
                    $opt = old('camara_movimiento', $e['camara_movimiento'] ?? '');
                    $mov = ['Fijo', 'Travelling', 'Paneo', 'Giro', 'Seguimiento', 'Dolly', 'Grúa'];
                    foreach ($mov as $p) {
                        $sel = ($opt === $p) ? 'selected' : '';
                        echo "<option $sel>$p</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Trípode / mano / gimbal / otro</label>
                <select name="camara_soporte" class="form-select">
                    <?php
                    $opt = old('camara_soporte', $e['camara_soporte'] ?? '');
                    $sup = ['Trípode', 'Mano', 'Gimbal', 'Otro'];
                    foreach ($sup as $p) {
                        $sel = ($opt === $p) ? 'selected' : '';
                        echo "<option $sel>$p</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- CONSTRUCCIÓN DEL PLANO -->
            <div class="col-12">
                <h4 class="mt-4">CONSTRUCCIÓN DEL PLANO</h4>
                <hr>
            </div>

            <div class="col-md-6">
                <label class="form-label">Esquema de iluminación</label>
                <textarea name="plano_esquema_iluminacion" class="form-control" rows="3"><?= old('plano_esquema_iluminacion', $e['plano_esquema_iluminacion'] ?? '') ?></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label">Hora del día</label>
                <select name="plano_hora_dia" class="form-select">
                    <?php
                    $opt = old('plano_hora_dia', $e['plano_hora_dia'] ?? '');
                    $horas = ['Noche', 'Tarde', 'Día', 'Amanecer', 'Atardecer', 'Interior'];
                    foreach ($horas as $p) {
                        $sel = ($opt === $p) ? 'selected' : '';
                        echo "<option $sel>$p</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Objetos en escena</label>
                <textarea name="plano_objetos" class="form-control" rows="2"><?= old('plano_objetos', $e['plano_objetos'] ?? '') ?></textarea>
            </div>

            <div class="col-md-3 form-check mt-2">
                <input class="form-check-input" type="checkbox" name="plano_actores" id="plano_actores"
                    <?= old('plano_actores', ($e['plano_actores'] ?? 'N') === 'S' ? 'checked' : '') ?>>
                <label for="plano_actores" class="form-check-label">Actores (sí)</label>
            </div>

            <div class="col-12">
                <label class="form-label">Toma alternativa</label>
                <textarea name="plano_toma_alternativa" class="form-control" rows="2"><?= old('plano_toma_alternativa', $e['plano_toma_alternativa'] ?? '') ?></textarea>
            </div>

            

            <!-- SONIDO -->
            <div class="col-12">
                <h4 class="mt-4">SONIDO</h4>
                <hr>
            </div>

            <div class="col-md-3 form-check mt-2">
                <input class="form-check-input" type="checkbox" name="sonido_ambiente" id="sonido_ambiente"
                    <?= old('sonido_ambiente', ($e['sonido_ambiente'] ?? 'N') === 'S' ? 'checked' : '') ?>>
                <label for="sonido_ambiente" class="form-check-label">Sonido ambiente (sí)</label>
            </div>

            <div class="col-md-3 form-check mt-2">
                <input class="form-check-input" type="checkbox" name="sonido_antiviento" id="sonido_antiviento"
                    <?= old('sonido_antiviento', ($e['sonido_antiviento'] ?? 'N') === 'S' ? 'checked' : '') ?>>
                <label for="sonido_antiviento" class="form-check-label">Antiviento (sí)</label>
            </div>

            <div class="col-12">
                <label class="form-label">Diálogo escrito</label>
                <textarea name="sonido_dialogo_escrito" class="form-control" rows="3"><?= old('sonido_dialogo_escrito', $e['sonido_dialogo_escrito'] ?? '') ?></textarea>
            </div>

            <!-- REFERENCIAS VISUALES -->
            <div class="col-12">
                <h4 class="mt-4">Referencias visuales</h4>
                <hr>
            </div>

            <!-- Texto: enlaces y notas de referencia -->
            <div class="col-md-6">
                <label class="form-label">Referencia (texto) — Lugar y objetos</label>
                <textarea name="plano_ref_lugar_texto" class="form-control" rows="4"
                    placeholder="Enlaces y notas (uno por línea). Ej: https://youtu.be/..., https://mi-moodboard.com/..."><?= old('plano_ref_lugar_texto', $e['plano_ref_lugar_texto'] ?? '') ?></textarea>
                <small class="text-muted">Pon aquí links a vídeos/imágenes o notas rápidas. Se mostrarán en la vista de rodaje.</small>
            </div>

            <div class="col-md-6">
                <label class="form-label">Referencia (texto) — Inspiración</label>
                <textarea name="plano_ref_inspiracion_texto" class="form-control" rows="4"
                    placeholder="Enlaces y notas (uno por línea). Ej: paletas de color, planos, referencias de estilo"><?= old('plano_ref_inspiracion_texto', $e['plano_ref_inspiracion_texto'] ?? '') ?></textarea>
                <small class="text-muted">Útil para paletas, planos de inspiración, BTS, etc.</small>
            </div>

            <!-- Archivos: galerías por categoría -->
            <div class="col-md-6">
                <label class="form-label mt-2">Lugar y objetos (múltiples imágenes)</label>
                <input type="file" class="form-control" name="lugar_objetos[]" accept="image/*" multiple>
                <?php if (!empty($imagenes_lugar ?? [])): ?>
                    <div class="row g-2 mt-2">
                        <?php foreach ($imagenes_lugar as $img): ?>
                            <div class="col-6 col-md-3">
                                <div class="card">
                                    <img class="card-img-top" src="<?= base_url('/' . $img['ruta']) ?>" alt="">
                                    <div class="card-body p-2 text-center">
                                        <a class="btn btn-sm btn-outline-danger"
                                            href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/' . ($e['id'] ?? $escena['id']) . '/imagen/delete/' . $img['id']) ?>"
                                            onclick="return confirm('¿Eliminar imagen?')">Eliminar</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label mt-2">Inspiración (múltiples imágenes)</label>
                <input type="file" class="form-control" name="inspiracion[]" accept="image/*" multiple>
                <?php if (!empty($imagenes_insp ?? [])): ?>
                    <div class="row g-2 mt-2">
                        <?php foreach ($imagenes_insp as $img): ?>
                            <div class="col-6 col-md-3">
                                <div class="card">
                                    <img class="card-img-top" src="<?= base_url('/' . $img['ruta']) ?>" alt="">
                                    <div class="card-body p-2 text-center">
                                        <a class="btn btn-sm btn-outline-danger"
                                            href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/' . ($e['id'] ?? $escena['id']) . '/imagen/delete/' . $img['id']) ?>"
                                            onclick="return confirm('¿Eliminar imagen?')">Eliminar</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>


        </div><!-- row -->

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary" type="submit">Guardar escena</button>
            <?php if ($escena): ?>
                <a class="btn btn-success" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/show/' . $escena['id']) ?>">Ver</a>
            <?php endif; ?>
            <a class="btn btn-secondary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas') ?>">Volver</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>