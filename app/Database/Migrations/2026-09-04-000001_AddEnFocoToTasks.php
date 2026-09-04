<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEnFocoToTasks extends Migration
{
    public function up()
    {
        // "En foco": subconjunto de las tareas con estrella (is_current) que el
        // usuario elige para centrarse en ellas los próximos días/semanas.
        // Solo tiene sentido cuando is_current = 1; al quitar la estrella en el
        // Journal, este flag se limpia también (ver Journal::toggleCurrent y
        // Journal::edit).
        $this->forge->addColumn('tasks', [
            'en_foco' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'is_current'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tasks', 'en_foco');
    }
}
