<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="bp-header mb-3">
    <a href="<?= site_url('buscapp') ?>" class="bp-back"><i class="bi bi-chevron-left"></i> Buscapp</a>
    <h2 class="bp-title mb-0 mt-1"><i class="bi bi-envelope-open text-primary"></i> Telegrama #<?= (int) $telegrama['id'] ?></h2>
</div>

<dl class="bp-dl mb-4">
    <dt>Emisor</dt>
    <dd><?= esc($emisor['nombre'] ?? '—') ?></dd>

    <dt>Tipo</dt>
    <dd><span class="badge text-bg-secondary"><?= esc($telegrama['tipo']) ?></span></dd>

    <dt>Urgencia</dt>
    <dd><span class="badge text-bg-<?= $telegrama['urgencia'] === 'urgente' ? 'danger' : 'secondary' ?>"><?= esc($telegrama['urgencia']) ?></span></dd>

    <dt>Mensaje</dt>
    <dd><?= $telegrama['mensaje'] ? nl2br(esc($telegrama['mensaje'])) : '<span class="text-muted">—</span>' ?></dd>

    <dt>Enviado</dt>
    <dd><?= esc($telegrama['enviado_en'] ?? '—') ?></dd>

    <dt>Caduca</dt>
    <dd><?= esc($telegrama['caduca_en'] ?? 'sin caducidad') ?></dd>
</dl>

<h5 class="mb-2">Destinatarios</h5>
<div class="table-responsive">
    <table class="table table-sm align-middle">
        <thead>
            <tr>
                <th>Receptor</th>
                <th>Canal</th>
                <th>Estado</th>
                <th>Respuesta</th>
                <th>Entregado</th>
                <th>Respondido</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($destinos as $d): ?>
                <tr>
                    <td><?= esc($usuariosPorId[$d['receptor_id']]['nombre'] ?? $d['receptor_id']) ?></td>
                    <td><?= esc($d['canal']) ?></td>
                    <td><span class="badge text-bg-secondary"><?= esc($d['estado']) ?></span></td>
                    <td><?= esc($d['respuesta'] ?? '—') ?></td>
                    <td><?= esc($d['entregado_en'] ?? '—') ?></td>
                    <td><?= esc($d['respondido_en'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<style>
.bp-back { display: inline-flex; align-items: center; font-size: .85rem; color: var(--bs-secondary-color); text-decoration: none; }
.bp-back:hover { color: var(--bs-emphasis-color); }
.bp-title { font-size: 1.35rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; }
.bp-dl { display: grid; grid-template-columns: max-content 1fr; column-gap: 12px; row-gap: 6px; }
.bp-dl dt { font-weight: 600; color: var(--bs-secondary-color); }
.bp-dl dd { margin: 0; }
</style>

<?= $this->endSection() ?>
