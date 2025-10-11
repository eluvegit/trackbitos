<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<style>
    .storyboard-group { margin-bottom: 2rem; }
    .storyboard-group h2 {
        font-size: 16px;
        margin-bottom: .5rem;
        border-bottom: 2px solid #ccc;
        padding-bottom: .25rem;
    }
    .storyboard-scenes { display: flex; flex-wrap: wrap; gap: 16px; }
    .storyboard-scene {
        width: 220px;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 6px;
        background: #fff;
        font-size: 12px;
    }
    .scene-images { display: flex; justify-content: flex-start; align-items: flex-start; gap: 6px; margin-bottom: 6px; flex-wrap: wrap; }
    .scene-images img {
        width: 100%; height: auto; object-fit: cover; border-radius: 4px;
        border: 1px solid rgba(0,0,0,.12); max-height: 220px;
    }
    .scene-info strong { display: block; font-size: 13px; margin-bottom: 2px; }
    .scene-info .small { font-size: 11px; color: #666; }

    .scene-images-2 img { flex: 1 1 50%; }
    .scene-images-1 img { flex: 1 1 100%; }

    @media print {
        .no-print { display: none !important; }
        .scene-images { gap: 3mm; margin-bottom: 3mm; }
        .scene-images img { max-height: 45mm; border: 1px solid #000; border-radius: 0; }
    }

    .link-muted { color: #6c757d; text-decoration: none; }
    .link-muted:hover { color: #0d6efd; text-decoration: underline; }
</style>

<div class="container py-3">
    <h1 class="mb-4">Storyboard — por clasificación (<?= esc($proyecto['titulo']) ?>)</h1>
    <div class="d-flex gap-2 mb-3">
        <a class="btn btn-outline-secondary" href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas') ?>">← Volver</a>
    </div>

    <div class="card mb-3 no-print">
  <div class="card-body">
    <form class="row g-2 align-items-end" method="get" action="">
      <div class="col-md-6">
        <label class="form-label mb-1">Filtrar por clasificación</label>
        <select name="q[]" class="form-select" multiple size="4">
          <?php foreach ($clasificaciones as $c): 
                $selected = (isset($q) && in_array($c['title'], (array)$q, true)) ? 'selected' : ''; ?>
            <option value="<?= esc($c['title']) ?>" <?= $selected ?>>
              <?= esc($c['title']) ?> (<?= (int)$c['count'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <small class="text-muted">Puedes seleccionar varias (Ctrl/Cmd + click).</small>
      </div>
      <div class="col-md-6 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Aplicar filtro</button>
        <a class="btn btn-outline-secondary" href="<?= current_url() ?>">Quitar filtro</a>
        <button class="btn btn-outline-dark" onclick="window.print();return false;">Imprimir</button>
      </div>
    </form>
  </div>
</div>


    <?php foreach ($groups as $g): ?>
        <div class="storyboard-group">
            <h2><?= esc($g['_title']) ?></h2>
            <div class="storyboard-scenes">
                <?php foreach ($g['items'] as $item): $esc = $item['escena']; ?>
                    <div class="storyboard-scene">
                        <?php
                            $hasLugar = !empty($item['cover_lugar']);
                            $hasInsp  = !empty($item['cover_insp']);
                            $count    = ($hasLugar ? 1 : 0) + ($hasInsp ? 1 : 0);
                        ?>
                        <div class="scene-images scene-images-<?= $count ?>">
                            <?php if ($hasLugar): ?>
                                <img src="<?= base_url($item['cover_lugar']) ?>" alt="Lugar/Objetos">
                            <?php endif; ?>
                            <?php if ($hasInsp): ?>
                                <img src="<?= base_url($item['cover_insp']) ?>" alt="Inspiración">
                            <?php endif; ?>
                        </div>

                        <div class="scene-info">
                            <strong><?= esc($esc['escena_bloque']) ?> · Orden <?= esc($esc['orden']) ?></strong>

                            <?php if (!empty($esc['plano_hora_dia'])): ?>
                                <div class="small">Clasificación: <?= esc($esc['plano_hora_dia']) ?></div>
                            <?php endif; ?>

                            <?php if (!empty($esc['escena_tomas'])): ?>
                                <div class="small">Toma/s: <?= esc($esc['escena_tomas']) ?></div>
                            <?php endif; ?>

                            <?php if (!empty($esc['escena_descripcion'])): ?>
                                <div class="small">Descripción: <?= esc($esc['escena_descripcion']) ?></div>
                            <?php endif; ?>

                            <?php if (!empty($esc['plano_ref_inspiracion_texto'])): ?>
                                <div class="small my-4">
                                    Enlaces referencia:
                                    <ul class="mb-0">
                                        <?php
                                        $lines = preg_split('/\r\n|\r|\n/', trim($esc['plano_ref_inspiracion_texto']));
                                        $n = 1;
                                        foreach ($lines as $line) {
                                            $line = trim($line);
                                            if (!$line) continue;

                                            if (preg_match('#^https?://#i', $line)) {
                                                $host = parse_url($line, PHP_URL_HOST);
                                                echo '<li><a href="' . esc($line) . '" target="_blank" rel="noopener">'
                                                    . esc($host) . ' + enlace ' . $n
                                                    . '</a></li>';
                                                $n++;
                                            } else {
                                                echo '<li>' . esc($line) . '</li>';
                                            }
                                        }
                                        ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <a class="link-muted mt-5 no-print"
                               href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/edit/' . $esc['id']) ?>">
                                ✏️ Editar
                            </a>
                            <a class="link-muted mt-5 no-print"
                               href="<?= site_url('rodajes/' . $proyecto['id'] . '/escenas/show/' . $esc['id']) ?>">
                                👁️ Ver
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>
