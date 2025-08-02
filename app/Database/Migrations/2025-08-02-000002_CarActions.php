<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CarActions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'auto_increment' => true],
            'reminder_id'  => ['type' => 'INT', 'null' => true],
            'title'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'date'         => ['type' => 'DATE'],
            'kilometers'   => ['type' => 'INT', 'null' => true],
            'notes'        => ['type' => 'TEXT', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'default' => 'CURRENT_TIMESTAMP'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('reminder_id', 'car_reminders', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('car_actions');
    }

    public function down()
    {
        $this->forge->dropTable('car_actions');
    }
}
