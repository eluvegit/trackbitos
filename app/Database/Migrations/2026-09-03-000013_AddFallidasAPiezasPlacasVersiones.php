<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Descartes por línea de la bitácora (fase 56): de las `cantidad` copias
 * impresas de esa versión en la placa, cuántas no valen por roturas,
 * malformaciones o mal diseño. Servibles de la línea = cantidad - fallidas;
 * las de la placa, la suma. 0 = sin descartes.
 */
class AddFallidasAPiezasPlacasVersiones extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_placas_versiones', [
            'fallidas' => ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0, 'after' => 'cantidad'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_placas_versiones', 'fallidas');
    }
}
