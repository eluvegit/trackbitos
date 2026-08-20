<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php
    $idPlaca = (int) $placa['id'];

    // Para el <input type="datetime-local">, que quiere "Y-m-dTH:i" exacto.
    $paraInput = static fn(?string $fecha) => $fecha ? date('Y-m-d\TH:i', strtotime($fecha)) : '';

    // Los decimales se enseñan con coma, que es como se teclean aquí; el
    // controlador vuelve a aceptar las dos formas al guardar.
    $peso = static fn($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');

    // Los tiempos se guardan en minutos, pero se devuelven al formulario en la
    // misma forma en que se escriben ("2h 35"): reeditar una bitácora no debe
    // obligar a traducir mentalmente 155 minutos.
    $duracion = static function ($minutos): string {
        if ($minutos === null) {
            return '';
        }
        $minutos = (int) $minutos;
        $h = intdiv($minutos, 60);
        $m = $minutos % 60;

        return $h ? ($m ? "{$h}h {$m}" : "{$h}h") : (string) $m;
    };
?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-journal-text text-primary"></i>
    <a href="<?= site_url('piezas/placas') ?>" class="text-decoration-none text-muted fw-normal">Placas</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora') ?>" class="text-decoration-none text-muted fw-normal">
        <?= esc($placa['nombre']) ?>
    </a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Editar</strong>
</h5>

<?php if (session('error')): ?>
    <div class="alert alert-warning py-2"><?= esc(session('error')) ?></div>
<?php endif; ?>

<form method="post" action="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora') ?>">
    <?= csrf_field() ?>

    <div class="row g-2">
        <div class="col-md-6">
            <label class="form-label small mb-1">Nombre de la placa</label>
            <input type="text" name="nombre" class="form-control form-control-sm" maxlength="150"
                value="<?= esc(old('nombre', $placa['nombre']), 'attr') ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Impresa el</label>
            <input type="datetime-local" name="impresa_en" class="form-control form-control-sm"
                value="<?= esc(old('impresa_en', $paraInput($placa['impresa_en'])), 'attr') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Exposición</label>
            <input type="text" name="exposicion" class="form-control form-control-sm" maxlength="255"
                placeholder="3.2s capa / 30s base, 0.05mm"
                value="<?= esc(old('exposicion', (string) $placa['exposicion']), 'attr') ?>">
        </div>

        <div class="col-md-3">
            <label class="form-label small mb-1">Peso del tanque antes (g)</label>
            <input type="text" inputmode="decimal" name="peso_antes" class="form-control form-control-sm"
                placeholder="1234,5" value="<?= esc(old('peso_antes', $peso($placa['peso_antes'])), 'attr') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Peso después (g)</label>
            <input type="text" inputmode="decimal" name="peso_despues" class="form-control form-control-sm"
                placeholder="1180" value="<?= esc(old('peso_despues', $peso($placa['peso_despues'])), 'attr') ?>">
            <div class="form-text">La diferencia se calcula sola en la bitácora.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Resina estimada por el programa (g)</label>
            <input type="text" inputmode="decimal" name="resina_estimada" class="form-control form-control-sm"
                placeholder="48" value="<?= esc(old('resina_estimada', $peso($placa['resina_estimada'])), 'attr') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Veredicto</label>
            <select name="veredicto" class="form-select form-select-sm">
                <option value="">Sin juzgar todavía</option>
                <?php foreach (\App\Models\PiezaPlacaModel::VEREDICTOS as $clave => $texto): ?>
                    <option value="<?= esc($clave, 'attr') ?>"
                        <?= old('veredicto', (string) $placa['veredicto']) === $clave ? 'selected' : '' ?>>
                        <?= esc($texto) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php // Los tres relojes y con qué se imprimió. Los tiempos se escriben como
          // se leen de la pantalla ("2h 35", "2:35" o los minutos a secas). ?>
    <div class="row g-2 mt-1">
        <div class="col-md-3">
            <label class="form-label small mb-1">Tiempo estimado (programa)</label>
            <input type="text" name="minutos_estimados" class="form-control form-control-sm"
                placeholder="2h 35" value="<?= esc(old('minutos_estimados', $duracion($placa['minutos_estimados'])), 'attr') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Tiempo previsto (máquina)</label>
            <input type="text" name="minutos_previstos" class="form-control form-control-sm"
                placeholder="2:50" value="<?= esc(old('minutos_previstos', $duracion($placa['minutos_previstos'])), 'attr') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Tiempo real</label>
            <input type="text" name="minutos_reales" class="form-control form-control-sm"
                placeholder="3h" value="<?= esc(old('minutos_reales', $duracion($placa['minutos_reales'])), 'attr') ?>">
            <div class="form-text">Vale "2h 35", "2:35" o los minutos sueltos.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Temperatura (°C)</label>
            <input type="text" inputmode="decimal" name="temperatura" class="form-control form-control-sm"
                placeholder="24,5" value="<?= esc(old('temperatura', $peso($placa['temperatura'])), 'attr') ?>">
        </div>

        <div class="col-md-12">
            <label class="form-label small mb-1">Resina (marca, color, lote)</label>
            <input type="text" name="resina" class="form-control form-control-sm" maxlength="120"
                placeholder="Elegoo ABS-like gris, lote de marzo" value="<?= esc(old('resina', (string) $placa['resina']), 'attr') ?>">
        </div>
    </div>

    <h6 class="mt-4"><i class="bi bi-box"></i> Qué llevaba</h6>
    <?php if (empty($piezas)): ?>
        <p class="text-muted small">Ninguna de esas versiones existe ya.</p>
    <?php else: ?>
        <?php // Las piezas no se añaden ni se quitan aquí: eso lo decide la placa
              // al montarla en la galería. Aquí solo se anota sobre lo que ya llevó. ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle" style="font-size: .8rem;">
                <thead>
                    <tr class="text-muted">
                        <th style="width: 2.5rem;"></th>
                        <th>Pieza</th>
                        <th style="width: 6rem;">Copias</th>
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
                                <?php if ($p['variante'] && $p['familia']): ?>
                                    <?= esc($p['familia']['nombre']) ?> - <?= esc($p['variante']['nombre']) ?>
                                    <span class="text-muted">· v<?= sprintf('%03d', (int) $p['version']['numero']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">(esa pieza ya no existe)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="number" min="1" max="999"
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

    <h6 class="mt-3"><i class="bi bi-question-circle"></i> Qué se estaba probando</h6>
    <p class="text-muted small mb-2">
        La pregunta se escribe antes de imprimir; la respuesta, al mirar la pieza ya curada.
        Las filas que dejes en blanco no se guardan.
    </p>
    <div id="listaPruebas">
        <?php foreach ($pruebas as $prueba): ?>
            <div class="row g-1 mb-1 align-items-start" data-prueba>
                <div class="col-md-5">
                    <input type="text" name="pregunta[]" class="form-control form-control-sm" maxlength="255"
                        value="<?= esc($prueba['pregunta'], 'attr') ?>" placeholder="¿Qué querías averiguar?">
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

        <?php // Siempre una fila vacía esperando: la bitácora se rellena a
              // ratos, y tener que pulsar "añadir" antes de escribir la primera
              // pregunta sobra. ?>
        <div class="row g-1 mb-1 align-items-start" data-prueba>
            <div class="col-md-5">
                <input type="text" name="pregunta[]" class="form-control form-control-sm" maxlength="255"
                    placeholder="¿Qué querías averiguar?">
            </div>
            <div class="col-md-6">
                <textarea name="respuesta[]" class="form-control form-control-sm" rows="1"
                    placeholder="Qué pasó al imprimirla"></textarea>
            </div>
            <div class="col-md-1 d-grid">
                <button type="button" class="btn btn-sm btn-outline-danger" data-quitar-prueba title="Quitar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnAnadirPrueba">
        <i class="bi bi-plus-lg"></i> Otra prueba
    </button>

    <div class="row g-3 mt-2">
        <div class="col-md-6">
            <label class="form-label small mb-1"><i class="bi bi-chat-left-text"></i> Notas y mejoras</label>
            <textarea name="notas" class="form-control form-control-sm" rows="5"
                placeholder="Lo que pasó, lo que cambiarías…"><?= esc(old('notas', (string) $placa['notas'])) ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label small mb-1"><i class="bi bi-flag"></i> Conclusiones</label>
            <textarea name="conclusiones" class="form-control form-control-sm" rows="5"
                placeholder="Qué hacer distinto en la próxima placa"><?= esc(old('conclusiones', (string) $placa['conclusiones'])) ?></textarea>
        </div>
    </div>

    <div class="d-flex gap-2 mt-3 mb-4">
        <a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora') ?>" class="btn btn-sm btn-outline-secondary">
            Cancelar
        </a>
        <button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Guardar bitácora</button>
    </div>
</form>

<script>
(function () {
    var lista = document.getElementById('listaPruebas');
    var boton = document.getElementById('btnAnadirPrueba');
    if (!lista || !boton) return;

    boton.addEventListener('click', function () {
        // Se clona la última fila en vez de construir el HTML a mano: así el
        // marcado vive en un solo sitio (el PHP de arriba) y no hay dos
        // versiones que se puedan desincronizar.
        var filas = lista.querySelectorAll('[data-prueba]');
        var nueva = filas[filas.length - 1].cloneNode(true);
        nueva.querySelectorAll('input, textarea').forEach(function (campo) { campo.value = ''; });
        lista.appendChild(nueva);
        nueva.querySelector('input').focus();
    });

    // Quitar es vaciar: una fila en blanco no se guarda, así que no hace falta
    // borrar nada en el servidor. La última que quede se vacía pero no se va,
    // para no dejar la sección sin ninguna fila donde escribir.
    lista.addEventListener('click', function (e) {
        var boton = e.target.closest('[data-quitar-prueba]');
        if (!boton) return;

        var fila = boton.closest('[data-prueba]');
        if (lista.querySelectorAll('[data-prueba]').length > 1) {
            fila.remove();
        } else {
            fila.querySelectorAll('input, textarea').forEach(function (campo) { campo.value = ''; });
        }
    });
})();
</script>

<?= $this->endSection() ?>
