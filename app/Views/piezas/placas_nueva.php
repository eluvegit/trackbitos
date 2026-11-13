<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-placas.css') ?>?v=9">
<?php
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $veredictos = \App\Models\PiezaPlacaModel::VEREDICTOS;
    $tonoVer = ['buena' => 'e-ok', 'regular' => 'e-warn', 'repetir' => 'e-bad'];

    $plano = static function (array $grupos): array {
        $todas = [];
        foreach ($grupos as $lista) {
            foreach ($lista as $p) {
                $todas[] = $p;
            }
        }
        return $todas;
    };
    $porImprimir = $plano($bloques['porImprimir']['grupos']);
    $impresas    = $plano($bloques['impresa']['grupos']);

    $info = static function (array $placa) use ($piezas, $resumenes, $meses): array {
        $id    = (int) $placa['id'];
        $lista = $piezas[$id] ?? [];
        $uds   = array_sum(array_map(static fn($p) => (int) $p['fila']['cantidad'], $lista));
        $fal   = array_sum(array_map(static fn($p) => min((int) $p['fila']['cantidad'], (int) ($p['fila']['fallidas'] ?? 0)), $lista));
        $fecha = strtotime($placa['impresa_en'] ?: $placa['creado_en']);
        $nombres = array_map(static fn($p) => trim(($p['familia']['nombre'] ?? '') . ' ' . ($p['variante']['nombre'] ?? '') . ' ' . ($p['variante']['sku'] ?? '')), $lista);

        return [
            'id'      => $id,
            'lista'   => $lista,
            'fotos'   => array_values(array_filter(array_column($lista, 'miniatura'))),
            'uds'     => $uds,
            'fal'     => $fal,
            'fecha'   => $fecha,
            'mes'     => ucfirst($meses[(int) date('n', $fecha) - 1]) . ' ' . date('Y', $fecha),
            'nombre'  => trim(preg_replace('/^#\d+\s*(OLD|NEW)?\s*/', '', (string) $placa['nombre'])) ?: (string) $placa['nombre'],
            'buscar'  => mb_strtolower('#' . $id . ' | ' . implode(' | ', $nombres)),
            'resumen' => $resumenes[$id] ?? ['anotada' => false, 'sinResponder' => 0, 'enlaces' => 0, 'veredicto' => null],
        ];
    };
    $dur = static function ($m): string {
        if ($m === null || $m === '') {
            return '—';
        }
        $m = (int) $m;
        $h = intdiv($m, 60);
        $r = $m % 60;
        return $h ? ($r ? "{$h} h {$r}" : "{$h} h") : "{$r} min";
    };
    $gramos = static fn($v) => $v === null || $v === '' ? '—' : number_format((float) $v, 0, ',', '.') . ' g';
    $mini = static function (array $fotos, int $n, string $clase = 'pl-th'): string {
        $h = '';
        foreach (array_slice($fotos, 0, $n) as $f) {
            $h .= '<img src="' . $f . '" alt="" loading="lazy" class="' . $clase . '">';
        }
        return $h ?: '<span class="' . $clase . ' vacia"><i class="bi bi-printer"></i></span>';
    };
    $verTexto = static function (array $r) use ($veredictos, $tonoVer): string {
        if ($r['veredicto'] && isset($veredictos[$r['veredicto']])) {
            return '<span class="fv-estado ' . ($tonoVer[$r['veredicto']] ?? 'e-neu') . '">' . esc($veredictos[$r['veredicto']]) . '</span>';
        }
        return '<span class="fv-estado e-info">' . ($r['anotada'] ? 'Sin juzgar' : 'Sin anotar') . '</span>';
    };
    $procedencia = static function (array $placa) use ($origenNombres, $pedidosPorPlaca, $gruposReparto, $nombresPlacas): string {
        $id = (int) $placa['id'];
        $partes = [];
        if ($placa['origen_placa_id']) {
            $partes[] = '<span><i class="bi bi-signpost-split"></i> ' . esc($origenNombres[(int) $placa['origen_placa_id']] ?? 'placa borrada') . '</span>';
        }
        $pedidos = $pedidosPorPlaca[$id] ?? [];
        foreach (array_slice($pedidos, 0, 2) as $pedidoId) {
            $partes[] = '<a href="' . site_url('piezas/pedido/' . $pedidoId) . '"><i class="bi bi-cart-check"></i> Pedido #' . (int) $pedidoId . '</a>';
        }
        if (count($pedidos) > 2) {
            $resto = array_map(static fn($p) => '#' . (int) $p, array_slice($pedidos, 2));
            $partes[] = '<span title="También: pedidos ' . esc(implode(', ', $resto), 'attr') . '">+' . count($resto) . ' pedidos</span>';
        }
        if ($g = $gruposReparto[$id] ?? null) {
            $tono = ($g['raiz'] * 47) % 360;
            $hermanas = array_map(static fn($hid) => $nombresPlacas[$hid] ?? 'placa borrada', $g['hermanas']);
            $partes[] = '<span title="Placa dividida — ' . esc(implode(', ', $hermanas), 'attr') . '"><i class="pl-fam" style="background:hsl(' . $tono . ',65%,50%)"></i> Dividida (' . (count($g['hermanas']) + 1) . ')</span>';
        }
        return $partes ? '<div class="pl-proc">' . implode('', $partes) . '</div>' : '';
    };

    $infos = [];
    foreach ($impresas as $placa) {
        $infos[(int) $placa['id']] = $info($placa);
    }
    $porMes = [];
    foreach ($impresas as $placa) {
        $porMes[$infos[(int) $placa['id']]['mes']][] = $placa;
    }
    $sinJuzgar = array_values(array_filter($impresas, static fn($p) => empty($p['veredicto'])));
    $nOld = count(array_filter($impresas, static fn($p) => ($p['impresora'] ?? '') === 'OLD'));
    $nNew = count(array_filter($impresas, static fn($p) => ($p['impresora'] ?? '') === 'NEW'));
    $atrib = static function (array $placa, array $i): string {
        return 'data-placa-item data-placa="' . $i['id'] . '" data-mes="' . esc($i['mes'], 'attr') . '" data-maq="' . esc($placa['impresora'] ?? '', 'attr') . '"'
            . ' data-ver="' . esc($placa['veredicto'] ?? '', 'attr') . '" data-buscar="' . esc($i['buscar'], 'attr') . '"'
            . ' data-min="' . (int) ($placa['minutos_reales'] ?? 0) . '" data-res="' . (float) ($placa['resina_estimada'] ?? 0) . '"'
            . ' data-uds="' . $i['uds'] . '" data-fal="' . $i['fal'] . '" data-fecha="' . date('Y-m-d', $i['fecha']) . '"';
    };

    $ahoraTs = time();
    $tramo = static function (array $placa): ?array {
        $inicio = $placa['poner_en'] ?: $placa['impresa_en'];
        if (!$inicio) {
            return null;
        }
        $ini = strtotime($inicio);
        if ($placa['acaba_en']) {
            $fin = strtotime($placa['acaba_en']);
        } else {
            $min = (int) ($placa['minutos_reales'] ?: ($placa['minutos_previstos'] ?: $placa['minutos_estimados']));
            if (!$min) {
                return null;
            }
            $fin = $ini + $min * 60;
        }
        return [$ini, $fin];
    };
    $maquinas = [];
    foreach (\App\Models\PiezaPlacaModel::IMPRESORAS as $maq) {
        $enCurso = $siguiente = $ultima = null;
        foreach (array_merge($porImprimir, $impresas) as $placa) {
            if (($placa['impresora'] ?? '') !== $maq) {
                continue;
            }
            $t = $tramo($placa);
            if ($t && $t[0] <= $ahoraTs && $ahoraTs < $t[1]) {
                if (!$enCurso || $t[0] > $enCurso['t'][0]) {
                    $enCurso = ['placa' => $placa, 't' => $t];
                }
            } elseif ($t && $t[1] <= $ahoraTs) {
                if (!$ultima || $t[1] > $ultima['t'][1]) {
                    $ultima = ['placa' => $placa, 't' => $t];
                }
            } elseif (!$placa['impresa_en']) {
                $orden = $t ? $t[0] : PHP_INT_MAX;
                if (!$siguiente || $orden < $siguiente['orden']) {
                    $siguiente = ['placa' => $placa, 't' => $t, 'orden' => $orden];
                }
            }
        }
        $maquinas[$maq] = ['enCurso' => $enCurso, 'siguiente' => $siguiente, 'ultima' => $ultima];
    }
    $ultimas = array_slice($impresas, 0, 6);
?>

<div class="fv pl" id="pl">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><span>Placas</span></nav>
        <div class="fv-acciones">
            <a href="<?= site_url('piezas/galeria') ?>" class="fv-gbtn"><i class="bi bi-grid-3x3-gap"></i> Galería</a>
            <a href="<?= site_url('piezas/pedidos') ?>" class="fv-gbtn"><i class="bi bi-cart-check"></i> Pedidos</a>
            <a href="<?= site_url('piezas/existencias') ?>" class="fv-gbtn"><i class="bi bi-boxes"></i> Existencias</a>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/placas?nueva=0') ?>" class="fv-gbtn" title="Volver a la vista de siempre"><i class="bi bi-arrow-left-right"></i> Vista clásica</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1>Placas</h1>
        <div class="fv-meta">
            <span><b><?= count($impresas) ?></b> impresas</span>
            <?php if ($porImprimir): ?><span class="fv-estado e-acc"><?= count($porImprimir) ?> por imprimir</span><?php endif; ?>
            <?php if ($sinJuzgar): ?><span class="fv-estado e-info"><?= count($sinJuzgar) ?> sin juzgar</span><?php endif; ?>
            <span class="fv-dot"></span>
            <span><b><?= (int) $totalPlacasSiempre ?></b> desde siempre</span>
        </div>
    </div>

    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>
    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>

    <?php if (!$hayPlacas): ?>
        <p class="fv-vacio">Todavía no hay ninguna placa. En cuanto guardes o descargues alguna desde la galería, aparecerá aquí.</p>
    <?php else: ?>
    <div class="pl-cuerpo">
        <aside class="pl-side">
            <label class="pl-buscar">
                <i class="bi bi-search"></i>
                <input type="search" id="plBuscar" placeholder="Buscar pieza impresa o #placa" autocomplete="off" aria-label="Buscar placas por las piezas que llevan">
            </label>

            <section class="fv-panel pl-filtros">
                <div class="pl-lbl">Estado</div>
                <div class="pl-fl" data-filtro="estado">
                    <button type="button" data-v="" class="on">Todas <span class="n"><?= count($impresas) + count($porImprimir) ?></span></button>
                    <?php if ($porImprimir): ?>
                        <button type="button" data-v="imprimir"><span class="fv-estado e-acc">Por imprimir</span> <span class="n"><?= count($porImprimir) ?></span></button>
                    <?php endif; ?>
                    <button type="button" data-v="sinj"><span class="fv-estado e-info">Sin juzgar</span> <span class="n"><?= count($sinJuzgar) ?></span></button>
                    <button type="button" data-v="juzgadas"><span class="fv-estado e-neu">Juzgadas</span> <span class="n"><?= count($impresas) - count($sinJuzgar) ?></span></button>
                </div>
                <div class="pl-lbl">Máquina</div>
                <div class="pl-chips" data-filtro="maq">
                    <button type="button" data-v="" class="on">Las dos</button>
                    <button type="button" data-v="OLD">OLD <span class="n"><?= $nOld ?></span></button>
                    <button type="button" data-v="NEW">NEW <span class="n"><?= $nNew ?></span></button>
                </div>
                <?php $fepLimite = \App\Models\PiezaPlacaModel::FEP_LIMITE; ?>
                <?php foreach ((new \App\Models\PiezaPlacaModel())->despeguesFep() as $imp => $f): ?>
                    <?php $col = ['ok' => 'var(--fv-ok)', 'aviso' => 'var(--fv-warn)', 'peligro' => 'var(--fv-bad)', 'superado' => 'var(--fv-bad)'][$f['nivel']]; ?>
                    <div class="pl-fep" title="Despegues del FEP de <?= $imp ?>: capas impresas. Se renueva a los <?= number_format($fepLimite, 0, ',', '.') ?>.">
                        <div class="pl-fep-t"><span>FEP <?= $imp ?></span><span class="fv-mono" style="color: <?= $col ?>"><?= number_format($f['capas'], 0, ',', '.') ?> / <?= number_format($fepLimite / 1000) ?>k</span></div>
                        <div class="pl-fep-b"><i style="width: <?= min(100, round($f['pct'] * 100)) ?>%; background: <?= $col ?>"></i></div>
                        <?php if ($f['nivel'] === 'superado'): ?>
                            <div class="pl-fep-av" style="color: <?= $col ?>"><i class="bi bi-exclamation-triangle-fill"></i> Superado: toca cambiar el FEP</div>
                        <?php elseif ($f['nivel'] !== 'ok'): ?>
                            <div class="pl-fep-av" style="color: <?= $col ?>"><i class="bi bi-exclamation-triangle"></i> Quedan <?= number_format($fepLimite - $f['capas'], 0, ',', '.') ?> despegues</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <div class="pl-lbl">Mes</div>
                <div class="pl-fl" data-filtro="mes">
                    <button type="button" data-v="" class="on">Todos <span class="n"><?= count($impresas) ?></span></button>
                    <?php foreach ($porMes as $mes => $lista): ?>
                        <button type="button" data-v="<?= esc($mes, 'attr') ?>"><?= esc($mes) ?> <span class="n"><?= count($lista) ?></span></button>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="fv-panel pl-resumen" id="plResumen">
                <div class="pl-lbl" data-res-titulo></div>
                <div class="pl-kpis">
                    <div><span class="v" data-res="placas">0</span><span class="k">placas</span></div>
                    <div><span class="v"><span data-res="horas">0</span><small>h</small></span><span class="k">de máquina</span></div>
                    <div><span class="v"><span data-res="resina">0</span><small>g</small></span><span class="k">resina estimada</span></div>
                    <div><span class="v"><span data-res="fallos">0</span><small>%</small></span><span class="k">unidades fallidas</span></div>
                </div>
                <div class="pl-lbl">Veredictos</div>
                <div class="pl-cuad" data-res="cuadros"></div>
                <div class="pl-lbl">Placas por semana · últimas 8</div>
                <div class="pl-spark" data-res="semanas"></div>
            </section>

            <?php
                $diasImpresion = [];
                foreach ($impresas as $placa) {
                    $diasImpresion[date('Y-m-d', strtotime($placa['impresa_en']))][] = '#' . (int) $placa['id'] . (!empty($placa['impresora']) ? ' ' . $placa['impresora'] : '');
                }
                $hoyCal = new \DateTimeImmutable('today');
                $mesesCal = [$hoyCal->modify('first day of last month'), $hoyCal->modify('first day of this month')];
            ?>
            <section class="fv-panel pl-cal">
                <?php foreach ($mesesCal as $inicio): ?>
                    <?php
                        $diasMes = (int) $inicio->format('t');
                        $hueco = (int) $inicio->format('N') - 1;
                        $conImpresion = 0;
                        for ($d = 1; $d <= $diasMes; $d++) {
                            $conImpresion += isset($diasImpresion[$inicio->format('Y-m-') . sprintf('%02d', $d)]) ? 1 : 0;
                        }
                    ?>
                    <div class="pl-cal-mes">
                        <div class="pl-cal-h">
                            <span><?= esc(ucfirst($meses[(int) $inicio->format('n') - 1])) ?> <?= $inicio->format('Y') ?></span>
                            <span class="n"><?= $conImpresion ?> día<?= $conImpresion === 1 ? '' : 's' ?></span>
                        </div>
                        <div class="pl-cal-g">
                            <?php foreach (['L', 'M', 'X', 'J', 'V', 'S', 'D'] as $dn): ?><span class="dn"><?= $dn ?></span><?php endforeach; ?>
                            <?php for ($i = 0; $i < $hueco; $i++): ?><span></span><?php endfor; ?>
                            <?php for ($d = 1; $d <= $diasMes; $d++): ?>
                                <?php
                                    $clave = $inicio->format('Y-m-') . sprintf('%02d', $d);
                                    $placasDia = $diasImpresion[$clave] ?? [];
                                    $esHoy = $clave === $hoyCal->format('Y-m-d');
                                ?>
                                <span class="d<?= $placasDia ? ' imp' . (count($placasDia) > 1 ? ' mas' : '') : '' ?><?= $esHoy ? ' hoy' : '' ?>"
                                    <?= $placasDia ? 'title="' . esc($d . ': ' . implode(', ', $placasDia), 'attr') . '"' : '' ?>><?= $d ?></span>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>
        </aside>

        <div class="pl-main">
            <section class="pl-ahora" id="plAhora">
                <?php foreach ($maquinas as $maq => $m): ?>
                    <?php
                        $actual = $m['enCurso'] ?? null;
                        $porSacar = !$actual && $m['ultima'] && empty($m['ultima']['placa']['veredicto']) && $ahoraTs - $m['ultima']['t'][1] < 18 * 3600 ? $m['ultima'] : null;
                        $foco = $actual ?: $porSacar;
                        $estado = $actual ? 'imprimiendo' : ($porSacar ? 'sacar' : 'libre');
                    ?>
                    <div class="fv-panel pl-maquina est-<?= $estado ?>">
                        <div class="pl-maq-h">
                            <span class="pl-maq <?= $maq ?>"><?= $maq ?></span>
                            <?php if ($estado === 'imprimiendo'): ?>
                                <span class="fv-estado e-info pl-vivo">Imprimiendo</span>
                            <?php elseif ($estado === 'sacar'): ?>
                                <span class="fv-estado e-warn">Terminó a las <?= date('H:i', $porSacar['t'][1]) ?> · por sacar</span>
                            <?php else: ?>
                                <span class="fv-estado e-neu">Libre<?= $m['ultima'] ? ' desde ' . (date('Y-m-d', $m['ultima']['t'][1]) === date('Y-m-d') ? 'las ' . date('H:i', $m['ultima']['t'][1]) : 'el ' . date('d/m', $m['ultima']['t'][1])) : '' ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($foco): ?>
                            <?php $i = $info($foco['placa']); ?>
                            <a href="<?= site_url('piezas/placa/' . $i['id'] . '/bitacora/editar') ?>" class="pl-maq-placa">
                                <span class="pl-ths"><?= $mini($i['fotos'], 3, 'pl-th g') ?></span>
                                <span class="pl-maq-t">
                                    <span class="n1"><span class="fv-mono">#<?= $i['id'] ?></span> · <?= esc($i['nombre']) ?></span>
                                    <span class="n2"><?= count($i['lista']) ?> piezas · <?= $i['uds'] ?> uds</span>
                                </span>
                            </a>
                            <?php if ($actual): ?>
                                <div class="pl-prog" data-inicio="<?= $actual['t'][0] * 1000 ?>" data-fin="<?= $actual['t'][1] * 1000 ?>">
                                    <div class="pl-prog-b"><i style="width: <?= round(($ahoraTs - $actual['t'][0]) * 100 / max(1, $actual['t'][1] - $actual['t'][0])) ?>%"></i></div>
                                    <div class="pl-prog-t"><span data-quedan></span><span>acaba a las <b><?= date('H:i', $actual['t'][1]) ?></b></span></div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($m['siguiente']): ?>
                            <?php $sg = $m['siguiente']; ?>
                            <a href="<?= site_url('piezas/placa/' . (int) $sg['placa']['id'] . '/bitacora/editar') ?>" class="pl-maq-sig">
                                <span class="t3">Después</span>
                                <span class="fv-mono">#<?= (int) $sg['placa']['id'] ?></span>
                                <span class="nm"><?= esc(trim(preg_replace('/^#\d+\s*(OLD|NEW)?\s*/', '', (string) $sg['placa']['nombre']))) ?></span>
                                <?php if ($sg['t']): ?><span class="e-acc"><?= date('Y-m-d', $sg['t'][0]) === date('Y-m-d') ? date('H:i', $sg['t'][0]) : date('d/m H:i', $sg['t'][0]) ?></span><?php endif; ?>
                            </a>
                        <?php elseif (!$foco): ?>
                            <div class="pl-maq-sig t3">Nada en cola para esta máquina.</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>

            <?php if ($porImprimir || $sinJuzgar): ?>
                <div class="pl-bandeja" id="plBandeja">
                    <?php if ($porImprimir): ?>
                        <section class="fv-panel pl-cola" data-bandeja="imprimir">
                            <div class="fv-ph"><h3>Por imprimir</h3><span class="cuenta"><?= count($porImprimir) ?></span></div>
                            <?php foreach ($porImprimir as $placa): ?>
                                <?php $i = $info($placa); $cuadros = $cuadrosPorPlaca[$i['id']] ?? ['porcentajeUsado' => 0, 'sinMedir' => 0]; ?>
                                <div class="pl-cola-it" data-abrir-placa data-placa="<?= $i['id'] ?>" data-buscar="<?= esc($i['buscar'], 'attr') ?>" data-maq="<?= esc($placa['impresora'] ?? '', 'attr') ?>" title="Abrir la bitácora de esta placa">
                                    <span class="pl-ths"><?= $mini($i['fotos'], 3) ?></span>
                                    <div class="pl-cola-t">
                                        <div class="n1"><span class="fv-mono">#<?= $i['id'] ?></span> · <?= esc($i['nombre']) ?></div>
                                        <div class="n2">
                                            <?php if (!empty($placa['impresora'])): ?><span class="pl-maq <?= esc($placa['impresora'], 'attr') ?>"><?= esc($placa['impresora']) ?></span><?php endif; ?>
                                            <span><?= count($i['lista']) ?> piezas · <?= $i['uds'] ?> uds</span>
                                            <?php if ($cuadros['porcentajeUsado'] > 0 || $cuadros['sinMedir'] > 0): ?>
                                                <span title="Porcentaje ocupado de la plataforma<?= $cuadros['sinMedir'] > 0 ? ' (' . $cuadros['sinMedir'] . ' pieza(s) sin medir)' : '' ?>"><?= round($cuadros['porcentajeUsado']) ?>%<?= $cuadros['sinMedir'] > 0 ? '+' : '' ?> de plataforma</span>
                                            <?php endif; ?>
                                            <span>creada <?= esc(date('d/m H:i', strtotime($placa['creado_en']))) ?></span>
                                            <?php if (!empty($placa['poner_en'])): ?><span class="e-acc">poner <?= esc(date('d/m H:i', strtotime($placa['poner_en']))) ?></span><?php endif; ?>
                                        </div>
                                        <?= $procedencia($placa) ?>
                                    </div>
                                    <a href="<?= site_url('piezas/placa/' . $i['id'] . '/enviar') ?>" class="fv-btn fv-btn-sm<?= $placa['enviada_en'] ? '' : ' fv-btn-pri' ?>"
                                        title="<?= $placa['enviada_en'] ? 'Enviada el ' . esc(date('d/m H:i', strtotime($placa['enviada_en'])), 'attr') . ' — volver a enviar' : 'Enviar placa' ?>">
                                        <i class="bi <?= $placa['enviada_en'] ? 'bi-send-check' : 'bi-send' ?>"></i> <?= $placa['enviada_en'] ? 'Enviada' : 'Enviar' ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </section>
                    <?php endif; ?>
                    <?php if ($sinJuzgar): ?>
                        <section class="fv-panel pl-cola" data-bandeja="sinj">
                            <div class="fv-ph"><h3>Por juzgar</h3><span class="cuenta"><?= count($sinJuzgar) ?></span></div>
                            <?php foreach (array_slice($sinJuzgar, 0, 4) as $placa): ?>
                                <?php $i = $infos[(int) $placa['id']]; ?>
                                <div class="pl-cola-it" data-abrir-placa data-placa="<?= $i['id'] ?>">
                                    <span class="pl-ths"><?= $mini($i['fotos'], 2) ?></span>
                                    <div class="pl-cola-t">
                                        <div class="n1"><span class="fv-mono">#<?= $i['id'] ?></span> · <?= esc($i['nombre']) ?></div>
                                        <div class="n2">
                                            <span><?= esc(date('d/m', $i['fecha'])) ?></span>
                                            <span><?= $i['uds'] ?> uds</span>
                                            <?php if ($i['fal']): ?><span class="e-bad"><?= $i['fal'] ?> fallidas</span><?php endif; ?>
                                            <span><?= $dur($placa['minutos_reales']) ?></span>
                                            <?php if ($i['resumen']['sinResponder'] > 0): ?><span class="e-warn"><i class="bi bi-question-circle"></i> <?= (int) $i['resumen']['sinResponder'] ?> sin responder</span><?php endif; ?>
                                        </div>
                                    </div>
                                    <a href="<?= site_url('piezas/placa/' . $i['id'] . '/bitacora/editar') ?>" class="fv-btn fv-btn-sm">Juzgar <i class="bi bi-arrow-right"></i></a>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($sinJuzgar) > 4): ?>
                                <button type="button" class="fv-link" data-ir-estado="sinj">Ver las <?= count($sinJuzgar) ?> <i class="bi bi-chevron-right"></i></button>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <section class="pl-movil">
                <div class="fv-ph"><h3>Últimas impresas</h3></div>
                <div class="pl-ult">
                    <?php foreach ($ultimas as $placa): ?>
                        <?php $i = $infos[(int) $placa['id']]; ?>
                        <a href="<?= site_url('piezas/placa/' . $i['id'] . '/bitacora/editar') ?>" class="pl-ult-f">
                            <span class="pl-ths"><?= $mini($i['fotos'], 1) ?></span>
                            <span class="pl-ult-t">
                                <span class="n1"><span class="fv-mono">#<?= $i['id'] ?></span> <?= esc($i['nombre']) ?></span>
                                <span class="n2">
                                    <?php if (!empty($placa['impresora'])): ?><span class="pl-maq <?= esc($placa['impresora'], 'attr') ?>"><?= esc($placa['impresora']) ?></span><?php endif; ?>
                                    <span><?= esc(date('d/m', $i['fecha'])) ?></span>
                                    <span><?= $i['uds'] ?> uds</span>
                                    <?php if ($i['fal']): ?><span class="e-bad"><?= $i['fal'] ?> fall.</span><?php endif; ?>
                                </span>
                            </span>
                            <?= $verTexto($i['resumen']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="pl-movil-bot">
                    <button type="button" class="fv-btn" data-movil="historial"><i class="bi bi-clock-history"></i> <span>Historial completo</span> <span class="n"><?= count($impresas) ?></span></button>
                    <button type="button" class="fv-btn" data-movil="filtros"><i class="bi bi-funnel"></i> <span>Buscar y filtrar</span></button>
                </div>
            </section>

            <div class="pl-barra" id="plBarraVista">
                <span class="pl-cuenta"><b id="plCuenta"><?= count($impresas) ?></b> impresas<span id="plDe"></span></span>
                <div class="pi-vistas pl-vistas" role="group" aria-label="Vista">
                    <button type="button" data-vista="tarjetas" title="Tarjetas"><i class="bi bi-grid-3x2-gap"></i> Tarjetas</button>
                    <button type="button" data-vista="registro" title="Registro"><i class="bi bi-list-ul"></i> Registro</button>
                </div>
            </div>

            <p class="fv-vacio d-none" id="plNada">Ninguna placa coincide.</p>

            <div class="pl-vista" data-vista-cuerpo="tarjetas">
                <?php foreach ($porMes as $mes => $lista): ?>
                    <section class="pl-mes" data-mes-grupo="<?= esc($mes, 'attr') ?>">
                        <div class="pl-mes-h"><h3><?= esc($mes) ?></h3><span data-mes-tot></span></div>
                        <div class="pl-gal">
                            <?php foreach ($lista as $placa): ?>
                                <?php $i = $infos[(int) $placa['id']]; $r = $i['resumen']; $idPlaca = $i['id']; ?>
                                <article class="pl-card<?= empty($placa['veredicto']) ? ' sinj' : '' ?>" <?= $atrib($placa, $i) ?> data-abrir-placa title="Abrir la bitácora de esta placa">
                                    <div class="pl-mos n<?= min(6, max(1, count($i['fotos']))) ?>">
                                        <?php if ($i['fotos']): ?>
                                            <?php foreach (array_slice($i['fotos'], 0, 6) as $f): ?><img src="<?= $f ?>" alt="" loading="lazy"><?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="vacia"><i class="bi bi-printer"></i></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="pl-cb">
                                        <div class="r1">
                                            <span class="id">#<?= $idPlaca ?></span>
                                            <?php if (!empty($placa['impresora'])): ?><span class="pl-maq <?= esc($placa['impresora'], 'attr') ?>"><?= esc($placa['impresora']) ?></span><?php endif; ?>
                                            <span class="der"><?= $verTexto($r) ?></span>
                                        </div>
                                        <div class="nm" title="<?= esc($placa['nombre'], 'attr') ?>"><?= esc($i['nombre']) ?></div>
                                        <div class="dt">
                                            <span><?= esc(date('d/m', $i['fecha'])) ?></span>
                                            <span><?= $i['uds'] ?> uds</span>
                                            <?php if ($i['fal']): ?><span class="e-bad"><?= $i['fal'] ?> fall.</span><?php endif; ?>
                                            <?php if ($placa['minutos_reales'] !== null): ?><span><?= $dur($placa['minutos_reales']) ?></span><?php endif; ?>
                                            <?php if ($placa['resina_estimada'] !== null): ?><span><?= $gramos($placa['resina_estimada']) ?></span><?php endif; ?>
                                            <?php if ($r['sinResponder'] > 0): ?><span class="e-warn" title="Preguntas que escribiste antes de imprimir y siguen sin respuesta"><i class="bi bi-question-circle"></i> <?= (int) $r['sinResponder'] ?></span><?php endif; ?>
                                            <?php if ($r['enlaces'] > 0): ?><span title="Enlaces guardados"><i class="bi bi-link-45deg"></i> <?= (int) $r['enlaces'] ?></span><?php endif; ?>
                                        </div>
                                        <?= $procedencia($placa) ?>
                                        <?php if (trim((string) $placa['conclusiones']) !== ''): ?>
                                            <div class="cc"><?= esc($placa['conclusiones']) ?></div>
                                        <?php endif; ?>
                                        <?php include APPPATH . 'Views/piezas/_placa_acciones.php'; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>

            <div class="pl-vista d-none" data-vista-cuerpo="registro">
                <div class="pl-reg">
                    <table>
                        <thead>
                            <tr>
                                <th>Placa</th><th></th><th>Qué llevaba</th><th>Máq.</th><th>Impresa</th>
                                <th class="d">Piezas</th><th class="d">Uds</th><th class="d">Fallidas</th><th class="d">Tiempo</th><th>Exposición</th><th>Veredicto</th>
                            </tr>
                        </thead>
                        <?php foreach ($porMes as $mes => $lista): ?>
                            <tbody data-mes-grupo="<?= esc($mes, 'attr') ?>">
                                <tr class="mes"><td colspan="11"><b><?= esc($mes) ?></b><span data-mes-tot></span></td></tr>
                                <?php foreach ($lista as $placa): ?>
                                    <?php $i = $infos[(int) $placa['id']]; $r = $i['resumen']; ?>
                                    <tr class="fila<?= empty($placa['veredicto']) ? ' sinj' : '' ?>" <?= $atrib($placa, $i) ?> data-abrir-placa title="<?= esc($placa['nombre'], 'attr') ?>">
                                        <td class="id">#<?= $i['id'] ?></td>
                                        <td><span class="pl-ths"><?= $mini($i['fotos'], 2) ?></span></td>
                                        <td class="nom"><?= esc($i['nombre']) ?></td>
                                        <td><?php if (!empty($placa['impresora'])): ?><span class="pl-maq <?= esc($placa['impresora'], 'attr') ?>"><?= esc($placa['impresora']) ?></span><?php endif; ?></td>
                                        <td class="fv-mono t2"><?= esc(date('d/m', $i['fecha'])) ?></td>
                                        <td class="d num"><?= count($i['lista']) ?></td>
                                        <td class="d num"><?= $i['uds'] ?></td>
                                        <td class="d num<?= $i['fal'] ? ' x' : '' ?>"><?= $i['fal'] ?: '—' ?></td>
                                        <td class="d num"><?= $dur($placa['minutos_reales']) ?></td>
                                        <td class="expo" title="<?= esc((string) ($placa['exposicion'] ?? ''), 'attr') ?>"><?= trim((string) ($placa['exposicion'] ?? '')) !== '' ? esc($placa['exposicion']) : '<span class="t3">—</span>' ?></td>
                                        <td><?= $verTexto($r) ?><?php if ($r['sinResponder'] > 0): ?> <span class="e-warn pl-preg" title="Preguntas sin responder"><i class="bi bi-question-circle"></i> <?= (int) $r['sinResponder'] ?></span><?php endif; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var raiz = document.getElementById('pl');
    if (!raiz || !document.getElementById('plBuscar')) return;

    function leer(c, d) { try { return localStorage.getItem(c) || d; } catch (e) { return d; } }
    function guardar(c, v) { try { localStorage.setItem(c, v); } catch (e) {} }

    raiz.addEventListener('click', function (e) {
        var t = e.target.closest('[data-abrir-placa]');
        if (!t || e.target.closest('form, a, button')) return;
        location.href = '<?= site_url('piezas/placa') ?>/' + t.getAttribute('data-placa') + '/bitacora/editar';
    });

    var F = { estado: '', maq: '', mes: '', q: '' };
    var vista = leer('piezas_placas_vista', 'tarjetas');
    var botonesVista = raiz.querySelectorAll('[data-vista]');
    var cuerpos = raiz.querySelectorAll('[data-vista-cuerpo]');
    var bandeja = document.getElementById('plBandeja');

    function horas(m) { return Math.round(m / 60); }
    function num(n) { return Math.round(n).toLocaleString('es-ES'); }
    function pct(f, u) { return u ? (f * 100 / u).toFixed(1).replace('.', ',') : '0'; }
    function dur(m) { var h = Math.floor(m / 60), r = m % 60; return h ? (r ? h + ' h ' + r : h + ' h') : r + ' min'; }
    function suma(items, attr) { return items.reduce(function (a, el) { return a + (parseFloat(el.getAttribute(attr)) || 0); }, 0); }
    function totales(items) {
        return items.length + ' placas · ' + dur(suma(items, 'data-min')) + ' de máquina · ' + num(suma(items, 'data-res')) + ' g resina · '
            + num(suma(items, 'data-uds')) + ' uds · ' + pct(suma(items, 'data-fal'), suma(items, 'data-uds')) + ' % fallidas';
    }

    function coincide(texto) {
        var palabras = F.q.split(/\s+/);
        return texto.split(' | ').some(function (parte) {
            return palabras.every(function (t) { return parte.indexOf(t) !== -1; });
        });
    }

    function pasa(el) {
        var v = el.getAttribute('data-ver');
        if (F.estado === 'imprimir') return false;
        if (F.estado === 'sinj' && v) return false;
        if (F.estado === 'juzgadas' && !v) return false;
        if (F.maq && el.getAttribute('data-maq') !== F.maq) return false;
        if (F.mes && el.getAttribute('data-mes') !== F.mes) return false;
        if (F.q && !coincide(el.getAttribute('data-buscar'))) return false;
        return true;
    }

    function resumen() {
        var cuerpo = raiz.querySelector('[data-vista-cuerpo="tarjetas"]');
        var todas = Array.prototype.slice.call(cuerpo.querySelectorAll('[data-placa-item]'));
        var mes = F.mes || (todas[0] && todas[0].getAttribute('data-mes')) || '';
        var del = todas.filter(function (el) { return el.getAttribute('data-mes') === mes && (!F.maq || el.getAttribute('data-maq') === F.maq); });
        var r = document.getElementById('plResumen');
        r.querySelector('[data-res-titulo]').textContent = mes + (F.maq ? ' · ' + F.maq : '');
        r.querySelector('[data-res="placas"]').textContent = del.length;
        r.querySelector('[data-res="horas"]').textContent = horas(suma(del, 'data-min'));
        r.querySelector('[data-res="resina"]').textContent = num(suma(del, 'data-res'));
        r.querySelector('[data-res="fallos"]').textContent = pct(suma(del, 'data-fal'), suma(del, 'data-uds'));
        var color = { buena: 'var(--fv-ok)', regular: 'var(--fv-warn)', repetir: 'var(--fv-bad)' };
        r.querySelector('[data-res="cuadros"]').innerHTML = del.slice().reverse().map(function (el) {
            var v = el.getAttribute('data-ver');
            return '<i title="#' + el.getAttribute('data-placa') + '" class="' + (v ? '' : 'sin') + '" style="' + (v ? 'background:' + color[v] : '') + '"></i>';
        }).join('');
        var hoy = new Date(); hoy.setHours(0, 0, 0, 0);
        var sem = [0, 0, 0, 0, 0, 0, 0, 0];
        todas.forEach(function (el) {
            if (F.maq && el.getAttribute('data-maq') !== F.maq) return;
            var d = Math.floor((hoy - new Date(el.getAttribute('data-fecha') + 'T00:00:00')) / 604800000);
            if (d >= 0 && d < 8) sem[7 - d]++;
        });
        var max = Math.max.apply(null, sem) || 1;
        r.querySelector('[data-res="semanas"]').innerHTML = sem.map(function (v, i) {
            return '<i title="' + v + ' placa(s)' + (i === 7 ? ' esta semana' : ' hace ' + (7 - i) + ' semana(s)') + '" class="' + (i === 7 ? 'hoy' : '') + '" style="height:' + Math.max(6, v * 100 / max) + '%"></i>';
        }).join('');
    }

    function aplicar() {
        botonesVista.forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.vista === vista ? 'true' : 'false'); });
        cuerpos.forEach(function (c) { c.classList.toggle('d-none', c.getAttribute('data-vista-cuerpo') !== vista || F.estado === 'imprimir'); });
        document.getElementById('plBarraVista').classList.toggle('d-none', F.estado === 'imprimir');

        var visibles = 0;
        cuerpos.forEach(function (c) {
            c.querySelectorAll('[data-mes-grupo]').forEach(function (g) {
                var items = Array.prototype.slice.call(g.querySelectorAll('[data-placa-item]'));
                var ok = items.filter(function (el) { var s = pasa(el); el.classList.toggle('d-none', !s); return s; });
                g.classList.toggle('d-none', ok.length === 0);
                var t = g.querySelector('[data-mes-tot]');
                if (t) t.textContent = ok.length ? totales(ok) : '';
                if (c.getAttribute('data-vista-cuerpo') === 'tarjetas') visibles += ok.length;
            });
        });
        var total = raiz.querySelectorAll('[data-vista-cuerpo="tarjetas"] [data-placa-item]').length;
        document.getElementById('plCuenta').textContent = visibles;
        document.getElementById('plDe').textContent = visibles !== total ? ' de ' + total : '';
        document.getElementById('plNada').classList.toggle('d-none', visibles > 0 || F.estado === 'imprimir');

        if (bandeja) {
            var colaImp = bandeja.querySelector('[data-bandeja="imprimir"]');
            var colaSin = bandeja.querySelector('[data-bandeja="sinj"]');
            if (colaImp) {
                var hay = 0;
                colaImp.querySelectorAll('.pl-cola-it').forEach(function (it) {
                    var s = (!F.maq || it.getAttribute('data-maq') === F.maq)
                        && (!F.q || coincide(it.getAttribute('data-buscar')));
                    it.classList.toggle('d-none', !s);
                    if (s) hay++;
                });
                colaImp.classList.toggle('d-none', !hay || F.estado === 'sinj' || F.estado === 'juzgadas' || !!F.mes);
            }
            if (colaSin) colaSin.classList.toggle('d-none', F.estado === 'imprimir' || F.estado === 'juzgadas' || !!F.q || !!F.mes || !!F.maq);
            bandeja.classList.toggle('d-none', !Array.prototype.some.call(bandeja.children, function (c) { return !c.classList.contains('d-none'); }));
        }
        resumen();
    }

    raiz.querySelectorAll('[data-filtro]').forEach(function (grupo) {
        grupo.addEventListener('click', function (e) {
            var b = e.target.closest('button[data-v]');
            if (!b) return;
            F[grupo.getAttribute('data-filtro')] = b.getAttribute('data-v');
            grupo.querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); });
            aplicar();
        });
    });
    raiz.querySelectorAll('[data-ir-estado]').forEach(function (b) {
        b.addEventListener('click', function () {
            var destino = raiz.querySelector('[data-filtro="estado"] button[data-v="' + b.getAttribute('data-ir-estado') + '"]');
            if (destino) destino.click();
        });
    });
    botonesVista.forEach(function (b) {
        b.addEventListener('click', function () { vista = b.dataset.vista; guardar('piezas_placas_vista', vista); aplicar(); });
    });
    raiz.querySelectorAll('[data-movil]').forEach(function (b) {
        b.addEventListener('click', function () {
            var clase = b.getAttribute('data-movil') === 'filtros' ? 'ver-filtros' : 'ver-historial';
            var abrir = !raiz.classList.contains(clase);
            raiz.classList.toggle(clase, abrir);
            if (clase === 'ver-filtros' && abrir) raiz.classList.add('ver-historial');
            raiz.querySelectorAll('[data-movil]').forEach(function (x) {
                var c = x.getAttribute('data-movil') === 'filtros' ? 'ver-filtros' : 'ver-historial';
                x.classList.toggle('on', raiz.classList.contains(c));
            });
            if (abrir) {
                var destino = clase === 'ver-filtros' ? raiz.querySelector('.pl-side') : document.getElementById('plBarraVista');
                setTimeout(function () { destino.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 30);
            }
        });
    });

    var lateral = raiz.querySelector('.pl-side');
    function ajustarLateral() {
        if (!lateral) return;
        lateral.style.top = Math.min(12, window.innerHeight - lateral.offsetHeight - 12) + 'px';
    }
    ajustarLateral();
    window.addEventListener('resize', ajustarLateral);
    if (window.ResizeObserver && lateral) new ResizeObserver(ajustarLateral).observe(lateral);

    function progreso() {
        raiz.querySelectorAll('.pl-prog').forEach(function (p) {
            var ini = +p.getAttribute('data-inicio'), fin = +p.getAttribute('data-fin'), ahora = Date.now();
            var pct = Math.max(0, Math.min(100, (ahora - ini) * 100 / Math.max(1, fin - ini)));
            p.querySelector('i').style.width = pct + '%';
            var resto = Math.max(0, Math.round((fin - ahora) / 60000));
            var h = Math.floor(resto / 60), m = resto % 60;
            p.querySelector('[data-quedan]').textContent = resto > 0 ? 'quedan ' + (h ? h + ' h ' + (m < 10 ? '0' : '') + m : m + ' min') : 'terminando';
        });
    }
    progreso();
    setInterval(progreso, 30000);

    var caja = document.getElementById('plBuscar');
    caja.addEventListener('input', function () { F.q = caja.value.trim().toLowerCase(); aplicar(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === '/' && !e.target.closest('input, textarea, select')) { e.preventDefault(); caja.focus(); }
    });

    aplicar();
})();
</script>

<?= $this->endSection() ?>
