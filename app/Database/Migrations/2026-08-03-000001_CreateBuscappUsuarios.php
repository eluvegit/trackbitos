<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBuscappUsuarios extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'auto_increment' => true, 'unsigned' => true],
            'telefono_e164'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'nombre'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'avatar_url'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'fcm_token'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'telegram_chat_id' => ['type' => 'BIGINT', 'null' => true],
            // Token de acceso a la API (Bearer), emitido en el registro. No es
            // sesión de Myth\Auth: la app no puede "hacer login" con cookies.
            'api_token'        => ['type' => 'VARCHAR', 'constraint' => 64],
            'creado_en'        => ['type' => 'DATETIME', 'null' => true],
            'ultimo_acceso'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('telefono_e164');
        $this->forge->addUniqueKey('api_token');
        $this->forge->createTable('buscapp_usuarios');
    }

    public function down()
    {
        $this->forge->dropTable('buscapp_usuarios', true);
    }
}
