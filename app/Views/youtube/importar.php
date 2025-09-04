<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center mb-3">
  <h1 class="h3 mb-0">Importar vídeos de YouTube</h1>
  <a class="btn btn-outline-secondary btn-sm ms-4" href="<?= site_url('youtube/'.$lista['slug']) ?>">← Volver</a>
</div>

<?php if (!empty($lista)): ?>
  <p class="text-muted mb-3">
    Lista destino: <strong><?= esc($lista['nombre'] ?? ($lista['titulo'] ?? '')) ?></strong>
    <span class="ms-2">(<code><?= esc($lista['slug']) ?></code>)</span>
  </p>
<?php else: ?>
  <div class="alert alert-warning">
    <strong>Atención:</strong> no se encontró la lista objetivo. Revisa el slug en la URL.
  </div>
<?php endif; ?>

<div class="accordion mb-3" id="ejemploJSON">
  <div class="accordion-item">
    <h2 class="accordion-header" id="headingEj">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEj" aria-expanded="false" aria-controls="collapseEj">
        Ver ejemplo de formato JSON admitido
      </button>
    </h2>

    
    <div id="collapseEj" class="accordion-collapse collapse" aria-labelledby="headingEj" data-bs-parent="#ejemploJSON">
      <div class="accordion-body">
        <p class="mb-2">Se aceptan ambos formatos:</p>
        <pre class="bg-light p-3 border rounded small mb-3">[
  {
    "titulo": "elevate your handheld camera skills with these 5 tips.",
    "url": "https://www.youtube.com/watch?v=D2SO4xJ4oqo"
  },
  {
    "titulo": "How I make my YouTube videos (my job)",
    "url": "https://www.youtube.com/watch?v=qFY2eiJ6hUI"
  }
]</pre>
        <pre class="bg-light p-3 border rounded small mb-0">{
  "titulo": "documentary interview lighting made simple with JUST windows",
  "url": "https://www.youtube.com/watch?v=IvzCHx0K4P0"
},
{
  "titulo": "How the 'Severance' Editor Cut the Finale Like a Music Video",
  "url": "https://www.youtube.com/watch?v=eGkfByNEcw0"
}</pre>
        <p class="text-muted small mt-2 mb-0">Si pegas objetos sueltos separados por comas, se envuelven automáticamente en <code>[ ... ]</code>.</p>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <strong>Errores:</strong>
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?>
        <li><?= esc($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post"
      action="<?= !empty($lista) ? site_url('youtube/' . esc($lista['slug']) . '/importar') : '#' ?>"
      onsubmit="return beforeSubmit()"
      class="mb-4">
  <?= csrf_field() ?>

  <div class="mb-2">
    <label for="jsonInput" class="form-label">Pega tu JSON aquí</label>
    <textarea class="form-control" name="json" id="jsonInput" rows="10" placeholder='[{ "titulo": "...", "url": "https://..." }, ...]'><?= esc($oldJson ?? '') ?></textarea>
  </div>
  <div class="d-flex justify-content-between align-items-center">
    <small id="counter" class="text-muted">0 elementos detectados</small>
    <button class="btn btn-primary" type="submit" <?= empty($lista) ? 'disabled' : '' ?>>Importar</button>
  </div>
</form>

<?php if (isset($results) && is_array($results)): ?>
  <?php
    $okCount  = count(array_filter($results, fn($r) => !empty($r['ok'])));
    $badCount = count($results) - $okCount;
    $dupCount = count(array_filter($results, fn($r) => !empty($r['duplicado'])));
  ?>
  <div class="card">
    <div class="card-body">
      <h2 class="h5">Resultado de la importación</h2>
      <p class="text-muted mb-3">
        Procesados: <strong><?= count($results) ?></strong> —
        <span class="text-success">OK: <?= $okCount ?></span> —
        <span class="text-danger">Errores: <?= $badCount ?></span>
        <?php if ($dupCount > 0): ?> —
          <span class="text-secondary">Duplicados: <?= $dupCount ?></span>
        <?php endif; ?>
      </p>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th>Título</th>
              <th>URL</th>
              <th>Video ID</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results as $i => $r): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><?= esc($r['titulo'] ?? '') ?></td>
                <td>
                  <?php if (!empty($r['url'])): ?>
                    <a href="<?= esc($r['url']) ?>" target="_blank" rel="noopener"><?= esc($r['url']) ?></a>
                  <?php endif; ?>
                </td>
                <td class="text-muted"><small><?= esc($r['youtubeId'] ?? '') ?></small></td>
                <td>
                  <?php if (!empty($r['ok'])): ?>
                    <?php if (!empty($r['duplicado'])): ?>
                      <span class="badge bg-secondary">Ya existía</span>
                      <?php if (!empty($r['insert_id'])): ?>
                        <small class="text-muted ms-1">ID: <?= esc($r['insert_id']) ?></small>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="badge bg-success">Importado</span>
                      <?php if (!empty($r['insert_id'])): ?>
                        <small class="text-muted ms-1">ID: <?= esc($r['insert_id']) ?></small>
                      <?php endif; ?>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="badge bg-danger">Error</span>
                    <small class="text-muted d-block"><?= esc($r['error'] ?? 'Desconocido') ?></small>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="text-muted small mb-0">* Validamos/extraemos el ID y guardamos asociando <code>lista_id</code>.</p>
    </div>
  </div>
<?php endif; ?>
<h1 class="mt-5">Instrucciones</h1>
<p class="text-muted small mt-2">
<h5 class="mt-4">Paso 1: instalacion de yt-dlp</h5>
<code>python3 -m pip install -U yt-dlp</code>
<h5 class="mt-4">Paso 2: Descargar url a JSON</h5>
<code>yt-dlp -J --flat-playlist "https://www.youtube.com/playlist?list=PLlMGuLBtJfY26hDtxy79vM4EElhRc4aok" > playlist_raw.json</code>
<h5 class="mt-4">Paso 3: Transformar a formato trackbitos</h5>
<code>jq '[ .entries[]
     | {
         titulo: .title,
         url: ("https://www.youtube.com/watch?v="+.id)
         } ]' playlist_raw.json > import.json</code>
</p>
<script>
  (function(){
    const input = document.getElementById('jsonInput');
    const counter = document.getElementById('counter');

    function countItems(text){
      try{
        let t = (text || '').trim();
        if(!t) return 0;
        if(t[0] !== '['){
          t = '[' + t.replace(/,\s*$/,'') + ']';
        }
        const data = JSON.parse(t);
        return Array.isArray(data) ? data.length : 0;
      }catch(e){ return 0; }
    }

    function updateCounter(){
      if(counter && input){
        counter.textContent = countItems(input.value) + ' elementos detectados';
      }
    }

    window.beforeSubmit = function(){
      <?php if (empty($lista)): ?>
        alert('No hay lista destino. Revisa el slug en la URL.');
        return false;
      <?php endif; ?>
      const n = countItems(input.value);
      if(n === 0){
        return confirm('No se detectaron elementos válidos. ¿Enviar de todas formas?');
      }
      return true;
    }

    if (input){
      input.addEventListener('input', updateCounter);
      updateCounter();
    }
  })();
</script>

<?= $this->endSection() ?>
