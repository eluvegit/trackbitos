<?php
/**
 * Mensaje guardado a un modelo (estilo fv). Mismo marcado en JS
 * (assets/js/sesiones.js → htmlMensaje).
 *
 * @var array $m fila de sesion_mensajes_modelo
 */
?>
<article class="ss-msg" data-id="<?= (int) $m['id'] ?>">
    <div class="ss-msg-cab">
        <b><?= esc($m['nombre_modelo']) ?></b>
        <span class="t3"><?= esc(date('d/m/Y', strtotime($m['creado_at'] ?? 'now'))) ?></span>
        <div class="der">
            <button type="button" class="fv-ibtn ss-msg-copiar" title="Copiar al portapapeles"><i class="bi bi-clipboard"></i></button>
            <button type="button" class="fv-ibtn ss-msg-borrar" title="Borrar"><i class="bi bi-trash"></i></button>
        </div>
    </div>
    <div class="ss-msg-txt"><?= esc($m['mensaje']) ?></div>
</article>
