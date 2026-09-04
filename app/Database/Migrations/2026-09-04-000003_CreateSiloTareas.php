<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cola de trabajo del agente `.py`: la web decide qué toca hacer, el
 * agente la sondea (o la recibe en el `handshake`) y reporta resultado.
 * `unidad_id` nulo = tarea que no depende de una unidad concreta.
 * `payload`/`resultado` van en JSON de texto (mismo criterio que
 * `silo_unidades.fichero_control`), sin tipo JSON nativo para no atarse a
 * una versión concreta de MySQL. Primer uso real: `escaneo_maestro`,
 * disparada por `POST /api/silo/agente/escaneo` desde el propio agente
 * (auto-reporte), no todavía por aprobación humana desde la web.
 */
class CreateSiloTareas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'unidad_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'tipo'           => ['type' => 'VARCHAR', 'constraint' => 40],
            'payload'        => ['type' => 'TEXT', 'null' => true],
            'estado'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pendiente'],
            'aprobada'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'resultado'      => ['type' => 'TEXT', 'null' => true],
            'error'          => ['type' => 'TEXT', 'null' => true],
            'creado_en'      => ['type' => 'DATETIME', 'null' => true],
            'actualizado_en' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('unidad_id');
        $this->forge->addKey('estado');
        $this->forge->addForeignKey('unidad_id', 'silo_unidades', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('silo_tareas');
    }

    public function down()
    {
        $this->forge->dropTable('silo_tareas', true);
    }
}
