<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Orden manual de una toma dentro de su setup en el plan de rodaje (flechas ↑↓
 * del Plan). NULL = el automático (imprescindibles primero y menos cambios de props).
 */
class RodajesOrdenRodajeToma extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('orden_rodaje', 'rodajes_escenas')) {
            $this->forge->addColumn('rodajes_escenas', [
                'orden_rodaje' => ['type' => 'INT', 'null' => true, 'after' => 'orden'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('orden_rodaje', 'rodajes_escenas')) {
            $this->forge->dropColumn('rodajes_escenas', 'orden_rodaje');
        }
    }
}
