<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
helper('lectura');
$js = base_url('assets/js/lectura.js') . '?v=' . (@filemtime(FCPATH . 'assets/js/lectura.js') ?: 1);
$id = (int) $libro['id'];
$base = site_url('reading/libro/' . $id);
$estadosUi = lec_estados();
$total = (int) ($libro['total_pages'] ?? 0);
$actual = (int) $libro['current_page'];
$r = $resumen;

$sub = [];
if (!empty($libro['author'])) $sub[] = '<span>' . esc($libro['author']) . '</span>';
$sub[] = $r['ultima']
    ? '<span><i class="bi bi-clock-history"></i> Última vez leído ' . esc(lec_ultima_vez($r['ultima'])) . '</span>'
    : '<span class="t3">Aún sin sesiones</span>';
if (!empty($libro['anchor_routine'])) $sub[] = '<span><i class="bi bi-link-45deg"></i> ' . esc($libro['anchor_routine']) . '</span>';
if ($libro['status'] === 'terminado' && $libro['rating']) $sub[] = lec_estrellas((int) $libro['rating']);
?>
<div class="fv lc">
    <?= view('reading/_cabecera', [
        'migas'  => [$estadosUi[$libro['status']][2] ?? 'Biblioteca' => site_url('reading') . '?tab=' . $libro['status'], $libro['title'] => null],
        'titulo' => esc($libro['title']),
        'sub'    => implode('<span class="fv-dot"></span>', $sub),
    ]) ?>

    <div class="lc-ficha" data-lc-libro>
        <!-- Columna lateral: portada, estado, progreso, datos -->
        <aside class="lc-lado">
            <?= lec_portada($libro) ?>

            <div class="lc-estados" data-lc-estados="<?= $base ?>/estado">
                <?php foreach (['quiero_leer', 'leyendo', 'pausado', 'terminado', 'abandonado'] as $valor): ?>
                    <?php [$ico, $etq] = $estadosUi[$valor]; ?>
                    <button type="button" class="<?= $libro['status'] === $valor ? 'on' : '' ?>" data-status="<?= $valor ?>" title="<?= $etq ?>">
                        <i class="bi <?= $ico ?>"></i><?= $etq ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <section class="fv-panel lc-progreso">
                <div class="grande">
                    <b data-lc-pct><?= $libro['progreso'] !== null ? $libro['progreso'] . '%' : '—' ?></b>
                    <?php if ($total && $actual < $total): ?>
                        <span data-lc-quedan data-total="<?= $total ?>">quedan <?= $total - $actual ?> págs.</span>
                    <?php elseif ($total): ?>
                        <span data-lc-quedan data-total="<?= $total ?>">completo</span>
                    <?php endif; ?>
                </div>
                <?php if ($libro['progreso'] !== null): ?>
                    <div class="lc-barra"><i data-lc-fill style="width:<?= (int) $libro['progreso'] ?>%"></i></div>
                <?php endif; ?>
                <label class="lc-pag">
                    Voy por la pág.
                    <input class="fv-input" type="number" inputmode="numeric" min="0" <?= $total ? 'max="' . $total . '"' : '' ?>
                           value="<?= $actual ?>" data-lc-pagina="<?= $base ?>/pagina">
                    <?php if ($total): ?>de <?= $total ?><?php endif; ?>
                    <i class="bi bi-check2 lc-ok"></i>
                </label>
            </section>

            <section class="fv-panel">
                <dl class="fv-kv">
                    <?php if ($libro['started_at']): ?><div><dt>Empezado</dt><dd><?= esc(lec_fecha($libro['started_at'])) ?></dd></div><?php endif; ?>
                    <?php if ($libro['finished_at']): ?><div><dt><?= $libro['status'] === 'abandonado' ? 'Dejado' : 'Terminado' ?></dt><dd><?= esc(lec_fecha($libro['finished_at'])) ?></dd></div><?php endif; ?>
                    <div><dt>Sesiones</dt><dd><?= $r['sesiones'] ?></dd></div>
                    <?php if ($r['minutos']): ?><div><dt>Tiempo leyendo</dt><dd><?= $r['minutos'] >= 60 ? floor($r['minutos'] / 60) . ' h ' . ($r['minutos'] % 60) . ' min' : $r['minutos'] . ' min' ?></dd></div><?php endif; ?>
                    <?php if ($r['hilo']): ?><div><dt>Perdí el hilo</dt><dd><?= $r['hilo'] ?>×</dd></div><?php endif; ?>
                    <div><dt>Día satisfactorio</dt><dd><?= (int) $libro['min_goal_pages'] ?> pág.</dd></div>
                    <?php if (!empty($libro['isbn'])): ?><div><dt>ISBN</dt><dd class="fv-mono"><?= esc($libro['isbn']) ?></dd></div><?php endif; ?>
                </dl>
            </section>
        </aside>

        <div class="lc-col">
            <!-- Hoy: lo primero que se decide es el sí/no, no un contador -->
            <?php if (!$hoy): ?>
                <div class="lc-hoy-panel" data-lc-dia>
                    <div class="lc-hoy-botones" data-lc-dia-botones>
                        <button type="button" class="lc-cta" data-lc-abrir-sesion>
                            <i class="bi bi-book-half"></i>
                            <div><b>Sí, hoy he leído</b><small>Con <?= (int) $libro['min_goal_pages'] === 1 ? 'una página' : (int) $libro['min_goal_pages'] . ' páginas' ?> ya cuenta</small></div>
                        </button>
                        <button type="button" class="fv-btn" data-lc-notoca="<?= $base ?>/hoy-no-toca" data-recargar>Hoy no toca</button>
                    </div>

                    <form class="fv-panel lc-form" data-lc-sesion="<?= $base ?>/sesion" hidden>
                        <div class="fv-ph"><h3>Sesión de hoy</h3><span class="cuenta">todo opcional</span></div>
                        <div class="lc-fila2">
                            <div class="lc-campo">
                                <label for="lcPag">Página alcanzada</label>
                                <input class="fv-input" id="lcPag" name="page_reached" type="number" inputmode="numeric" min="0" placeholder="<?= $actual ?: '' ?>">
                            </div>
                            <div class="lc-campo">
                                <label for="lcMin">Minutos</label>
                                <input class="fv-input" id="lcMin" name="minutes" type="number" inputmode="numeric" min="0">
                            </div>
                        </div>
                        <div class="lc-campo">
                            <label for="lcNota">Nota rápida</label>
                            <input class="fv-input" id="lcNota" name="note" maxlength="280" placeholder="una línea, lo que sea">
                        </div>
                        <div class="lc-campo">
                            <label for="lcApar">Aparcar un pensamiento</label>
                            <input class="fv-input" id="lcApar" name="parked_thought" maxlength="280" placeholder="lo dejas aquí y sigues leyendo">
                        </div>
                        <div class="lc-form-pie">
                            <span class="lc-hilo">
                                <button type="button" class="fv-btn fv-btn-sm" data-lc-hilo><i class="bi bi-signpost-split"></i>Perdí el hilo</button>
                                <b data-lc-hilo-n></b>
                                <input type="hidden" name="lost_thread_count" value="0">
                            </span>
                            <button type="submit" class="fv-btn fv-btn-pri der">Registrar</button>
                        </div>
                    </form>
                </div>
            <?php elseif ($hoy['skipped']): ?>
                <div class="lc-resuelto neu"><i class="bi bi-moon"></i><div>Hoy no toca.<small>Está bien. Mañana será otro día.</small></div></div>
            <?php else: ?>
                <div class="lc-resuelto"><i class="bi bi-check-circle-fill"></i><div>Hoy ya has tocado este libro.<small>Eso es lo que cuenta.</small></div></div>
            <?php endif; ?>

            <section class="fv-panel">
                <div class="fv-ph"><h3><?= (int) $constancia['tocados'] ?> de los últimos <?= (int) $constancia['dias'] ?> días</h3><span class="cuenta der">relleno = lo tocaste</span></div>
                <?= lec_puntos($constancia['ventana'], true) ?>
            </section>

            <?php if ($pensamientos): ?>
                <section class="fv-panel">
                    <div class="fv-ph"><h3>Pensamientos aparcados</h3><span class="cuenta"><?= count($pensamientos) ?></span></div>
                    <ul class="lc-pens">
                        <?php foreach ($pensamientos as $p): ?>
                            <li><?= esc($p['parked_thought']) ?><small><?= esc(lec_fecha($p['session_date'])) ?><?= $p['page_reached'] ? ' · pág. ' . (int) $p['page_reached'] : '' ?></small></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <section class="fv-panel">
                <div class="fv-ph"><h3>Historial</h3><?php if ($historial): ?><span class="cuenta"><?= $hayMas ? 'últimas ' . count($historial) : count($historial) ?></span><?php endif; ?></div>
                <?php if (!$historial): ?>
                    <p class="fv-vacio">Todavía no hay sesiones. La primera vez que lo toques aparecerá aquí.</p>
                <?php else: ?>
                    <div class="lc-historial">
                        <?php foreach ($historial as $h): ?>
                            <div class="lc-ses<?= $h['skipped'] ? ' salto' : '' ?>">
                                <span class="f"><?= esc(lec_dia_relativo($h['session_date'])) ?></span>
                                <div class="c">
                                    <?php if ($h['skipped']): ?>
                                        <span>Hoy no toca</span>
                                    <?php else: ?>
                                        <?php $dat = array_filter([$h['page_reached'] ? 'pág. ' . (int) $h['page_reached'] : null, $h['minutes'] ? (int) $h['minutes'] . ' min' : null]); ?>
                                        <?php if ($dat): ?><span class="dat"><?= esc(implode(' · ', $dat)) ?></span><?php elseif (empty($h['note']) && empty($h['parked_thought'])): ?><span class="t3">Leído</span><?php endif; ?>
                                        <?php if (!empty($h['note'])): ?><span class="nota"><?= esc($h['note']) ?></span><?php endif; ?>
                                        <?php if (!empty($h['parked_thought'])): ?><span class="apar">Aparcado: «<?= esc($h['parked_thought']) ?>»</span><?php endif; ?>
                                        <?php if (!empty($h['lost_thread_count'])): ?><span class="hilo"><i class="bi bi-signpost-split"></i> Perdí el hilo <?= (int) $h['lost_thread_count'] ?>×</span><?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <details class="fv-panel lc-ajustes">
                <summary><i class="bi bi-sliders"></i>Ajustes del libro<i class="bi bi-chevron-down"></i></summary>
                <form class="lc-form" action="<?= $base ?>/actualizar" method="post" enctype="multipart/form-data" data-lc-ficha-form>
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="<?= esc($libro['status'], 'attr') ?>">

                    <?= view('reading/_buscador', ['libro' => $libro]) ?>

                    <div class="lc-campo">
                        <label for="lcTitulo">Título</label>
                        <input class="fv-input" id="lcTitulo" name="title" required maxlength="255" value="<?= esc($libro['title'], 'attr') ?>">
                    </div>
                    <div class="lc-campo">
                        <label for="lcAutor">Autor</label>
                        <input class="fv-input" id="lcAutor" name="author" maxlength="255" value="<?= esc($libro['author'] ?? '', 'attr') ?>">
                    </div>
                    <div class="lc-fila2">
                        <div class="lc-campo">
                            <label for="lcTotal">Páginas totales</label>
                            <input class="fv-input" id="lcTotal" name="total_pages" type="number" min="1" value="<?= $total ?: '' ?>">
                        </div>
                        <div class="lc-campo">
                            <label for="lcMinimo">Día satisfactorio (págs.)</label>
                            <input class="fv-input" id="lcMinimo" name="min_goal_pages" type="number" min="1" value="<?= (int) $libro['min_goal_pages'] ?>">
                        </div>
                    </div>
                    <div class="lc-campo">
                        <label for="lcAncla">Rutina ancla</label>
                        <input class="fv-input" id="lcAncla" name="anchor_routine" maxlength="255" value="<?= esc($libro['anchor_routine'] ?? '', 'attr') ?>" placeholder="ej: café de la mañana, antes de dormir…">
                    </div>
                    <?php if ($libro['status'] === 'terminado'): ?>
                        <div class="lc-campo">
                            <span class="lc-lbl">Valoración (opcional)</span>
                            <div class="lc-valorar" data-lc-valorar>
                                <input type="hidden" name="rating" value="<?= esc((string) ($libro['rating'] ?? ''), 'attr') ?>">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <button type="button" data-v="<?= $i ?>" class="<?= $i <= (int) $libro['rating'] ? 'on' : '' ?>" title="<?= $i ?>"><i class="bi <?= $i <= (int) $libro['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i></button>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="lc-form-pie">
                        <button type="submit" class="fv-btn fv-btn-pri">Guardar ajustes</button>
                    </div>
                </form>
                <form action="<?= $base ?>/borrar" method="post" onsubmit="return confirm('¿Eliminar este libro y todo su historial de lectura?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="fv-btn fv-btn-sm fv-btn-peligro"><i class="bi bi-trash"></i>Eliminar libro</button>
                </form>
            </details>
        </div>
    </div>
</div>
<script src="<?= $js ?>"></script>
<?= $this->endSection() ?>
