<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDescripcionToJournalTaskFilesAndLinks extends Migration
{
    public function up()
    {
        $this->forge->addColumn('journal_task_files', [
            'descripcion' => ['type' => 'TEXT', 'null' => true, 'after' => 'nombre_original'],
        ]);
        $this->forge->addColumn('journal_task_links', [
            'descripcion' => ['type' => 'TEXT', 'null' => true, 'after' => 'titulo'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('journal_task_files', 'descripcion');
        $this->forge->dropColumn('journal_task_links', 'descripcion');
    }
}
