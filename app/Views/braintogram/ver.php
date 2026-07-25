<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="bt-header mb-3">
    <a href="<?= site_url('braintogram') ?>" class="bt-back"><i class="bi bi-chevron-left"></i> Braintogram</a>
    <h2 class="bt-title mb-0 mt-1"><i class="bi bi-envelope-open text-primary"></i> Mensaje #<?= (int) $mensaje['id'] ?></h2>
</div>

<div class="bt-detalle mb-3">
    <dl class="bt-dl">
        <dt>Recibido (servidor)</dt>
        <dd><?= esc($mensaje['created_at']) ?></dd>

        <dt>Fecha Telegram</dt>
        <dd><?= esc($mensaje['fecha_telegram'] ?? '—') ?></dd>

        <dt>Tipo</dt>
        <dd><span class="bt-tipo bt-tipo-<?= esc($mensaje['tipo'] ?? 'otro') ?>"><?= esc($mensaje['tipo'] ?? 'otro') ?></span></dd>

        <dt>Update ID</dt>
        <dd><?= esc($mensaje['update_id'] ?? '—') ?></dd>

        <dt>Chat</dt>
        <dd><?= esc($mensaje['chat_id'] ?? '—') ?> <?= $mensaje['chat_type'] ? '(' . esc($mensaje['chat_type']) . ')' : '' ?></dd>

        <dt>De</dt>
        <dd>
            <?= esc($mensaje['from_nombre'] ?? '—') ?>
            <?= $mensaje['from_username'] ? ' @' . esc($mensaje['from_username']) : '' ?>
            <?= $mensaje['from_id'] ? ' · id ' . esc($mensaje['from_id']) : '' ?>
        </dd>

        <dt>Texto</dt>
        <dd><?= $mensaje['texto'] ? nl2br(esc($mensaje['texto'])) : '<span class="text-muted">—</span>' ?></dd>

        <dt>IP origen</dt>
        <dd><?= esc($mensaje['ip_origen'] ?? '—') ?></dd>

        <dt>1. Secret token</dt>
        <dd>
            <?php if ($mensaje['secret_valido'] === null): ?>
                <span class="text-muted">Sin configurar (no se filtra)</span>
            <?php elseif ((int) $mensaje['secret_valido'] === 1): ?>
                <span class="bt-secret bt-secret-ok"><i class="bi bi-shield-check"></i> Válido</span>
            <?php else: ?>
                <span class="bt-secret bt-secret-bad"><i class="bi bi-shield-exclamation"></i> Inválido — cortado aquí</span>
            <?php endif; ?>
        </dd>

        <dt>2. Chat autorizado</dt>
        <dd>
            <?php if ($mensaje['chat_autorizado'] === null): ?>
                <span class="text-muted">Sin whitelist configurada (no se filtra)</span>
            <?php elseif ((int) $mensaje['chat_autorizado'] === 1): ?>
                <span class="bt-secret bt-secret-ok"><i class="bi bi-person-check"></i> Autorizado</span>
            <?php else: ?>
                <span class="bt-secret bt-secret-bad"><i class="bi bi-person-x"></i> No autorizado — cortado aquí</span>
            <?php endif; ?>
        </dd>

        <dt>3. Rate limit</dt>
        <dd>
            <?php if ($mensaje['rate_limited'] === null): ?>
                <span class="text-muted">No evaluado (cortado en un paso anterior)</span>
            <?php elseif ((int) $mensaje['rate_limited'] === 1): ?>
                <span class="bt-secret bt-secret-bad"><i class="bi bi-hourglass-split"></i> Bloqueado — cortado aquí</span>
            <?php else: ?>
                <span class="bt-secret bt-secret-ok"><i class="bi bi-speedometer2"></i> Dentro del límite</span>
            <?php endif; ?>
        </dd>
    </dl>
</div>

<div class="bt-raw-header d-flex align-items-center justify-content-between">
    <span class="small text-muted">JSON crudo recibido</span>
    <button type="button" class="bt-copy" id="btCopyRaw" title="Copiar JSON">
        <i class="bi bi-clipboard"></i> Copiar
    </button>
</div>
<?php
    $bonito = $mensaje['raw_json'];
    $decoded = json_decode((string) $mensaje['raw_json'], true);
    if ($decoded !== null) {
        $bonito = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
?>
<pre class="bt-raw" id="btRaw"><?= esc($bonito) ?></pre>

<style>
.bt-back { display: inline-flex; align-items: center; font-size: .85rem; color: var(--bs-secondary-color); text-decoration: none; }
.bt-back:hover { color: var(--bs-emphasis-color); }
.bt-title { font-size: 1.25rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; }

.bt-detalle { border: 1px solid var(--bs-border-color); border-radius: 12px; background: var(--bs-tertiary-bg); padding: 12px 16px; }
.bt-dl { display: grid; grid-template-columns: 160px 1fr; gap: 6px 12px; margin: 0; }
.bt-dl dt { font-size: .78rem; color: var(--bs-secondary-color); font-weight: 600; }
.bt-dl dd { margin: 0; font-size: .9rem; color: var(--bs-emphasis-color); word-break: break-word; }

.bt-tipo {
    font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em;
    border-radius: 999px; padding: .1rem .5rem;
    background: rgba(124, 58, 237, .15); color: #a78bfa;
}
.bt-tipo-invalido, .bt-tipo-otro { background: rgba(220, 53, 69, .15); color: #f08a94; }
.bt-secret-ok { color: #3fb950; }
.bt-secret-bad { color: #f85149; }

.bt-copy { border: 1px solid var(--bs-border-color); background: var(--bs-tertiary-bg); color: var(--bs-secondary-color); padding: .15rem .6rem; border-radius: 8px; font-size: .78rem; }
.bt-copy:hover { color: var(--bs-emphasis-color); }

.bt-raw {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 10px;
    padding: 12px;
    font-size: .78rem;
    max-height: 60vh;
    overflow: auto;
    white-space: pre-wrap;
    word-break: break-word;
}

@media (max-width: 576px) {
    .bt-dl { grid-template-columns: 1fr; }
    .bt-dl dt { margin-top: 6px; }
}
</style>

<script>
document.getElementById('btCopyRaw').addEventListener('click', function () {
    navigator.clipboard.writeText(document.getElementById('btRaw').textContent).then(function () {
        var btn = document.getElementById('btCopyRaw');
        btn.innerHTML = '<i class="bi bi-check2"></i> Copiado';
        setTimeout(function () { btn.innerHTML = '<i class="bi bi-clipboard"></i> Copiar'; }, 1200);
    });
});
</script>

<?= $this->endSection() ?>
