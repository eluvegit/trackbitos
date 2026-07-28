<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSesionMensajesModelo extends Migration
{
    public function up()
    {
        // Sustituye al campo único 'mensaje_modelos': puede haber varios
        // modelos/dueños distintos en una misma sesión, cada uno con su
        // propio mensaje (y puede que no tenga model release asociado).
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'auto_increment' => true],
            'sesion_id'        => ['type' => 'INT'],
            'model_release_id' => ['type' => 'INT', 'null' => true],
            'nombre_modelo'    => ['type' => 'VARCHAR', 'constraint' => 150],
            'mensaje'          => ['type' => 'TEXT'],
            'creado_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('sesion_id', 'sesiones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('model_release_id', 'model_releases', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('sesion_mensajes_modelo');

        $this->forge->dropColumn('sesiones', 'mensaje_modelos');
    }

    public function down()
    {
        $this->forge->addColumn('sesiones', [
            'mensaje_modelos' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'entrega_modelos',
            ],
        ]);

        $this->forge->dropTable('sesion_mensajes_modelo', true);
    }
}
