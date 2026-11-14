<?php
/**
 * Editor de series compartido (registro de entreno y plantillas).
 *
 * @var array  $ejerciciosAgrupados  bloques con series (ver controladores)
 * @var string $claveEe              'ee_id' (entreno) o 'pe_id' (plantilla)
 * @var array  $recientes, $grupos, $todos
 * @var bool   $cronometro           cronómetro de descanso y avisos de récord
 * @var array  $config               configuración para gimnasio-editor.js
 * @var string $vacioTxt
 */
$js = static fn (string $f) => base_url('assets/js/' . $f) . '?v=' . (@filemtime(FCPATH . 'assets/js/' . $f) ?: 1);
?>
<div class="gm-ejs" id="gmEjs">
    <?php foreach ($ejerciciosAgrupados as $g): ?>
        <section class="gm-ej" data-ee="<?= $g[$claveEe] ?>" data-ej="<?= $g['ejercicio_id'] ?>" data-nombre="<?= esc($g['ejercicio_nombre']) ?>" data-grupo="<?= esc($g['grupo_nombre']) ?>">
            <div class="gm-ej-h">
                <div class="pp">
                    <div class="t"><a href="<?= site_url('gimnasio/ejercicios/estadisticas/' . $g['ejercicio_id']) ?>"><?= esc($g['ejercicio_nombre']) ?></a></div>
                    <div class="d"><?= esc($g['grupo_nombre']) ?> · <span data-resumen></span></div>
                </div>
                <button type="button" class="fv-ibtn" data-mover="up" title="Subir"><i class="bi bi-arrow-up"></i></button>
                <button type="button" class="fv-ibtn" data-mover="down" title="Bajar"><i class="bi bi-arrow-down"></i></button>
            </div>
            <div class="gm-series">
                <?php foreach ($g['series'] as $i => $s): ?>
                    <?php
                    $datos = [
                        'id' => (int) $s['id'], 'series' => (int) $s['series'], 'repeticiones' => (int) $s['repeticiones'],
                        'peso' => (float) $s['peso'], 'rpe' => $s['rpe'] !== null && $s['rpe'] !== '' ? (int) $s['rpe'] : null, 'nota' => $s['nota'],
                    ];
                    ?>
                    <button type="button" class="gm-serie" data-serie="<?= esc(json_encode($datos, JSON_UNESCAPED_UNICODE)) ?>">
                        <span class="i"><?= $i + 1 ?></span>
                        <span class="sr"><?= (int) $s['series'] ?><small>×</small><?= (int) $s['repeticiones'] ?></span>
                        <?php if ((float) $s['peso'] > 0): ?><span class="kg"><?= gim_kg($s['peso']) ?><small>kg</small></span><?php endif; ?>
                        <?php if (!empty($s['pr'])): ?><i class="bi bi-trophy-fill" title="Récord: superó lo mejor de sesiones anteriores"></i><?php endif; ?>
                        <span class="ex"><?php if ($datos['rpe'] !== null): ?><span class="rpe">RPE <?= $datos['rpe'] ?></span><?php endif; ?><?= esc($s['nota'] ?? '') ?></span>
                        <i class="bi bi-pencil"></i>
                    </button>
                <?php endforeach; ?>
            </div>
            <button type="button" class="gm-ej-mas" data-mas><i class="bi bi-plus-lg"></i> Otra serie</button>
        </section>
    <?php endforeach; ?>
</div>
<div class="gm-vacio" id="gmEjsVacia" <?= $ejerciciosAgrupados ? 'hidden' : '' ?>>
    <i class="bi bi-plus-circle-dotted"></i><?= $vacioTxt ?>
</div>
<div class="gm-hueco"></div>

<button type="button" class="gm-fab" id="gmFab" title="Añadir serie"><i class="bi bi-plus-lg"></i></button>

<div class="gm-velo" id="gmVelo"></div>
<div class="gm-hoja" id="gmHoja" role="dialog" aria-labelledby="gmHojaT">
    <div class="gm-hoja-asa"></div>
    <div class="gm-hoja-h">
        <div class="t"><span id="gmHojaT">Añadir serie</span> <span class="d" id="gmHojaD"></span></div>
        <button type="button" class="fv-gbtn" id="gmCambiar">Cambiar</button>
        <button type="button" class="fv-ibtn" id="gmCerrar" title="Cerrar"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="gm-hoja-b">
        <!-- Paso 1: elegir ejercicio -->
        <div id="gmElegir" class="gm-sesion">
            <div class="gm-buscar"><i class="bi bi-search"></i><input type="search" id="gmBuscarEj" class="fv-input" placeholder="Buscar entre tus <?= count($todos) ?> ejercicios…" autocomplete="off"></div>
            <div data-bloque="resultados" id="gmBloqueResultados" hidden><div class="gm-chips" id="gmResultados"></div></div>
            <div data-bloque="sesion" hidden>
                <div class="gm-lbl" style="margin-bottom:6px">En esta sesión</div>
                <div class="gm-chips" id="gmEnSesion"></div>
            </div>
            <?php if ($recientes): ?>
                <div data-bloque="fijo">
                    <div class="gm-lbl" style="margin-bottom:6px">Los que más haces</div>
                    <div class="gm-chips" id="gmRecientes">
                        <?php foreach ($recientes as $r): ?>
                            <button type="button" class="gm-chip" data-elegir="<?= esc(json_encode(['id' => (int) $r['id'], 'nombre' => $r['nombre'], 'grupo' => $grupos[$r['grupo_muscular']] ?? $r['grupo_muscular']], JSON_UNESCAPED_UNICODE)) ?>"><?= esc($r['nombre']) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <div data-bloque="fijo">
                <div class="gm-lbl" style="margin-bottom:6px">Por grupo muscular</div>
                <div class="gm-grupos">
                    <?php foreach ($grupos as $k => $n): ?>
                        <button type="button" data-grupo="<?= esc($k) ?>"><?= esc($n) ?></button>
                    <?php endforeach; ?>
                </div>
                <div class="gm-chips" id="gmDeGrupo" style="margin-top:10px"></div>
            </div>
        </div>

        <!-- Paso 2: la serie -->
        <div id="gmSerie" class="gm-sesion" hidden>
            <div class="gm-ultima" id="gmUltima" hidden>
                <div class="h"><i class="bi bi-clock-history"></i><span></span></div>
                <div class="ss"></div>
                <div class="rec"></div>
            </div>
            <div class="gm-steps">
                <div class="gm-step" data-campo="series">
                    <label for="gmVSeries">Series</label>
                    <button type="button" class="m" data-dir="-1">−</button>
                    <input id="gmVSeries" type="number" inputmode="numeric" min="1">
                    <button type="button" class="m" data-dir="1">+</button>
                </div>
                <div class="gm-step" data-campo="repeticiones">
                    <label for="gmVReps">Reps</label>
                    <button type="button" class="m" data-dir="-1">−</button>
                    <input id="gmVReps" type="number" inputmode="numeric" min="0">
                    <button type="button" class="m" data-dir="1">+</button>
                </div>
                <div class="gm-step" data-campo="peso">
                    <label for="gmVPeso">Peso <small>kg</small><button type="button" id="gmPaso"></button></label>
                    <button type="button" class="m" data-dir="-1">−</button>
                    <input id="gmVPeso" type="number" inputmode="decimal" step="any" min="0" placeholder="—">
                    <button type="button" class="m" data-dir="1">+</button>
                </div>
            </div>
            <details class="gm-mas" id="gmMasSerie">
                <summary><i class="bi bi-chevron-down"></i> RPE y nota</summary>
                <div class="gm-mas-c">
                    <div class="gm-step" data-campo="rpe">
                        <label for="gmVRpe">RPE <small>1–10</small></label>
                        <button type="button" class="m" data-dir="-1">−</button>
                        <input id="gmVRpe" type="number" inputmode="numeric" min="1" max="10" placeholder="—">
                        <button type="button" class="m" data-dir="1">+</button>
                    </div>
                    <textarea id="gmNotaSerie" class="gm-nota" rows="1" placeholder="Nota de la serie (opcional)"></textarea>
                </div>
            </details>
            <div class="gm-hoja-pie">
                <button type="button" class="gm-borrar" id="gmBorrar" title="Eliminar serie" hidden><i class="bi bi-trash"></i></button>
                <button type="button" class="gm-guardar" id="gmGuardar"><i class="bi bi-check-lg"></i> Guardar serie</button>
            </div>
            <div class="gm-feedback" id="gmFeedback"></div>
        </div>
    </div>
</div>

<?php if (!empty($cronometro)): ?>
    <div class="gm-timer" id="gmTimer" role="timer">
        <div class="reloj">1:30</div>
        <div class="lbl"><b>Descanso</b>vibra al acabar</div>
        <div class="ps">
            <button type="button" data-s="60">1:00</button>
            <button type="button" data-s="90">1:30</button>
            <button type="button" data-s="120">2:00</button>
            <button type="button" data-s="180">3:00</button>
        </div>
        <button type="button" class="fv-ibtn gm-pausa" data-pausa title="Pausar"><i class="bi bi-pause-fill"></i></button>
        <button type="button" class="fv-ibtn" data-parar title="Cerrar"><i class="bi bi-x-lg"></i></button>
        <div class="barra"></div>
    </div>
    <div class="gm-toast" id="gmToast" role="status"><i class="bi bi-trophy-fill"></i><div><b></b><small></small></div></div>
<?php endif; ?>

<script>
window.GM_EDITOR = <?= json_encode($config + [
    'todos'  => array_map(fn ($e) => ['id' => (int) $e['id'], 'nombre' => $e['nombre'], 'grupo' => $grupos[$e['grupo_muscular']] ?? (string) $e['grupo_muscular']], $todos),
    'grupos' => $grupos,
    'defecto' => ['series' => 1, 'reps' => 10],
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<script src="<?= $js('gimnasio.js') ?>"></script>
<script src="<?= $js('gimnasio-editor.js') ?>"></script>
