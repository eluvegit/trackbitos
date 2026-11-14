<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php helper('gimnasio'); $js = base_url('assets/js/gimnasio.js') . '?v=' . (@filemtime(FCPATH . 'assets/js/gimnasio.js') ?: 1); ?>
<div class="fv gm">
    <?= view('gimnasio/_cabecera', ['activa' => 'catalogo',
        'migas' => ['Catálogo' => site_url('gimnasio/catalogo'), ucfirst($c['nombre']) => null],
        'titulo' => esc(ucfirst($c['nombre'])),
        'sub' => '<span class="gm-chip"><i class="bi bi-bullseye"></i>' . esc(gim_catalogo_etiqueta('objetivo', $c['objetivo'])) . '</span>'
            . '<span class="gm-chip"><i class="bi bi-person"></i>' . esc(gim_catalogo_etiqueta('parte', $c['parte'])) . '</span>'
            . '<span class="gm-chip"><i class="bi bi-tools"></i>' . esc(gim_catalogo_etiqueta('equipo', $c['equipo'])) . '</span>']) ?>

    <div class="gm-ficha">
        <div>
            <div class="gm-ficha-img"><img src="<?= esc(gim_catalogo_media($c['gif'])) ?>" alt="<?= esc($c['nombre']) ?>"></div>
            <div class="gm-credito">© <a href="https://gymvisual.com/" target="_blank" rel="noopener">Gym visual</a></div>
        </div>
        <div style="display:grid;gap:20px">
            <section class="fv-panel">
                <div class="fv-ph"><h3>Cómo se hace</h3></div>
                <?php if ($pasos): ?>
                    <ol class="gm-pasos"><?php foreach ($pasos as $p): ?><li><?= esc($p) ?></li><?php endforeach; ?></ol>
                <?php elseif ($c['instrucciones_es']): ?>
                    <p style="color:var(--fv-text-2)"><?= esc($c['instrucciones_es']) ?></p>
                <?php endif; ?>
                <?php if ($c['secundarios']): ?><p class="gm-ayuda">Secundarios: <?= esc($c['secundarios']) ?></p><?php endif; ?>
                <?php if ($c['instrucciones_en']): ?>
                    <details class="gm-mas"><summary><i class="bi bi-chevron-down"></i> Original en inglés</summary><p class="gm-ayuda" style="margin-top:8px"><?= esc($c['instrucciones_en']) ?></p></details>
                <?php endif; ?>
            </section>

            <section class="fv-panel">
                <div class="fv-ph"><h3>En mis ejercicios</h3></div>
                <?php if ($vinculados): ?>
                    <div class="gm-filas">
                        <?php foreach ($vinculados as $v): ?>
                            <div class="gm-fila">
                                <i class="bi bi-check-circle-fill" style="color:var(--fv-ok)"></i>
                                <a class="pp" href="<?= site_url('gimnasio/ejercicios/estadisticas/' . $v['id']) ?>"><span class="t"><?= esc($v['nombre']) ?></span><span class="d"><?= esc(gim_grupo_nombre($v['grupo_muscular'])) ?></span></a>
                                <form action="<?= site_url('gimnasio/catalogo/desvincular/' . $v['id']) ?>" method="post"><button class="fv-gbtn" title="Quitar vínculo (no borra tu ejercicio)"><i class="bi bi-x-circle"></i> Desvincular</button></form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="fv-vacio">Todavía no está en tus ejercicios.</p>
                <?php endif; ?>

                <form action="<?= site_url('gimnasio/catalogo/' . $c['id'] . '/vincular') ?>" method="post" class="gm-campo">
                    <label for="cvEj">Es uno que ya tengo</label>
                    <div class="gm-nueva">
                        <select id="cvEj" name="ejercicio_id" class="gm-sel" required style="flex:1;min-width:0">
                            <option value="">Elige tu ejercicio…</option>
                            <?php $grupoActual = null; foreach ($misEjercicios as $e): ?>
                                <?php if ($e['grupo_muscular'] !== $grupoActual): ?><?= $grupoActual !== null ? '</optgroup>' : '' ?><optgroup label="<?= esc(gim_grupo_nombre($e['grupo_muscular'])) ?>"><?php $grupoActual = $e['grupo_muscular']; endif; ?>
                                <option value="<?= $e['id'] ?>"><?= esc($e['nombre']) ?><?= $e['catalogo_id'] ? ' ·  ya vinculado' : '' ?></option>
                            <?php endforeach; ?>
                            <?= $grupoActual !== null ? '</optgroup>' : '' ?>
                        </select>
                        <button class="fv-btn"><i class="bi bi-link"></i> Vincular</button>
                    </div>
                    <span class="gm-ayuda">Le añade la animación e instrucciones; no cambia nada más.</span>
                </form>

                <form action="<?= site_url('gimnasio/catalogo/' . $c['id'] . '/adoptar') ?>" method="post" class="gm-campo">
                    <label for="caN">O añadirlo como ejercicio nuevo</label>
                    <div class="gm-nueva" style="flex-wrap:wrap">
                        <input type="text" id="caN" name="nombre" class="fv-input" maxlength="100" value="<?= esc(ucfirst($c['nombre'])) ?>" required style="min-width:180px">
                        <select name="grupo_muscular" class="gm-sel" style="width:auto">
                            <?php foreach ($grupos as $k => $v): ?><option value="<?= esc($k) ?>" <?= $k === $grupoSugerido ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?>
                        </select>
                        <button class="fv-btn fv-btn-pri"><i class="bi bi-plus-lg"></i> Añadir</button>
                    </div>
                    <span class="gm-ayuda">Ponle el nombre que usarías tú (p. ej. en español).</span>
                </form>
            </section>
        </div>
    </div>
</div>
<script src="<?= $js ?>"></script>
<?= $this->endSection() ?>
