<?php
/**
 * El formulario de la bitácora, en un solo sitio (fase 39): lo usan la
 * pantalla /bitacora/editar y el modal de Placas, que es donde se rellena
 * de verdad — junto a la impresora, a ratos, mientras la placa está viva.
 * Un único marcado para los dos, porque son literalmente el mismo cuaderno
 * y dos copias acabarían diciendo cosas distintas.
 *
 * Recibe: $placa, $piezas, $pruebas, $enlaces y $enModal (bool). Los cuatro
 * primeros llegan solos por ser datos de la vista padre; `$enModal` solo lo
 * pone el controlador al renderizar el trozo suelto para el modal, así que
 * aquí se da por falso cuando no viene.
 *
 * El orden no es el del esquema, es el del día: arriba lo que se apunta
 * nada más apagar la máquina (cuándo acabó, cuánto tardó, qué pesa el
 * tanque, si salió bien) y plegado debajo lo que se consulta de vez en
 * cuando. Lo de arriba tiene que caber en una pantalla de móvil sin hacer
 * scroll: si hay que buscar el campo, se acaba no apuntando nada.
 */
$idPlaca = (int) $placa['id'];
$enModal = isset($enModal) && $enModal;
$enlaces = $enlaces ?? [];
$imagenes = $imagenes ?? [];

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
?>

<form method="post" action="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora') ?>"
    data-bitacora-form data-placa="<?= $idPlaca ?>">
    <?= csrf_field() ?>

    <?php // Lo de después de imprimir, todo junto y sin plegar. ?>
    <div class="row g-2 align-items-end">
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1">Impresa el</label>
            <div class="input-group input-group-sm">
                <input type="datetime-local" name="impresa_en" class="form-control form-control-sm"
                    value="<?= esc(old('impresa_en', $paraInput($placa['impresa_en'])), 'attr') ?>">
                <?php // Teclear una fecha con hora a mano es lo más pesado del
                      // formulario, y casi siempre es "hace un rato". ?>
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
            <label class="form-label small mb-1">Peso del tanque antes (g)</label>
            <input type="text" inputmode="decimal" name="peso_antes" class="form-control form-control-sm" data-peso
                placeholder="1234,5" value="<?= esc(old('peso_antes', $peso($placa['peso_antes'])), 'attr') ?>">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small mb-1">Peso después (g)</label>
            <input type="text" inputmode="decimal" name="peso_despues" class="form-control form-control-sm" data-peso
                placeholder="1180" value="<?= esc(old('peso_despues', $peso($placa['peso_despues'])), 'attr') ?>">
        </div>
    </div>

    <?php // Las cuentas se hacen aquí mismo mientras se teclea: son la razón
          // de apuntar dos pesos y tres relojes, y esperar a guardar para
          // verlas convierte el número en un trámite. ?>
    <div class="small text-muted mt-1" data-calculado hidden></div>

    <div class="mt-2">
        <label class="form-label small mb-1">Cómo salió</label>
        <div class="d-flex flex-wrap gap-1">
            <?php // Botones y no un desplegable: es un clic contra tres, y de
                  // paso el color se queda en la cabeza al repasar el histórico. ?>
            <input type="radio" class="btn-check" name="veredicto" value=""
                id="v-nada-<?= $idPlaca ?>" <?= $veredictoActual === '' ? 'checked' : '' ?>>
            <label class="btn btn-sm btn-outline-secondary" for="v-nada-<?= $idPlaca ?>">Sin juzgar</label>

            <?php foreach (\App\Models\PiezaPlacaModel::VEREDICTOS as $clave => $texto): ?>
                <input type="radio" class="btn-check" name="veredicto" value="<?= esc($clave, 'attr') ?>"
                    id="v-<?= esc($clave, 'attr') ?>-<?= $idPlaca ?>" <?= $veredictoActual === $clave ? 'checked' : '' ?>>
                <label class="btn btn-sm btn-outline-<?= $colorVeredicto[$clave] ?? 'secondary' ?>"
                    for="v-<?= esc($clave, 'attr') ?>-<?= $idPlaca ?>"><?= esc($texto) ?></label>
            <?php endforeach; ?>
        </div>
    </div>

    <?php // Plegado: el nombre, los ajustes de la máquina y lo que prometía el
          // laminador se tocan una vez, al montar la placa, no cada vez que se
          // abre el cuaderno a anotar cómo quedó. ?>
    <details class="mt-3" <?= $enModal ? '' : 'open' ?>>
        <summary class="small fw-semibold text-body-secondary" style="cursor: pointer;">
            <i class="bi bi-sliders"></i> Nombre y ajustes de la impresión
        </summary>
        <div class="row g-2 mt-1">
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
                <label class="form-label small mb-1">Temperatura ambiente (°C)</label>
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
                <div class="form-text">Los tiempos valen como "2h 35", "2:35" o los minutos sueltos.</div>
            </div>
        </div>
    </details>

    <?php // Los enlaces, sin plegar: es lo que se viene a buscar cuando se
          // abre una placa vieja para volver a imprimir lo mismo. ?>
    <div class="mt-3">
        <div class="small fw-semibold text-body-secondary mb-1">
            <i class="bi bi-link-45deg"></i> Enlaces (Drive, fotos, dónde estaba la receta)
        </div>
        <div data-lista-enlaces>
            <?php $filasEnlace = $enlaces; ?>
            <?php $filasEnlace[] = ['url' => '', 'titulo' => '']; // siempre una vacía esperando ?>
            <?php foreach ($filasEnlace as $enlace): ?>
                <div class="row g-1 mb-1" data-enlace>
                    <div class="col-md-4">
                        <input type="text" name="enlace_titulo[]" class="form-control form-control-sm" maxlength="150"
                            placeholder="Proyecto en Drive" value="<?= esc((string) $enlace['titulo'], 'attr') ?>">
                    </div>
                    <div class="col-md-7">
                        <input type="url" inputmode="url" name="enlace_url[]" class="form-control form-control-sm"
                            placeholder="https://drive.google.com/…" value="<?= esc((string) $enlace['url'], 'attr') ?>">
                    </div>
                    <div class="col-md-1 d-grid">
                        <?php // Abrir sin salir del cuaderno: si estoy mirando la
                              // placa es justo para ir a ver eso de fuera. ?>
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

    <?php // Las piezas no se añaden ni se quitan aquí: eso lo decide la placa
          // al montarla en la galería. Aquí solo se anota sobre lo que llevó. ?>
    <?php
        /**
         * Cuántas placas hace falta según las cuadrículas medidas de cada
         * STL (spec: reparto de piezas en placas). Con las cantidades de
         * AHORA, así que se recalcula cada vez que se guarda — cambiar
         * "Copias" no lo actualiza solo hasta que se pulse Guardar.
         */
        $reparto = $reparto ?? [];
        $sinMedir = $sinMedir ?? 0;
    ?>
    <?php $capacidad = \App\Services\PiezaEmpaquetadoService::COLUMNAS * \App\Services\PiezaEmpaquetadoService::FILAS; ?>
    <?php if ($reparto !== [] || $sinMedir > 0): ?>
        <div class="alert <?= count($reparto) > 1 ? 'alert-info' : 'alert-secondary' ?> mt-3 py-2 mb-2 small" data-reparto>
            <?php if (count($reparto) <= 1): ?>
                <i class="bi bi-grid-3x3"></i> Cabe en <strong>una placa</strong>
                (<?= (int) ($reparto[0]['cuadrosUsados'] ?? 0) ?>/<?= $capacidad ?> cuadrículas).
            <?php else: ?>
                <?php // "No cabe" a secas suena a que algo ha ido mal — y no es
                      // así, es que hacen falta dos o más, que es tan normal
                      // como una: por eso el color es informativo, no de aviso. ?>
                <i class="bi bi-grid-3x3"></i> No cabe en una placa, pero sí en
                <strong><?= count($reparto) ?></strong> (cálculo aproximado):
                <ul class="mb-0 mt-1 ps-3">
                    <?php foreach ($reparto as $i => $bin): ?>
                        <li>
                            Placa <?= $i + 1 ?> (<?= $bin['cuadrosUsados'] ?>/<?= $capacidad ?>):
                            <?= implode(', ', array_map(
                                static fn(array $p) => esc($p['etiqueta']) . ($p['cantidad'] > 1 ? ' ×' . $p['cantidad'] : ''),
                                $bin['piezas']
                            )) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <span class="text-muted">Usa "Repartir en otra placa" desde el histórico para materializarlo.</span>
            <?php endif; ?>
            <?php if ($sinMedir > 0): ?>
                <div class="text-warning-emphasis mt-1"><?= $sinMedir ?> STL sin cuadrícula medida, no entran en la cuenta.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <details class="mt-3" open>
        <summary class="small fw-semibold text-body-secondary" style="cursor: pointer;">
            <i class="bi bi-box"></i> Qué llevaba (<?= count($piezas) ?>)
        </summary>
        <?php if (empty($piezas)): ?>
            <p class="text-muted small mt-2">Ninguna de esas versiones existe ya.</p>
        <?php else: ?>
            <div class="table-responsive mt-1">
                <table class="table table-sm align-middle mb-0" style="font-size: .8rem;">
                    <thead>
                        <tr class="text-muted">
                            <th style="width: 2.5rem;"></th>
                            <th>Pieza</th>
                            <th style="width: 5.5rem;">Copias</th>
                            <th>Soportes y pruebas de esta pieza</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($piezas as $p): ?>
                            <?php $filaId = (int) $p['fila']['id']; ?>
                            <tr>
                                <td>
                                    <?php if ($p['miniatura']): ?>
                                        <img src="<?= $p['miniatura'] ?>" loading="lazy" alt=""
                                            class="rounded border" style="width: 28px; height: 28px; object-fit: cover;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php // En otra pestaña siempre: dentro del modal, navegar
                                          // aquí se llevaría por delante lo que no se haya
                                          // guardado del cuaderno. ?>
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
                                    <input type="number" min="1" max="999" inputmode="numeric"
                                        name="cantidad[<?= $filaId ?>]" class="form-control form-control-sm"
                                        value="<?= (int) $p['fila']['cantidad'] ?>">
                                </td>
                                <td>
                                    <input type="text" maxlength="500"
                                        name="nota_pieza[<?= $filaId ?>]" class="form-control form-control-sm"
                                        placeholder="tumbada 30°, soportes medios en la base"
                                        value="<?= esc((string) $p['fila']['notas'], 'attr') ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </details>

    <div class="mt-3">
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
                            value="<?= esc((string) $prueba['pregunta'], 'attr') ?>" placeholder="¿Qué querías averiguar?">
                    </div>
                    <div class="col-md-6">
                        <textarea name="respuesta[]" class="form-control form-control-sm" rows="1"
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

    <div class="row g-3 mt-1">
        <div class="col-md-6">
            <label class="form-label small mb-1"><i class="bi bi-chat-left-text"></i> Notas y mejoras</label>
            <textarea name="notas" class="form-control form-control-sm" rows="4"
                placeholder="Lo que pasó, lo que cambiarías…"><?= esc(old('notas', (string) $placa['notas'])) ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label small mb-1"><i class="bi bi-flag"></i> Conclusiones</label>
            <textarea name="conclusiones" class="form-control form-control-sm" rows="4"
                placeholder="Qué hacer distinto en la próxima placa"><?= esc(old('conclusiones', (string) $placa['conclusiones'])) ?></textarea>
        </div>
    </div>

    <?php if (!$enModal): ?>
        <div class="d-flex gap-2 mt-3 mb-4">
            <a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora') ?>" class="btn btn-sm btn-outline-secondary">
                Cancelar
            </a>
            <button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Guardar bitácora</button>
        </div>
    <?php endif; ?>
</form>

<?php // Fotos de la plataforma del laminador: de dónde partía la impresión y
      // cómo quedó orientada/soportada, no solo el resultado ya curado.
      // Fuera del <form> de arriba (no se puede anidar un <form> dentro de
      // otro): alta y baja son inmediatas, igual que las referencias de una
      // variante — no forman parte del guardado general de la bitácora. ?>
<div class="mt-3">
    <div class="small fw-semibold text-body-secondary mb-1">
        <i class="bi bi-camera"></i> Fotos de la plataforma (laminador)
    </div>

    <?php if (empty($imagenes)): ?>
        <p class="text-muted small mb-2">
            Sin fotos todavía (captura del laminador: orientación, soportes, desde dónde partía).
        </p>
    <?php else: ?>
        <div class="d-flex flex-wrap gap-2 mb-2">
            <?php foreach ($imagenes as $img): ?>
                <div class="position-relative" style="width: 88px;">
                    <a href="<?= imagen_pieza($img, 'placa-imagen', 'v') ?>" target="_blank"
                        title="<?= esc($img['notas'] ?? '') ?>">
                        <img src="<?= imagen_pieza($img, 'placa-imagen') ?>"
                            class="rounded border" style="width: 88px; height: 88px; object-fit: cover;"
                            alt="Plataforma del laminador" loading="lazy">
                    </a>
                    <form method="post" action="<?= site_url('piezas/placa-imagen/' . (int) $img['id'] . '/borrar') ?>"
                        onsubmit="return confirm('¿Apartar esta foto a la papelera?');" class="position-absolute top-0 end-0">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-dark py-0 px-1 opacity-75" style="font-size: .65rem;" title="Borrar">
                            <i class="bi bi-x"></i>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-center"
        action="<?= site_url('piezas/placa/' . $idPlaca . '/imagen') ?>">
        <?= csrf_field() ?>
        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp"
            class="form-control form-control-sm" style="max-width: 220px;" required>
        <input type="text" name="notas" class="form-control form-control-sm" maxlength="150"
            placeholder="Nota (opcional)" style="max-width: 180px;">
        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-upload"></i> Subir foto</button>
    </form>
</div>
