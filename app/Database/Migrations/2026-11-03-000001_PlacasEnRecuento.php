<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Placas impresas cuyo contenido ya entró en existencias con un recuento a
 * mano: no se dan de alta desde la placa para no contarlas dos veces.
 */
class PlacasEnRecuento extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_placas', [
            'inventario_en_recuento' => ['type' => 'DATETIME', 'null' => true, 'after' => 'inventario_sincronizado_en'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_placas', 'inventario_en_recuento');
    }
}
