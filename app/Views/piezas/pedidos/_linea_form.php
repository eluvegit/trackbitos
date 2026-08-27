<?php
/**
 * El <form> real de esta línea, vacío salvo el CSRF: vive fuera de la tabla
 * (un <form> no puede envolver <tr> sueltas) y todos sus campos, repartidos
 * en las celdas de _linea_fila, se asocian a él por id vía el atributo HTML
 * `form`. Se renderiza aparte para poder reusarlo tal cual también en la
 * respuesta AJAX de "añadir línea" (PedidosController::agregarLinea).
 */
?>
<form id="form-linea-<?= (int) $linea['id'] ?>" method="post"
    action="<?= site_url('piezas/pedido-linea/' . $linea['id'] . '/editar') ?>"><?= csrf_field() ?></form>
