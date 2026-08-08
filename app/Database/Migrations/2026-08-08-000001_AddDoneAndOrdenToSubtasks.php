<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDoneAndOrdenToSubtasks extends Migration
{
    public function up()
    {
        $this->forge->addColumn('subtasks', [
            // 0 = pendiente, 1 = hecha (tachada)
            'is_done' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'title'],
            'orden'   => ['type' => 'INT', 'default' => 0, 'after' => 'is_done'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('subtasks', ['is_done', 'orden']);
    }
}
