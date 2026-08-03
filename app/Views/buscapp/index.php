<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="bp-header mb-3">
    <a href="<?= site_url('dashboard') ?>" class="bp-back"><i class="bi bi-chevron-left"></i> Dashboard</a>
    <h2 class="bp-title mb-0 mt-1"><i class="bi bi-broadcast text-primary"></i> Buscapp</h2>
</div>

<h5 class="mb-2">Usuarios (<?= count($usuarios) ?>)</h5>
<?php if (empty($usuarios)): ?>
    <p class="text-muted">Todavía no se ha registrado nadie desde la app.</p>
<?php else: ?>
    <div class="table-responsive mb-4">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Canal</th>
                    <th>Registrado</th>
                    <th>Último acceso</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= esc($u['nombre']) ?></td>
                        <td><?= esc($u['telefono_e164'] ?? '—') ?></td>
                        <td>
                            <?php if ($u['fcm_token']): ?>
                                <span class="badge text-bg-success"><i class="bi bi-phone"></i> app</span>
                            <?php elseif ($u['telegram_chat_id']): ?>
                                <span class="badge text-bg-info"><i class="bi bi-telegram"></i> telegram</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">sin canal</span>
                            <?php endif; ?>
                        </td>
                        <td><?= esc($u['creado_en'] ?? '—') ?></td>
                        <td><?= esc($u['ultimo_acceso'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<h5 class="mb-2">Telegramas enviados</h5>
<?php if (empty($telegramas)): ?>
    <p class="text-muted">Todavía no se ha enviado ningún telegrama.</p>
<?php else: ?>
    <div class="bp-list">
        <?php foreach ($telegramas as $t): ?>
            <a href="<?= site_url('buscapp/' . $t['id']) ?>" class="bp-row">
                <div class="bp-row-top">
                    <span class="bp-hora"><?= esc($t['enviado_en'] ?? '—') ?></span>
                    <span class="badge text-bg-<?= $t['urgencia'] === 'urgente' ? 'danger' : 'secondary' ?>"><?= esc($t['tipo']) ?></span>
                    <span class="text-muted small"><?= esc($t['urgencia']) ?></span>
                </div>
                <?php if ($t['mensaje']): ?>
                    <div class="bp-row-texto"><?= esc(mb_strimwidth($t['mensaje'], 0, 160, '…')) ?></div>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="mt-3"><?= $pager->links() ?></div>
<?php endif; ?>

<style>
.bp-back { display: inline-flex; align-items: center; font-size: .85rem; color: var(--bs-secondary-color); text-decoration: none; }
.bp-back:hover { color: var(--bs-emphasis-color); }
.bp-title { font-size: 1.35rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; }

.bp-list { display: flex; flex-direction: column; gap: 8px; }
.bp-row {
    display: block;
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    background: var(--bs-tertiary-bg);
    padding: 10px 12px;
    text-decoration: none;
    color: inherit;
}
.bp-row:hover { border-color: #7c3aed; }
.bp-row-top { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.bp-hora { font-size: .8rem; font-weight: 700; color: var(--bs-emphasis-color); font-variant-numeric: tabular-nums; }
.bp-row-texto { margin-top: 4px; font-size: .85rem; color: var(--bs-secondary-color); }
</style>

<?= $this->endSection() ?>
