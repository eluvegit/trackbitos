<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tareas de Journal fijadas por el usuario para acceso rápido desde el
 * sidebar del dashboard, aparte de los "enlaces rápidos" fijos que ya vivían
 * como lista en código (Dashboard::enlacesRapidos). `task_id` único: fijar
 * una tarea ya fijada no crea una fila duplicada, solo no hace nada.
 */
class CreateDashboardTareasFijadas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'task_id'   => ['type' => 'INT', 'unsigned' => true],
            'orden'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'creado_en' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('task_id');
        $this->forge->addForeignKey('task_id', 'tasks', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('dashboard_tareas_fijadas');
    }

    public function down()
    {
        $this->forge->dropTable('dashboard_tareas_fijadas', true);
    }
}
