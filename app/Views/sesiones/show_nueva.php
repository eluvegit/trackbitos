<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Ficha de sesión (estilo fv). Etapas arriba (un toque para cambiar) y el
 * contenido en pestañas: Plan · Moodboard · Equipo · Modelos · Rodaje.
 * Todo se guarda sin recargar (assets/js/sesiones.js).
 *
 * @var array $sesion, $situaciones, $moodboard_por_situacion, $equipo,
 *            $model_releases, $mensajes_modelo, $rodajes, $equipo_de_otras
 */
$s   = $sesion;
$sid = (int) $s['id'];
$estadosUi = ses_estados();
$orden     = array_keys($estadosUi);
$partesUi  = ses_partes();
$entregaUi = ses_entrega();
$pausada   = (int) $s['pausada'] === 1;
$js  = base_url('assets/js/sesiones.js') . '?v=' . (@filemtime(FCPATH . 'assets/js/sesiones.js') ?: 1);

$nMood   = array_sum(array_map('count', $moodboard_por_situacion));
$nEquipo = count($equipo);
$nEqOk   = count(array_filter($equipo, static fn ($i) => (int) $i['marcado'] === 1));
// "Qué llevar" en dos listas.
$listas = [
    'equipo' => ['Equipo', 'bi-camera', 'Cámara, ópticas, luces, trípode…'],
    'props'  => ['Props', 'bi-box2-heart', 'Atrezzo, vestuario, objetos…'],
];
$porTipo = array_fill_keys(array_keys($listas), []);
foreach ($equipo as $it) $porTipo[($it['tipo'] ?? 'equipo') === 'props' ? 'props' : 'equipo'][] = $it;
$nModelos = count($model_releases) + count($mensajes_modelo);

// Briefing: HTML de Quill; si es texto plano antiguo, se envuelve en <p> por línea.
$briefingHtml = trim((string) ($s['briefing'] ?? ''));
if ($briefingHtml !== '' && strip_tags($briefingHtml) === $briefingHtml) {
    $briefingHtml = implode('', array_map(static fn ($l) => '<p>' . esc($l) . '</p>', preg_split('/\r\n|\r|\n/', $briefingHtml)));
}

$sub = [];
if ($s['fecha_sesion']) $sub[] = '<span title="' . esc($s['fecha_sesion'], 'attr') . '"><i class="bi bi-calendar3"></i> ' . esc(ses_fecha_relativa($s['fecha_sesion'])) . ' · ' . esc(date('d/m/Y', strtotime($s['fecha_sesion']))) . '</span>';
else $sub[] = '<span class="t3"><i class="bi bi-calendar3"></i> sin fecha</span>';
$sub[] = '<span id="ssPausaChip" class="e-warn"' . ($pausada ? '' : ' hidden') . '><i class="bi bi-pause-fill"></i> Pausada</span>';
if ($s['entrega_modelos'] === 'pendiente') $sub[] = '<span class="e-warn"><i class="bi bi-send"></i> Pendiente de entregar a modelos</span>';

ob_start(); ?>
<a class="fv-btn" href="<?= site_url("sesiones/$sid/editar") ?>"><i class="bi bi-pencil"></i>Editar</a>
<details class="ss-menu">
    <summary class="fv-btn" title="Más"><i class="bi bi-three-dots"></i></summary>
    <div class="ss-menu-pop">
        <button type="button" data-accion="pausa"><i class="bi <?= $pausada ? 'bi-play-fill' : 'bi-pause-fill' ?>"></i><span><?= $pausada ? 'Reanudar sesión' : 'Pausar sesión' ?></span></button>
        <a href="<?= site_url("sesiones/$sid/exportar") ?>" target="_blank"><i class="bi bi-printer"></i>Exportar informe</a>
        <hr>
        <form method="post" action="<?= site_url("sesiones/$sid/borrar") ?>" onsubmit="return confirm('¿Borrar esta sesión? Se perderá todo su contenido (moodboard, equipo, model releases, historial). No se puede deshacer.')">
            <?= csrf_field() ?>
            <button type="submit" class="peligro"><i class="bi bi-trash"></i>Borrar sesión</button>
        </form>
    </div>
</details>
<?php $acciones = ob_get_clean(); ?>

<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">

<div class="fv ss" id="ssFicha"
     data-base="<?= site_url("sesiones/$sid") ?>"
     data-csrf="<?= csrf_hash() ?>"
     data-media="<?= rtrim(base_url(), '/') . '/' ?>"
     data-titulo="<?= esc($s['titulo'], 'attr') ?>"
     data-fecha="<?= esc($s['fecha_sesion'] ?? '', 'attr') ?>"
     data-estados='<?= esc(json_encode(array_map(static fn ($e) => ['t' => $e[0], 'c' => $e[2]], $estadosUi), JSON_UNESCAPED_UNICODE), 'attr') ?>'>

    <?= view('sesiones/_cabecera', [
        'migas'    => [$s['titulo'] => null],
        'titulo'   => esc($s['titulo']),
        'sub'      => implode('', $sub),
        'acciones' => $acciones,
    ]) ?>

    <!-- Etapas -->
    <section class="fv-panel ss-etapas<?= $pausada ? ' pausada' : '' ?>">
        <?php foreach ($partesUi as $parte => [$nombre, $ico, $colorP]): ?>
            <?php $est = $s['estado_' . $parte]; if ($est === null) continue; $idx = array_search($est, $orden, true); ?>
            <div class="ss-etapa" data-parte="<?= $parte ?>" style="--c: <?= $estadosUi[$est][2] ?>; --p: <?= $colorP ?>">
                <div class="ss-etapa-nom"><i class="bi <?= $ico ?>"></i><?= $nombre ?></div>
                <div class="ss-stepper">
                    <?php foreach ($orden as $i => $e): ?>
                        <button type="button" data-estado="<?= $e ?>" class="<?= $i < $idx ? 'hecho' : ($i === $idx ? 'actual' : '') ?>">
                            <i class="bi <?= $estadosUi[$e][1] ?>"></i><span><?= $estadosUi[$e][0] ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- Pestañas -->
    <div class="fv-secs ss-secs" id="ssSecs">
        <button type="button" data-tab="plan" aria-pressed="true"><i class="bi bi-journal-text"></i>Plan</button>
        <button type="button" data-tab="moodboard" aria-pressed="false"><i class="bi bi-images"></i>Moodboard <span data-cuenta="moodboard"><?= $nMood ?></span></button>
        <button type="button" data-tab="equipo" aria-pressed="false"><i class="bi bi-bag-check"></i>Qué llevar <span data-cuenta="equipo"><?= $nEqOk ?>/<?= $nEquipo ?></span></button>
        <button type="button" data-tab="modelos" aria-pressed="false"><i class="bi bi-person-badge"></i>Modelos <span data-cuenta="modelos"><?= $nModelos ?></span></button>
        <button type="button" data-tab="rodaje" aria-pressed="false"><i class="bi bi-film"></i>Rodaje <?php if ($rodajes): ?><span><?= count($rodajes) ?></span><?php endif; ?></button>
    </div>

    <!-- PLAN -->
    <div class="ss-tab" data-panel="plan">
        <div class="ss-dos">
            <section class="fv-panel">
                <div class="fv-ph">
                    <h3>Briefing</h3>
                    <span class="ss-guardado" data-guardado="briefing"></span>
                    <a class="fv-link der" href="<?= site_url("sesiones/$sid/exportar") ?>" target="_blank"><i class="bi bi-printer"></i>Informe</a>
                </div>
                <div class="ss-quill"><div id="ssBriefing"><?= $briefingHtml ?></div></div>
                <div class="ss-pie-panel">
                    <span class="ss-ayuda">Va en el informe exportable (para pasárselo a la modelo).</span>
                    <button type="button" class="fv-btn fv-btn-sm fv-btn-pri" id="ssGuardarBriefing">Guardar briefing</button>
                </div>
            </section>
            <section class="fv-panel">
                <div class="fv-ph"><h3>Notas</h3><span class="ss-guardado" data-guardado="notas"></span></div>
                <textarea class="fv-input ss-notas" id="ssNotas" placeholder="Apuntes rápidos: se guardan solos al salir del cuadro."><?= esc($s['notas'] ?? '') ?></textarea>
            </section>
        </div>
    </div>

    <!-- MOODBOARD -->
    <div class="ss-tab" data-panel="moodboard" hidden>
        <div class="ss-mb-herr">
            <form class="ss-nueva-sit" id="ssNuevaSit">
                <i class="bi bi-plus-lg"></i><input type="text" name="nombre" maxlength="150" placeholder="Nueva situación (p. ej. «Cocina, luz de ventana»)" required>
            </form>
            <a class="fv-btn fv-btn-sm" href="<?= site_url("sesiones/$sid/exportar") ?>" target="_blank"><i class="bi bi-printer"></i>Exportar</a>
        </div>
        <p class="ss-ayuda"><i class="bi bi-lightbulb"></i> Arrastra imágenes encima de un grupo, o pulsa en él y pega con Ctrl+V. Las situaciones se reordenan arrastrando su asa.</p>

        <div class="ss-grupos" id="ssGrupos">
            <?php
            $grupos = [['id' => '', 'nombre' => 'General', 'items' => $moodboard_por_situacion['general'] ?? []]];
            foreach ($situaciones as $sit) $grupos[] = ['id' => (int) $sit['id'], 'nombre' => $sit['nombre'], 'items' => $moodboard_por_situacion[$sit['id']] ?? []];
            ?>
            <?php foreach ($grupos as $g): ?>
                <?= view('sesiones/_mb_grupo_nueva', ['g' => $g, 'sid' => $sid]) ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- EQUIPO -->
    <div class="ss-tab" data-panel="equipo" hidden>
        <div class="ss-dos" id="ssEquipo">
            <?php foreach ($listas as $tipo => [$nomLista, $icoLista, $phLista]): ?>
                <?php $its = $porTipo[$tipo]; $ok = count(array_filter($its, static fn ($i) => (int) $i['marcado'] === 1)); ?>
                <section class="fv-panel ss-equipo" data-tipo="<?= $tipo ?>">
                    <div class="fv-ph">
                        <i class="bi <?= $icoLista ?> t3"></i>
                        <h3 class="t1"><?= $nomLista ?></h3>
                        <span class="cuenta der" data-eq-cuenta><?= $ok ?>/<?= count($its) ?></span>
                    </div>
                    <div class="ss-barra<?= $its && $ok === count($its) ? ' ok' : '' ?>"><i data-eq-barra style="width:<?= $its ? round($ok / count($its) * 100) : 0 ?>%"></i></div>
                    <div class="ss-eq-lista">
                        <?php foreach ($its as $it): ?>
                            <label class="fv-check<?= (int) $it['marcado'] === 1 ? ' hecha' : '' ?>" data-id="<?= (int) $it['id'] ?>">
                                <input type="checkbox" <?= (int) $it['marcado'] === 1 ? 'checked' : '' ?>>
                                <span><?= esc($it['item']) ?></span>
                                <button type="button" class="deshacer ss-eq-editar" title="Editar"><i class="bi bi-pencil"></i></button>
                                <button type="button" class="deshacer ss-eq-mover" title="Pasar a <?= $tipo === 'props' ? 'Equipo' : 'Props' ?>"><i class="bi bi-arrow-left-right"></i></button>
                                <button type="button" class="deshacer ss-eq-borrar" title="Quitar"><i class="bi bi-x-lg"></i></button>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <form class="fv-nueva ss-eq-nuevo">
                        <i class="bi bi-plus-lg"></i><input type="text" name="item" maxlength="150" placeholder="<?= $phLista ?>" required>
                    </form>
                </section>
            <?php endforeach; ?>
        </div>
        <section class="fv-panel">
            <p class="ss-ayuda"><i class="bi bi-lightbulb"></i> Puedes pegar una lista de varias líneas de golpe: se crea un ítem por línea. Con <i class="bi bi-arrow-left-right" style="color:inherit"></i> pasas un ítem a la otra lista.</p>
            <?php if (!empty($equipo_de_otras)): ?>
                <div class="ss-copiar">
                    <span class="ss-ayuda">Cargar las listas de otra sesión:</span>
                    <select class="form-select ss-sel" id="ssEqDesde">
                        <?php foreach ($equipo_de_otras as $o): ?>
                            <option value="<?= (int) $o['id'] ?>"><?= esc($o['titulo']) ?> (<?= (int) $o['n'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="fv-btn fv-btn-sm" id="ssEqCopiar"><i class="bi bi-copy"></i>Copiar</button>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- MODELOS -->
    <div class="ss-tab" data-panel="modelos" hidden>
        <section class="fv-panel">
            <div class="fv-ph"><h3>Entrega del material a modelos</h3></div>
            <div class="ss-seg ss-entrega" id="ssEntrega">
                <?php foreach ($entregaUi as $k => [$txt, $cls]): ?>
                    <button type="button" data-valor="<?= $k ?>" class="<?= $cls ?><?= $s['entrega_modelos'] === $k ? ' on' : '' ?>"><?= $txt ?></button>
                <?php endforeach; ?>
            </div>
        </section>
        <div class="ss-dos">
            <section class="fv-panel">
                <div class="fv-ph"><h3>Model releases</h3><span class="cuenta" id="ssRelCuenta"><?= count($model_releases) ?></span></div>
                <ul class="ss-filas-lista" id="ssReleases">
                    <?php foreach ($model_releases as $r): ?>
                        <li data-id="<?= (int) $r['id'] ?>">
                            <i class="bi bi-file-earmark-check"></i>
                            <a href="<?= base_url($r['ruta_archivo']) ?>" target="_blank" rel="noopener"><?= esc($r['nombre_modelo']) ?></a>
                            <button type="button" class="fv-ibtn ss-rel-borrar" title="Borrar"><i class="bi bi-trash"></i></button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <form class="ss-form-mini" id="ssRelForm">
                    <input class="fv-input" name="nombre_modelo" placeholder="Nombre del modelo" required>
                    <label class="fv-btn fv-btn-sm ss-archivo"><input type="file" name="archivo" accept="image/*,application/pdf" required><i class="bi bi-paperclip"></i><span>Archivo</span></label>
                    <button type="submit" class="fv-btn fv-btn-sm fv-btn-pri"><i class="bi bi-upload"></i>Subir</button>
                </form>
            </section>
            <section class="fv-panel">
                <div class="fv-ph"><h3>Mensajes a modelos</h3><span class="cuenta" id="ssMsgCuenta"><?= count($mensajes_modelo) ?></span></div>
                <div class="ss-mensajes" id="ssMensajes">
                    <?php foreach ($mensajes_modelo as $m): ?>
                        <?= view('sesiones/_mensaje_nueva', ['m' => $m]) ?>
                    <?php endforeach; ?>
                </div>
                <details class="ss-plegable" <?= $mensajes_modelo ? '' : 'open' ?>>
                    <summary><i class="bi bi-plus-lg"></i>Guardar un mensaje</summary>
                    <form class="ss-form-mini col" id="ssMsgForm">
                        <input class="fv-input" name="nombre_modelo" placeholder="Para quién (modelo o dueño del sitio)" required list="ssModelosLista">
                        <datalist id="ssModelosLista"><?php foreach ($model_releases as $r): ?><option value="<?= esc($r['nombre_modelo'], 'attr') ?>"><?php endforeach; ?></datalist>
                        <textarea class="fv-input" name="mensaje" rows="5" placeholder="Mensaje completo: horario, ubicación, enlaces de referencia…" required></textarea>
                        <button type="submit" class="fv-btn fv-btn-sm fv-btn-pri"><i class="bi bi-plus-lg"></i>Guardar mensaje</button>
                    </form>
                </details>
            </section>
        </div>
    </div>

    <!-- RODAJE -->
    <div class="ss-tab" data-panel="rodaje" hidden>
        <?php if (!$rodajes): ?>
            <section class="fv-panel ss-vacio">
                <i class="bi bi-film"></i>
                <div>Ningún rodaje vinculado. Crea uno con una escena por cada situación y úsalo en el móvil como lista de tomas.</div>
                <div class="ss-fila-botones">
                    <form method="post" action="<?= site_url('rodajes/desde-sesion/' . $sid) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-film"></i>Crear rodaje desde esta sesión</button>
                    </form>
                    <a class="fv-btn" href="<?= site_url('rodajes') ?>">Vincular uno existente</a>
                </div>
            </section>
        <?php else: ?>
            <?php foreach ($rodajes as $r): ?>
                <?php $rid = (int) $r['id']; $tot = (int) $r['total']; $hec = (int) $r['hechas']; ?>
                <section class="fv-panel">
                    <div class="fv-ph">
                        <h3><a href="<?= site_url("rodajes/$rid/escenas") ?>" class="t1"><?= esc($r['titulo']) ?></a></h3>
                        <span class="cuenta"><?= $hec ?>/<?= $tot ?> tomas</span>
                        <a class="fv-btn fv-btn-sm fv-btn-pri der" href="<?= site_url("rodajes/$rid/plato") ?>"><i class="bi bi-phone"></i>Plató</a>
                    </div>
                    <?php if ($tot): ?><div class="ss-barra ok"><i style="width:<?= round($hec / $tot * 100) ?>%"></i></div><?php endif; ?>
                    <div class="ss-fila-botones">
                        <form method="post" action="<?= site_url("rodajes/$rid/sincronizar") ?>">
                            <?= csrf_field() ?><input type="hidden" name="direccion" value="traer"><input type="hidden" name="volver" value="sesion">
                            <button type="submit" class="fv-btn fv-btn-sm" title="Crea una escena por cada situación que aún no tenga"><i class="bi bi-box-arrow-right"></i>Situaciones → escenas</button>
                        </form>
                        <form method="post" action="<?= site_url("rodajes/$rid/sincronizar") ?>">
                            <?= csrf_field() ?><input type="hidden" name="direccion" value="enviar"><input type="hidden" name="volver" value="sesion">
                            <button type="submit" class="fv-btn fv-btn-sm" title="Crea situaciones con los bloques de escenas que aún no tengan situación"><i class="bi bi-box-arrow-in-left"></i>Bloques → situaciones</button>
                        </form>
                        <a class="fv-btn fv-btn-sm" href="<?= site_url("rodajes/$rid/escenas") ?>"><i class="bi bi-list-ul"></i>Escenas</a>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script src="<?= $js ?>"></script>
<?= $this->endSection() ?>
