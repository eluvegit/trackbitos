<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBraintogramMensajes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'auto_increment' => true, 'unsigned' => true],
            // update_id de Telegram, útil para detectar reintentos/duplicados
            'update_id'      => ['type' => 'BIGINT', 'null' => true],
            // message | edited_message | channel_post | callback_query | otro | invalido
            'tipo'           => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'chat_id'        => ['type' => 'BIGINT', 'null' => true],
            'chat_type'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'from_id'        => ['type' => 'BIGINT', 'null' => true],
            'from_username'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'from_nombre'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'texto'          => ['type' => 'TEXT', 'null' => true],
            // Fecha/hora que Telegram reporta para el mensaje (no la de llegada al servidor)
            'fecha_telegram' => ['type' => 'DATETIME', 'null' => true],
            'ip_origen'      => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            // NULL = no había secret configurado todavía; 1/0 = verificado contra el secret token
            'secret_valido'  => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'raw_json'       => ['type' => 'LONGTEXT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('update_id');
        $this->forge->addKey('chat_id');
        $this->forge->addKey('created_at');
        $this->forge->createTable('braintogram_mensajes');
    }

    public function down()
    {
        $this->forge->dropTable('braintogram_mensajes', true);
    }
}
