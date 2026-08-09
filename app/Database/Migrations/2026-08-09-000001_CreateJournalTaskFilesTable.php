<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateJournalTaskFilesTable extends Migration
{
    public function up()
    {
        // Historial de materiales (archivos de cualquier tipo) adjuntos a una
        // tarea del Journal, para llevar referencias/documentación de cómo
        // hacerla (fotos, PDFs, enlaces descargados, etc.).
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'task_id'         => ['type' => 'INT', 'unsigned' => true],
            'ruta_archivo'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'nombre_original' => ['type' => 'VARCHAR', 'constraint' => 255],
            'tamano'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'creado_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('task_id', 'tasks', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('journal_task_files');
    }

    public function down()
    {
        $this->forge->dropTable('journal_task_files', true);
    }
}
