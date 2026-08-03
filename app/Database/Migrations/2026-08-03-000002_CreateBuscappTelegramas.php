<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBuscappTelegramas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'auto_increment' => true, 'unsigned' => true],
            // Idempotencia: reintentos de red desde la app no duplican el envío.
            'uuid_cliente' => ['type' => 'VARCHAR', 'constraint' => 36],
            'emisor_id'    => ['type' => 'INT', 'unsigned' => true],
            'grupo_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'modo'         => ['type' => 'ENUM', 'constraint' => ['individual', 'despacho', 'circular'], 'default' => 'individual'],
            'tipo'         => ['type' => 'ENUM', 'constraint' => ['LLAMAME', 'CONFIRMA', 'INFO']],
            'mensaje'      => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'urgencia'     => ['type' => 'ENUM', 'constraint' => ['normal', 'urgente'], 'default' => 'normal'],
            'caduca_en'    => ['type' => 'DATETIME', 'null' => true],
            'enviado_en'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('uuid_cliente');
        $this->forge->addKey('emisor_id');
        $this->forge->addForeignKey('emisor_id', 'buscapp_usuarios', 'id', '', 'CASCADE');
        $this->forge->createTable('buscapp_telegramas');
    }

    public function down()
    {
        $this->forge->dropTable('buscapp_telegramas', true);
    }
}
