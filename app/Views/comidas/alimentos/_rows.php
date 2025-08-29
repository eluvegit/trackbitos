<?php if (empty($rows)): ?>
  <tr>
    <td colspan="3" class="text-muted text-center py-4">Sin resultados</td>
  </tr>
<?php else: ?>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= esc($r['nombre']) ?></td>
      <td class="text-end"><?= esc($r['kcal']) ?></td>
      <td class="text-end">
        <div class="d-inline-flex gap-1">

          <form action="<?= site_url('comidas/alimentos/delete/' . $r['id']) ?>"
                method="post"
                class="d-inline"
                onsubmit="return confirm('¿Eliminar este alimento?');">
            <?= csrf_field() ?>
            <button type="submit"
              class="btn btn-sm btn-outline-danger"
              title="Eliminar" aria-label="Eliminar">
              <i class="bi bi-trash"></i>
              <span class="visually-hidden">Eliminar</span>
            </button>
          </form>

          <a class="btn btn-sm btn-outline-primary"
            href="<?= site_url('comidas/porciones/alimento/' . $r['id']) ?>"
            title="Porciones" aria-label="Porciones">
            <i class="bi bi-collection"></i>
            <span class="visually-hidden">Porciones</span>
          </a>

          <a class="btn btn-sm btn-outline-secondary"
            href="<?= site_url('comidas/alimentos/edit/' . $r['id']) ?>"
            title="Editar" aria-label="Editar">
            <i class="bi bi-pencil"></i>
            <span class="visually-hidden">Editar</span>
          </a>

        </div>
      </td>
    </tr>
  <?php endforeach; ?>
<?php endif; ?>
