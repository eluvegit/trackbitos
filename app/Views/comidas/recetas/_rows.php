<?php if (empty($rows)): ?>
  <tr>
    <td colspan="2" class="text-muted text-center py-4">Sin resultados</td>
  </tr>
<?php else: ?>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td>
        <div class="fw-semibold"><?= esc($r['nombre']) ?></div>
        <div class="text-muted small">
          <?php if ($r['kcal'] !== null): ?>
            <?= number_format((float)$r['kcal'], 0) ?> kcal ·
            <?= number_format((float)$r['proteina_g'], 1) ?> g prot <br>
            <?= number_format((float)$r['carbohidratos_g'], 1) ?> g carb ·
            <?= number_format((float)$r['grasas_g'], 1) ?> g grasa
          <?php else: ?>
            — Macros no calculados —
          <?php endif; ?>
        </div>
      </td>
      <td class="text-end">
        <div class="d-inline-flex gap-1">
          <form action="<?= site_url('comidas/recetas/delete/' . $r['id']) ?>"
                method="post" class="d-inline"
                onsubmit="return confirm('¿Eliminar esta receta y su alimento virtual asociado?');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar" aria-label="Eliminar">
              <i class="bi bi-trash"></i>
              <span class="visually-hidden">Eliminar</span>
            </button>
          </form>
          <a class="btn btn-sm btn-outline-secondary"
             href="<?= site_url('comidas/recetas/edit/' . $r['id']) ?>"
             title="Editar" aria-label="Editar">
            <i class="bi bi-pencil"></i>
            <span class="visually-hidden">Editar</span>
          </a>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
<?php endif; ?>
