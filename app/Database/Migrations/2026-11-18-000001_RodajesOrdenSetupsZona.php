<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Orden de los montajes (setups) dentro de cada zona del plan: según por dónde
 * se llegue a la zona conviene montar antes uno u otro. JSON con los ids de
 * setup en orden; NULL = el orden general de los setups.
 */
class RodajesOrdenSetupsZona extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('orden_setups', 'rodajes_ubicaciones')) {
            $this->forge->addColumn('rodajes_ubicaciones', [
                'orden_setups' => ['type' => 'TEXT', 'null' => true, 'after' => 'orden'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('orden_setups', 'rodajes_ubicaciones')) {
            $this->forge->dropColumn('rodajes_ubicaciones', 'orden_setups');
        }
    }
}
