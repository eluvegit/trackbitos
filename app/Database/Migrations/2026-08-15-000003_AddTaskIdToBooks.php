<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTaskIdToBooks extends Migration
{
    public function up()
    {
        // Enlace opcional con app.tasks (categoría "Lectura" en Journal):
        // Journal sigue siendo la puerta de entrada, este módulo completa
        // el resto de datos del mismo libro.
        $this->forge->addColumn('books', [
            'task_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'id',
            ],
        ]);

        $this->db->query('ALTER TABLE `books` ADD UNIQUE KEY `books_task_id_unique` (`task_id`)');
        $this->db->query('ALTER TABLE `books` ADD CONSTRAINT `books_task_id_fk` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `books` DROP FOREIGN KEY `books_task_id_fk`');
        $this->db->query('ALTER TABLE `books` DROP INDEX `books_task_id_unique`');
        $this->forge->dropColumn('books', 'task_id');
    }
}
