<?php
/**
 * El formulario de la bitácora, a pantalla completa (fase 39, reordenado
 * fase 45, editar-solo desde fase 48 — el modal de Placas ahora es de solo
 * lectura, ver _bitacora_resumen.php). El formulario real (Form A: esencial
 * + ajustes) se cierra tras los ajustes para poder meter la foto de la
 * placa justo después sin anidar un <form> dentro de otro; el resto de
 * secciones (piezas, pruebas, notas, enlace) siguen sumando al mismo
 * guardado gracias al atributo `form="..."` de sus campos, que los asocia
 * al Form A aunque vivan fuera de su etiqueta — funciona incluso sin
 * JavaScript.
 */
$idPlaca = (int) $placa['id'];
$enlaces = $enlaces ?? [];
$imagenes = $imagenes ?? [];
$idForm = 'bitacora-form-' . $idPlaca;

// Ajustes de la calculadora de tiempo del índice (referencia capas/minutos
// + minutos fijos de preparación): el JS los lee de los data-* del form
// para estimar en vivo la duración a partir del número de capas.
$calc = $calcTiempo ?? ['capasReferencia' => 0, 'minutosReferencia' => 0, 'minutosPreparacion' => 0];

$paraInput = static fn(?string $fecha) => $fecha ? date('Y-m-d\TH:i', strtotime($fecha)) : '';
$peso = static fn($v) => $v === null || $v === '' ? '' : rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');
$duracion = static function ($minutos): string {
    if ($minutos === null) {
        return '';
    }
    $minutos = (int) $minutos;
    $h = intdiv($minutos, 60);
    $m = $minutos % 60;

    return $h ? ($m ? "{$h}h {$m}" : "{$h}h") : (string) $m;
};

$colorVeredicto = ['buena' => 'success', 'regular' => 'warning', 'repetir' => 'danger'];
$veredictoActual = (string) old('veredicto', (string) $placa['veredicto']);
$textoVeredictoActual = $veredictoActual === ''
    ? 'Sin juzgar'
    : (\App\Models\PiezaPlacaModel::VEREDICTOS[$veredictoActual] ?? 'Sin juzgar');
$colorVeredictoActual = $colorVeredicto[$veredictoActual] ?? 'secondary';
?>

<div data-bitacora-raiz>

<form id="<?= $idForm ?>" method="post" action="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora') ?>"
    data-bitacora-form data-placa="<?= $idPlaca ?>"
    data-calc-capas-ref="<?= esc($calc['capasReferencia'], 'attr') ?>"
    data-calc-minutos-ref="<?= esc($calc['minutosReferencia'], 'attr') ?>"
    data-calc-minutos-prep="<?= esc($calc['minutosPreparacion'], 'attr') ?>"
    data-csrf-name="<?= csrf_token() ?>" data-csrf-hash="<?= csrf_hash() ?>">
    <?= csrf_field() ?>

    <?php // Cómo salió, arriba del todo y a la derecha: solo se ve el estado
          // actual (como una pestaña de color), y pinchándolo se puede
          // cambiar por otro — no hace falta ver los tres botones siempre. ?>
    <div class="d-flex justify-content-end mb-2">
        <div class="dropdown" data-veredicto>
            <button type="button" class="btn btn-sm btn-<?= $colorVeredictoActual ?> dropdown-toggle"
                data-bs-toggle="dropdown" data-veredicto-boton><?= esc($textoVeredictoActual) ?></button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><button type="button" class="dropdown-item" data-veredicto-opcion
                    data-valor="" data-color="secondary">Sin juzgar</button></li>
                <?php foreach (\App\Models\PiezaPlacaModel::VEREDICTOS as $clave => $texto): ?>
                    <li><button type="button" class="dropdown-item" data-veredicto-opcion
                        data-valor="<?= esc($clave, 'attr') ?>" data-color="<?= $colorVeredicto[$clave] ?? 'secondary' ?>">
                        <?= esc($texto) ?></button></li>
                <?php endforeach; ?>
            </ul>
            <input type="hidden" name="veredicto" value="<?= esc($veredictoActual, 'attr') ?>" data-veredicto-input>
        </div>
    </div>

    <?php // Lo de después de imprimir, todo junto y sin plegar. ?>
    <div class="row g-3 align-items-end" id="info">
        <div class="col-12 col-md-6">
            <label class="form-label small mb-1">Impresa el</label>
            <div class="input-group input-group-sm">
                <input type="datetime-local" name="impresa_en" class="form-control form-control-sm"
                    value="<?= esc(old('impresa_en', $paraInput($placa['impresa_en'])), 'attr') ?>">
                <button type="button" class="btn btn-outline-secondary" data-ahora title="Poner la fecha y hora de ahora">
                    Ahora
                </button>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1">Tiempo real</label>
            <input type="text" name="minutos_reales" class="form-control form-control-sm" data-tiempo
                placeholder="2h 35" value="<?= esc(old('minutos_reales', $duracion($placa['minutos_reales'])), 'attr') ?>">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1">Número de capas</label>
            <input type="number" min="1" inputmode="numeric" name="numero_capas" class="form-control form-control-sm"
                placeholder="1240" value="<?= esc(old('numero_capas', (string) ($placa['numero_capas'] ?? '')), 'attr') ?>">
        </div>
    </div>

    <div class="small text-muted mt-1" data-calculado hidden></div>
    <?php // Estimación en vivo del tiempo a partir de las capas — mismo cálculo
          // que el botón "stopwatch" del índice (ver _bitacora_js.php). Lleva al
          // final, en la misma línea, un icono de "más información" cuyo tooltip
          // explica de dónde sale el número y con qué criterio se estima. ?>
    <div class="small text-body-secondary mt-1" data-estimado-capas hidden></div>

    <?php // Ya no plegado: a pantalla completa hay sitio de sobra, y
          // esconderlo solo añadía un clic. ?>
        <div class="mt-4" id="ajustes">
            <div class="small fw-semibold text-body-secondary mb-1">
                <i class="bi bi-sliders"></i> Nombre y ajustes de la impresión
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small mb-1">Nombre de la placa</label>
                    <input type="text" name="nombre" class="form-control form-control-sm" maxlength="150"
                        value="<?= esc(old('nombre', $placa['nombre']), 'attr') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small mb-1">Exposición</label>
                    <input type="text" name="exposicion" class="form-control form-control-sm" maxlength="255"
                        placeholder="3.2s capa / 30s base, 0.05mm"
                        value="<?= esc(old('exposicion', (string) $placa['exposicion']), 'attr') ?>">
                </div>
                <div class="col-md-9">
                    <label class="form-label small mb-1">Resina (marca, color, lote)</label>
                    <input type="text" name="resina" class="form-control form-control-sm" maxlength="120"
                        placeholder="Elegoo ABS-like gris, lote de marzo"
                        value="<?= esc(old('resina', (string) $placa['resina']), 'attr') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1 text-nowrap" title="Temperatura ambiente">Temp. (°C)</label>
                    <input type="text" inputmode="decimal" name="temperatura" class="form-control form-control-sm"
                        placeholder="24,5" value="<?= esc(old('temperatura', $peso($placa['temperatura'])), 'attr') ?>">
                </div>

                <div class="col-6 col-md-4">
                    <label class="form-label small mb-1">Tiempo estimado (programa)</label>
                    <input type="text" name="minutos_estimados" class="form-control form-control-sm" data-tiempo
                        placeholder="2h 35" value="<?= esc(old('minutos_estimados', $duracion($placa['minutos_estimados'])), 'attr') ?>">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label small mb-1">Tiempo previsto (máquina)</label>
                    <input type="text" name="minutos_previstos" class="form-control form-control-sm" data-tiempo
                        placeholder="2:50" value="<?= esc(old('minutos_previstos', $duracion($placa['minutos_previstos'])), 'attr') ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small mb-1">Resina estimada (g)</label>
                    <input type="text" inputmode="decimal" name="resina_estimada" class="form-control form-control-sm" data-peso
                        placeholder="48" value="<?= esc(old('resina_estimada', $peso($placa['resina_estimada'])), 'attr') ?>">
                </div>
                <div class="col-12">
                    <div class="form-text mb-0">Los tiempos valen como "2h 35", "2:35" o los minutos sueltos.</div>
                </div>
            </div>
        </div>
</form>

<?php // La foto de la placa y el "cabe o no" viven en el sidebar derecho
          // de bitacora_editar.php, no aquí (fase 46) — son consulta rápida,
          // no parte del cuerpo del formulario.

          // A partir de aquí los campos ya no están dentro de la etiqueta
          // form, pero suman al mismo guardado gracias al atributo "form"
          // de cada campo, que los asocia con el formulario aunque vivan
          // fuera de su etiqueta. ?>
    <div class="mt-4" id="piezas">
        <?php
            $totalPiezas = array_sum(array_map(static fn($p) => (int) $p['fila']['cantidad'], $piezas));
            $totalFallidas = array_sum(array_map(static fn($p) => (int) ($p['fila']['fallidas'] ?? 0), $piezas));
            $totalServibles = max(0, $totalPiezas - $totalFallidas);
            // Coste medio de una placa (resina + luz + desgaste), a ojo y no
            // por placa real: solo para hacerse una idea de cuánto sale cada
            // pieza suelta, no una cuenta de gastos de verdad.
            $costeMedioPlaca = 3.0;
            $precioPorPieza = $totalPiezas > 0 ? $costeMedioPlaca / $totalPiezas : null;
        ?>
        <div class="small fw-semibold text-body-secondary mb-1">
            <i class="bi bi-box"></i> Qué llevaba
            — <?= count($piezas) ?> pieza<?= count($piezas) === 1 ? '' : 's' ?> distinta<?= count($piezas) === 1 ? '' : 's' ?>,
            <?= $totalPiezas ?> en total
            <span data-total-servibles-eco>
                · <strong data-total-servibles><?= $totalServibles ?></strong> servibles
                <span class="text-muted fw-normal" data-total-fallidas-eco<?= $totalFallidas > 0 ? '' : ' hidden' ?>>
                    (<strong data-total-fallidas><?= $totalFallidas ?></strong> fallidas)
                </span>
            </span>
            <?php if ($precioPorPieza !== null): ?>
                · <?= esc(number_format($precioPorPieza, 2, ',', '.')) ?> €/pieza
                <span class="text-muted fw-normal" title="Placa a 3,00 € de media, repartido entre las <?= $totalPiezas ?> piezas">
                    <i class="bi bi-info-circle"></i>
                </span>
            <?php endif; ?>
        </div>
        <?php if (empty($piezas)): ?>
            <p class="text-muted small mt-2">Ninguna de esas versiones existe ya.</p>
        <?php else: ?>
            <div class="table-responsive mt-1">
                <table class="table table-sm align-middle mb-0" style="font-size: .8rem;">
                    <thead>
                        <tr class="text-muted">
                            <th style="width: 2.5rem;"></th>
                            <th>Pieza</th>
                            <th style="width: 3.5rem;">Copias</th>
                            <th style="width: 3.5rem;" title="De esas copias, cuántas no valen (roturas, malformaciones, mal diseño)">Fallidas</th>
                            <th style="width: 3rem;" title="Copias − fallidas">Servib.</th>
                            <th>Soportes y pruebas de esta pieza</th>
                            <th style="width: 3rem;"></th>
                            <th style="width: 2rem;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($piezas as $p): ?>
                            <?php
                                $filaId = (int) $p['fila']['id'];
                                $imagenesFila = $p['imagenes'] ?? [];
                            ?>
                            <tr>
                                <td>
                                    <?php if ($p['miniatura']): ?>
                                        <img src="<?= $p['miniatura'] ?>" loading="lazy" alt=""
                                            class="rounded border" style="width: 28px; height: 28px; object-fit: cover;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($p['variante'] && $p['familia']): ?>
                                        <a href="<?= site_url('piezas/variante/' . (int) $p['variante']['id']) ?>"
                                            target="_blank" rel="noopener" class="text-decoration-none text-body">
                                            <?= esc($p['familia']['nombre']) ?> - <?= esc($p['variante']['nombre']) ?>
                                            <span class="text-muted">· v<?= sprintf('%03d', (int) $p['version']['numero']) ?></span>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">(esa pieza ya no existe)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input type="number" min="1" max="99" inputmode="numeric" form="<?= $idForm ?>"
                                        name="cantidad[<?= $filaId ?>]" data-fila-copias
                                        class="form-control form-control-sm text-center px-1"
                                        value="<?= (int) $p['fila']['cantidad'] ?>">
                                </td>
                                <td>
                                    <input type="number" min="0" max="99" inputmode="numeric" form="<?= $idForm ?>"
                                        name="fallidas[<?= $filaId ?>]" data-fila-fallidas
                                        class="form-control form-control-sm text-center px-1"
                                        value="<?= (int) ($p['fila']['fallidas'] ?? 0) ?>">
                                </td>
                                <td class="text-center text-body-secondary" data-fila-servibles>
                                    <?= max(0, (int) $p['fila']['cantidad'] - (int) ($p['fila']['fallidas'] ?? 0)) ?>
                                </td>
                                <td>
                                    <?php // Una línea en reposo, varias al pinchar (data-nota-expandible,
                                          // ver _bitacora_js.php): así la fila no ocupa de más cuando no
                                          // hace falta, pero da sitio para escribir sin apretarse. ?>
                                    <textarea maxlength="500" form="<?= $idForm ?>" rows="1" data-nota-expandible
                                        name="nota_pieza[<?= $filaId ?>]" class="form-control form-control-sm"
                                        placeholder="tumbada 30°, soportes medios en la base"><?= esc((string) $p['fila']['notas']) ?></textarea>
                                </td>
                                <td>
                                    <?php if ($p['variante']): ?>
                                        <a href="<?= site_url('piezas/variante/' . (int) $p['variante']['id']) . '#capturas' ?>"
                                            target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary position-relative"
                                            title="Ver histórico de capturas de esta pieza">
                                            <i class="bi bi-camera"></i>
                                            <?php if ($imagenesFila !== []): ?>
                                                <span class="badge rounded-pill bg-secondary position-absolute top-0 start-100 translate-middle"
                                                    style="font-size: .55rem;"><?= count($imagenesFila) ?></span>
                                            <?php endif; ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-quitar-pieza
                                        data-fila="<?= $filaId ?>" title="Quitar esta pieza de la placa">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="mt-2" data-anadir-pieza>
            <label class="form-label small mb-1">Añadir pieza a esta placa</label>
            <div class="position-relative">
                <input type="text" class="form-control form-control-sm" placeholder="Nombre o SKU de la pieza…"
                    data-buscar-pieza autocomplete="off">
                <div class="list-group position-absolute w-100 shadow-sm" style="z-index: 1060; display: none;"
                    data-resultados-pieza></div>
            </div>
        </div>
    </div>

    <div class="mt-4" id="pruebas">
        <div class="small fw-semibold text-body-secondary mb-1">
            <i class="bi bi-question-circle"></i> Qué se estaba probando
        </div>
        <p class="text-muted small mb-2">
            La pregunta se escribe antes de imprimir; la respuesta, al mirar la pieza ya curada.
            Las filas que dejes en blanco no se guardan.
        </p>
        <div data-lista-pruebas>
            <?php $filasPrueba = $pruebas; ?>
            <?php $filasPrueba[] = ['pregunta' => '', 'respuesta' => '']; ?>
            <?php foreach ($filasPrueba as $prueba): ?>
                <div class="row g-1 mb-1 align-items-start" data-prueba>
                    <div class="col-md-5">
                        <input type="text" name="pregunta[]" class="form-control form-control-sm" maxlength="255"
                            form="<?= $idForm ?>"
                            value="<?= esc((string) $prueba['pregunta'], 'attr') ?>" placeholder="¿Qué querías averiguar?">
                    </div>
                    <div class="col-md-6">
                        <textarea name="respuesta[]" class="form-control form-control-sm" rows="1" form="<?= $idForm ?>"
                            placeholder="Qué pasó al imprimirla"><?= esc((string) $prueba['respuesta']) ?></textarea>
                    </div>
                    <div class="col-md-1 d-grid">
                        <button type="button" class="btn btn-sm btn-outline-danger" data-quitar-prueba title="Quitar">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-anadir-prueba>
            <i class="bi bi-plus-lg"></i> Otra prueba
        </button>
    </div>

    <div class="mt-4" id="notas">
        <label class="form-label small mb-1"><i class="bi bi-chat-left-text"></i> Notas y mejoras</label>
        <textarea name="notas" class="form-control form-control-sm" rows="4" form="<?= $idForm ?>"
            placeholder="Lo que pasó, lo que cambiarías…"><?= esc(old('notas', (string) $placa['notas'])) ?></textarea>
    </div>
    <div class="mt-4">
        <label class="form-label small mb-1"><i class="bi bi-flag"></i> Conclusiones</label>
        <textarea name="conclusiones" class="form-control form-control-sm" rows="8" form="<?= $idForm ?>"
            placeholder="Qué hacer distinto en la próxima placa"><?= esc(old('conclusiones', (string) $placa['conclusiones'])) ?></textarea>
    </div>

    <?php // El enlace, al final del todo: es lo último que se toca, cuando ya
          // está todo lo demás anotado. ?>
    <div class="mt-4" id="enlace">
        <div class="small fw-semibold text-body-secondary mb-1">
            <i class="bi bi-link-45deg"></i> Enlace al Drive
        </div>
        <div data-lista-enlaces>
            <?php $filasEnlace = $enlaces; ?>
            <?php $filasEnlace[] = ['url' => '', 'titulo' => '']; ?>
            <?php foreach ($filasEnlace as $enlace): ?>
                <div class="row g-1 mb-1" data-enlace>
                    <div class="col-md-4">
                        <input type="text" name="enlace_titulo[]" class="form-control form-control-sm" maxlength="150"
                            form="<?= $idForm ?>"
                            placeholder="Nombre para identificarlo" value="<?= esc((string) $enlace['titulo'], 'attr') ?>">
                    </div>
                    <div class="col-md-7">
                        <input type="url" inputmode="url" name="enlace_url[]" class="form-control form-control-sm"
                            form="<?= $idForm ?>"
                            placeholder="https://drive.google.com/…" value="<?= esc((string) $enlace['url'], 'attr') ?>">
                    </div>
                    <div class="col-md-1 d-grid">
                        <a href="<?= $enlace['url'] ? esc($enlace['url'], 'attr') : '#' ?>" target="_blank" rel="noopener"
                            class="btn btn-sm btn-outline-secondary <?= $enlace['url'] ? '' : 'disabled' ?>"
                            data-abrir-enlace title="Abrir en otra pestaña">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-anadir-enlace>
            <i class="bi bi-plus-lg"></i> Otro enlace
        </button>
    </div>

    <?php // Barra fija abajo: volver al histórico, guardar sin salir de aquí
          // (fase 49 — guardar ya no lleva a ninguna otra pantalla), o mandar
          // el zip otra vez a la impresora sin tener que ir a buscarlo al
          // histórico. ?>
    <div class="d-flex gap-2 py-2 mt-3 mb-1 position-sticky bottom-0 bg-body border-top">
        <a href="<?= site_url('piezas/placa/' . $idPlaca . '/descargar') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-download"></i> Descargar STL
        </a>
        <div class="d-flex gap-2 ms-auto">
            <a href="<?= site_url('piezas/placas') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            <button type="submit" form="<?= $idForm ?>" class="btn btn-sm btn-success">
                <i class="bi bi-check-lg"></i> Guardar
            </button>
        </div>
    </div>
</div>
