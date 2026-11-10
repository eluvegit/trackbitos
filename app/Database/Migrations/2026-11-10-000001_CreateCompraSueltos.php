<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompraSueltos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'auto_increment' => true],
            'supermercado_id' => ['type' => 'INT'],
            'nombre'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'comprado'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('supermercado_id');
        $this->forge->createTable('compra_sueltos', true);
    }

    public function down()
    {
        $this->forge->dropTable('compra_sueltos', true);
    }
}
