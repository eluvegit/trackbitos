<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSubtaskIdToJournalTaskFilesAndLinks extends Migration
{
    public function up()
    {
        // Un material o enlace puede referenciar opcionalmente una subtarea
        // concreta de la tarea, en vez de solo la tarea en general. Si se
        // borra la subtarea, el material/enlace se queda sin asociar (no se
        // borra).
        $this->forge->addColumn('journal_task_files', [
            'subtask_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'task_id'],
        ]);
        $this->forge->addForeignKey('subtask_id', 'subtasks', 'id', 'CASCADE', 'SET NULL');
        $this->forge->processIndexes('journal_task_files');

        $this->forge->addColumn('journal_task_links', [
            'subtask_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'task_id'],
        ]);
        $this->forge->addForeignKey('subtask_id', 'subtasks', 'id', 'CASCADE', 'SET NULL');
        $this->forge->processIndexes('journal_task_links');
    }

    public function down()
    {
        $this->forge->dropForeignKey('journal_task_files', 'journal_task_files_subtask_id_foreign');
        $this->forge->dropColumn('journal_task_files', 'subtask_id');

        $this->forge->dropForeignKey('journal_task_links', 'journal_task_links_subtask_id_foreign');
        $this->forge->dropColumn('journal_task_links', 'subtask_id');
    }
}
