<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFiltrosABraintogramMensajes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('braintogram_mensajes', [
            // NULL = no hay whitelist de chat_id configurada (fase de pruebas, no se filtra nada)
            'chat_autorizado' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'after' => 'secret_valido'],
            // NULL = no se llegó a evaluar (se cortó antes, en secret o chat_id)
            'rate_limited'    => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'after' => 'chat_autorizado'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('braintogram_mensajes', ['chat_autorizado', 'rate_limited']);
    }
}
