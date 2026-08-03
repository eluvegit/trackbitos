<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBuscappTelegramaDestinos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'auto_increment' => true, 'unsigned' => true],
            'telegrama_id'  => ['type' => 'INT', 'unsigned' => true],
            'receptor_id'   => ['type' => 'INT', 'unsigned' => true],
            'canal'         => ['type' => 'ENUM', 'constraint' => ['fcm', 'telegram'], 'default' => 'fcm'],
            'estado'        => [
                'type'       => 'ENUM',
                'constraint' => ['enviado', 'entregado', 'visto', 'respondido', 'anulado_por_despacho', 'caducado'],
                'default'    => 'enviado',
            ],
            'respuesta'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'entregado_en'  => ['type' => 'DATETIME', 'null' => true],
            'respondido_en' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        // Regla de escasez §3.1 bis: una solicitud pendiente por (emisor, receptor).
        // Aquí se aplica sobre (telegrama, receptor) porque el emisor ya identifica
        // el telegrama; la unicidad real se valida en el controlador antes de
        // insertar, comprobando que no exista un destino sin responder/caducar
        // para ese mismo par (emisor, receptor).
        $this->forge->addKey('telegrama_id');
        $this->forge->addKey('receptor_id');
        $this->forge->addForeignKey('telegrama_id', 'buscapp_telegramas', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('receptor_id', 'buscapp_usuarios', 'id', '', 'CASCADE');
        $this->forge->createTable('buscapp_telegrama_destinos');
    }

    public function down()
    {
        $this->forge->dropTable('buscapp_telegrama_destinos', true);
    }
}
