<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRecordatorios extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'auto_increment' => true],
            'titulo'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'categoria'      => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'otro'],
            'icono'          => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'calendar-event'],
            'fecha_evento'   => ['type' => 'DATE'],
            // Si se define, "Renovar" calcula la siguiente fecha sumando estos meses
            'periodo_meses'  => ['type' => 'INT', 'null' => true],
            'notas'          => ['type' => 'TEXT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('recordatorios');
    }

    public function down()
    {
        $this->forge->dropTable('recordatorios', true);
    }
}
