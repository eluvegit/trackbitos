<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CarReminders extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'auto_increment' => true],
            'title'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'interval_days'  => ['type' => 'INT', 'null' => true],
            'interval_km'    => ['type' => 'INT', 'null' => true],
            'notes'          => ['type' => 'TEXT', 'null' => true],
            'created_at'     => [
                'type'    => 'TIMESTAMP',
                'null'    => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('car_reminders');
    }

    public function down()
    {
        $this->forge->dropTable('car_reminders');
    }
}
