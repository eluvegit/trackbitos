<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CarFaults extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'auto_increment' => true],
            'date'        => ['type' => 'DATE'],
            'kilometers'  => ['type' => 'INT', 'null' => true],
            'notes'       => ['type' => 'TEXT'],
            'created_at'  => ['type' => 'DATETIME', 'default' => 'CURRENT_TIMESTAMP'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('car_faults');
    }

    public function down()
    {
        $this->forge->dropTable('car_faults');
    }
}
