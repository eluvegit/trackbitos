<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Flag por categoría/familia/variante para decidir qué se manda al catálogo
 * de sterclicks (SterclicksApi::catalogo()). Por defecto visible (1): una
 * pieza nueva se expone tal cual hasta que se marque lo contrario. Ocultar
 * un nivel superior (categoría o familia) oculta todo lo que cuelga de él,
 * aunque la variante en sí siga marcada como visible.
 */
class VisibilidadSterclicks extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_categorias', [
            'visible_sterclicks' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addColumn('piezas_familias', [
            'visible_sterclicks' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addColumn('piezas_variantes', [
            'visible_sterclicks' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_categorias', 'visible_sterclicks');
        $this->forge->dropColumn('piezas_familias', 'visible_sterclicks');
        $this->forge->dropColumn('piezas_variantes', 'visible_sterclicks');
    }
}
