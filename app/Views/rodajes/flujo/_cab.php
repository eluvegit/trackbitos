<?php
$pid = (int) $proyecto['id'];
$sub = '<span>' . $intro . '</span>';
if (!empty($sesion)) $sub .= '<a class="rd-ses" href="' . site_url('sesiones/' . $sesion['id']) . '"><i class="bi bi-camera"></i>' . esc($sesion['titulo']) . '</a>';
?>
<?= view('rodajes/_cabecera', [
    'migas'    => [$proyecto['titulo'] => site_url("rodajes/$pid/escenas"), $pasos[$paso]['txt'] => null],
    'titulo'   => $pasos[$paso]['txt'],
    'sub'      => $sub,
    'acciones' => $acciones ?? '',
    'clasica'  => false,
]) ?>
<?= view('rodajes/flujo/_pasos', ['proyecto' => $proyecto, 'pasos' => $pasos, 'paso' => $paso]) ?>
