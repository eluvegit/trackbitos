<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTimeSpentToSubtasks extends Migration
{
    public function up()
    {
        $this->forge->addColumn('subtasks', [
            // minutos acumulados en esta subtarea; se suman también al time_spent de la tarea
            'time_spent' => ['type' => 'INT', 'default' => 0, 'after' => 'orden'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('subtasks', ['time_spent']);
    }
}
