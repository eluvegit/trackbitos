<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Imagen del setup (foto del montaje), como la imagen guía de una zona: sin ella no se distingue un setup de otro al elegir. */
class RodajesSetupImagen extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('imagen', 'rodajes_setups')) {
            $this->forge->addColumn('rodajes_setups', [
                'imagen' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'notas'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('imagen', 'rodajes_setups')) {
            $this->forge->dropColumn('rodajes_setups', 'imagen');
        }
    }
}
