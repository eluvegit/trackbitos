<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Lentillas — editar un cambio (estilo fv).
 *
 * @var array $sustitucion
 */
use App\Libraries\RecordatorioServicio;

$s = $sustitucion;
$actual = RecordatorioServicio::norm((string) $s['elemento']);
$elementos = RecordatorioServicio::ELEMENTOS;
// Un elemento antiguo escrito a mano que no esté en la lista se conserva como opción.
if (!in_array($actual, array_map([RecordatorioServicio::class, 'norm'], $elementos), true)) $elementos[] = (string) $s['elemento'];
?>
<div class="fv rc estrecho">
    <?= view('recordatorios/_cabecera', [
        'modulo' => 'lentillas',
        'activa' => 'cambios',
        'migas'  => ['Cambios' => site_url('lentillas/sustituciones'), 'Editar' => null],
        'titulo' => 'Editar cambio',
    ]) ?>

    <form class="rc-form" method="post" action="<?= site_url('lentillas/sustituciones/actualizar/' . $s['id']) ?>">
        <?= csrf_field() ?>
        <div class="fv-panel">
            <div class="rc-filas">
                <label class="rc-campo">
                    <span>Qué</span>
                    <select class="rc-sel" name="elemento" required>
                        <?php foreach ($elementos as $el): ?>
                            <option value="<?= esc($el, 'attr') ?>" <?= RecordatorioServicio::norm($el) === $actual ? 'selected' : '' ?>><?= esc(mb_strtoupper(mb_substr($el, 0, 1)) . mb_substr($el, 1)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="rc-campo">
                    <span>Fecha</span>
                    <input type="date" class="fv-input" name="fecha" value="<?= esc(substr((string) $s['fecha'], 0, 10), 'attr') ?>" required>
                </label>
            </div>
            <label class="rc-campo">
                <span>Notas</span>
                <textarea class="fv-input" name="notas" rows="2"><?= esc((string) $s['notas']) ?></textarea>
            </label>
            <p class="rc-info"><i class="bi bi-info-circle"></i><span>Al guardar se recalcula el aviso de ese elemento. El stock no cambia.</span></p>
        </div>
        <div class="rc-pie">
            <div class="der">
                <a class="fv-btn" href="<?= site_url('lentillas/sustituciones') ?>">Cancelar</a>
                <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Guardar</button>
            </div>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
