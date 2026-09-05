<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFocoOrdenToTasks extends Migration
{
    public function up()
    {
        // Orden manual dentro de la vista Focalizar: el usuario arrastra las
        // tareas en foco para priorizarlas a su criterio, en vez de heredar el
        // orden por categoría/prioridad del Journal. Solo tiene sentido cuando
        // en_foco = 1.
        $this->forge->addColumn('tasks', [
            'foco_orden' => ['type' => 'INT', 'null' => true, 'default' => null, 'after' => 'en_foco'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tasks', 'foco_orden');
    }
}
