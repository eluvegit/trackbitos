<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sustituye `unidad_fisica` (texto libre) por una referencia real a
 * `silo_unidades` — la unidad física deja de ser un nombre suelto y pasa a
 * ser la entidad que la API rastreará (plan Silo, corrección de rumbo
 * 2026-09-03: Silo gestiona lo que ingesta la API desde unidades reales,
 * no texto tecleado a mano).
 */
class AddUnidadIdToSiloUbicaciones extends Migration
{
    public function up()
    {
        $this->forge->dropColumn('silo_ubicaciones', 'unidad_fisica');

        $this->forge->addColumn('silo_ubicaciones', [
            'unidad_id' => ['type' => 'INT', 'unsigned' => true, 'after' => 'pieza_id'],
        ]);
        $this->forge->addKey('unidad_id');
        $this->forge->addForeignKey('unidad_id', 'silo_unidades', 'id', 'CASCADE', 'CASCADE');
        $this->forge->processIndexes('silo_ubicaciones');
    }

    public function down()
    {
        $this->forge->dropForeignKey('silo_ubicaciones', 'silo_ubicaciones_unidad_id_foreign');
        $this->forge->dropColumn('silo_ubicaciones', 'unidad_id');

        $this->forge->addColumn('silo_ubicaciones', [
            'unidad_fisica' => ['type' => 'VARCHAR', 'constraint' => 100, 'after' => 'pieza_id'],
        ]);
    }
}
