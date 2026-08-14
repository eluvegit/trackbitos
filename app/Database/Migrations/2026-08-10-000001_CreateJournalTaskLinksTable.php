<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateJournalTaskLinksTable extends Migration
{
    public function up()
    {
        // Enlaces (URL + texto libre opcional) adjuntos a una tarea del
        // Journal, como los materiales pero sin subir archivo.
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'task_id'   => ['type' => 'INT', 'unsigned' => true],
            'url'       => ['type' => 'VARCHAR', 'constraint' => 1000],
            'titulo'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'creado_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('task_id', 'tasks', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('journal_task_links');
    }

    public function down()
    {
        $this->forge->dropTable('journal_task_links', true);
    }
}
