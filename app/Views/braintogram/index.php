<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="bt-header mb-3">
    <a href="<?= site_url('dashboard') ?>" class="bt-back"><i class="bi bi-chevron-left"></i> Dashboard</a>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-1">
        <h2 class="bt-title mb-0"><i class="bi bi-cpu text-primary"></i> Braintogram</h2>
        <label class="bt-auto-toggle">
            <input type="checkbox" id="btAuto"> Auto-actualizar (5s)
        </label>
    </div>
</div>

<div class="bt-webhook mb-3">
    <div class="small text-muted mb-1">URL del webhook (para <code>setWebhook</code> cuando el bot esté listo):</div>
    <div class="bt-webhook-url">
        <code><?= esc($webhookUrl) ?></code>
        <button type="button" class="bt-copy" data-copy="<?= esc($webhookUrl) ?>" title="Copiar">
            <i class="bi bi-clipboard"></i>
        </button>
    </div>
</div>

<?php if (empty($mensajes)): ?>
    <p class="text-muted">Todavía no ha llegado ningún mensaje. Prueba con un <code>curl -X POST</code> contra la URL de arriba.</p>
<?php else: ?>
    <div class="bt-list">
        <?php foreach ($mensajes as $m): ?>
            <a href="<?= site_url('braintogram/' . $m['id']) ?>" class="bt-row">
                <div class="bt-row-top">
                    <span class="bt-hora"><?= date('d/m/Y H:i:s', strtotime($m['fecha_telegram'] ?? $m['created_at'])) ?></span>
                    <span class="bt-tipo bt-tipo-<?= esc($m['tipo'] ?? 'otro') ?>"><?= esc($m['tipo'] ?? 'otro') ?></span>
                    <div class="bt-gates">
                        <?php if ($m['secret_valido'] === null): ?>
                            <i class="bi bi-dash-circle bt-secret bt-secret-na" title="Secret: sin configurar"></i>
                        <?php elseif ((int) $m['secret_valido'] === 1): ?>
                            <i class="bi bi-shield-check bt-secret bt-secret-ok" title="Secret: válido"></i>
                        <?php else: ?>
                            <i class="bi bi-shield-exclamation bt-secret bt-secret-bad" title="Secret: inválido"></i>
                        <?php endif; ?>

                        <?php if ($m['chat_autorizado'] === null): ?>
                            <i class="bi bi-dash-circle bt-secret bt-secret-na" title="Chat: sin whitelist configurada"></i>
                        <?php elseif ((int) $m['chat_autorizado'] === 1): ?>
                            <i class="bi bi-person-check bt-secret bt-secret-ok" title="Chat: autorizado"></i>
                        <?php else: ?>
                            <i class="bi bi-person-x bt-secret bt-secret-bad" title="Chat: no autorizado"></i>
                        <?php endif; ?>

                        <?php if ($m['rate_limited'] === null): ?>
                            <i class="bi bi-dash-circle bt-secret bt-secret-na" title="Rate limit: no evaluado"></i>
                        <?php elseif ((int) $m['rate_limited'] === 1): ?>
                            <i class="bi bi-hourglass-split bt-secret bt-secret-bad" title="Rate limit: bloqueado"></i>
                        <?php else: ?>
                            <i class="bi bi-speedometer2 bt-secret bt-secret-ok" title="Rate limit: dentro del límite"></i>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="bt-row-meta">
                    <?php if ($m['chat_id']): ?>
                        <span><i class="bi bi-chat-dots"></i> chat <?= esc($m['chat_id']) ?></span>
                    <?php endif; ?>
                    <?php if ($m['from_username'] || $m['from_nombre']): ?>
                        <span><i class="bi bi-person"></i> <?= esc($m['from_nombre'] ?: '') ?><?= $m['from_username'] ? ' @' . esc($m['from_username']) : '' ?></span>
                    <?php endif; ?>
                    <span class="text-muted"><i class="bi bi-globe"></i> <?= esc($m['ip_origen']) ?></span>
                </div>
                <?php if ($m['texto']): ?>
                    <div class="bt-row-texto"><?= esc(mb_strimwidth($m['texto'], 0, 160, '…')) ?></div>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="mt-3"><?= $pager->links() ?></div>
<?php endif; ?>

<style>
.bt-back { display: inline-flex; align-items: center; font-size: .85rem; color: var(--bs-secondary-color); text-decoration: none; }
.bt-back:hover { color: var(--bs-emphasis-color); }
.bt-title { font-size: 1.35rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; }

.bt-auto-toggle { display: inline-flex; align-items: center; gap: .4rem; font-size: .8rem; color: var(--bs-secondary-color); cursor: pointer; }

.bt-webhook { border: 1px solid var(--bs-border-color); border-radius: 10px; background: var(--bs-tertiary-bg); padding: 10px 12px; }
.bt-webhook-url { display: flex; align-items: center; gap: 8px; font-size: .85rem; word-break: break-all; }
.bt-copy { border: none; background: transparent; color: var(--bs-secondary-color); padding: 2px 6px; border-radius: 6px; flex-shrink: 0; }
.bt-copy:hover { color: var(--bs-emphasis-color); background: var(--bs-body-bg); }

.bt-list { display: flex; flex-direction: column; gap: 8px; }
.bt-row {
    display: block;
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    background: var(--bs-tertiary-bg);
    padding: 10px 12px;
    text-decoration: none;
    color: inherit;
}
.bt-row:hover { border-color: #7c3aed; }

.bt-row-top { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.bt-hora { font-size: .8rem; font-weight: 700; color: var(--bs-emphasis-color); font-variant-numeric: tabular-nums; }
.bt-tipo {
    font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em;
    border-radius: 999px; padding: .1rem .5rem;
    background: rgba(124, 58, 237, .15); color: #a78bfa;
}
.bt-tipo-invalido, .bt-tipo-otro { background: rgba(220, 53, 69, .15); color: #f08a94; }
.bt-gates { margin-left: auto; display: flex; align-items: center; gap: 6px; }
.bt-secret { font-size: .95rem; }
.bt-secret-ok { color: #3fb950; }
.bt-secret-bad { color: #f85149; }
.bt-secret-na { color: var(--bs-secondary-color); }

.bt-row-meta { display: flex; gap: 12px; flex-wrap: wrap; font-size: .78rem; color: var(--bs-secondary-color); margin-top: 4px; }
.bt-row-texto { font-size: .85rem; color: var(--bs-emphasis-color); margin-top: 6px; white-space: pre-wrap; }
</style>

<script>
(function () {
    document.querySelectorAll('.bt-copy').forEach(function (btn) {
        btn.addEventListener('click', function () {
            navigator.clipboard.writeText(btn.dataset.copy).then(function () {
                var icon = btn.querySelector('i');
                icon.className = 'bi bi-check2';
                setTimeout(function () { icon.className = 'bi bi-clipboard'; }, 1200);
            });
        });
    });

    var params = new URLSearchParams(location.search);
    var checkbox = document.getElementById('btAuto');
    if (params.get('auto') === '1') {
        checkbox.checked = true;
        setTimeout(function () { location.reload(); }, 5000);
    }
    checkbox.addEventListener('change', function () {
        params.set('auto', checkbox.checked ? '1' : '0');
        location.search = params.toString();
    });
})();
</script>

<?= $this->endSection() ?>
