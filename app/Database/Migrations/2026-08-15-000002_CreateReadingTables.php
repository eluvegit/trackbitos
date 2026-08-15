<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReadingTables extends Migration
{
    public function up()
    {
        // ---- Libros ----
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'title'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'author'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'cover_url'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'isbn'           => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'total_pages'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'current_page'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'status'         => ['type' => 'ENUM', 'constraint' => ['quiero_leer', 'leyendo', 'terminado', 'abandonado', 'pausado'], 'default' => 'quiero_leer'],
            // Meta mínima por sesión, elegida por el propio usuario (puede ser 1).
            'min_goal_pages' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            // Rutina ya existente a la que engancha la lectura (ej: "gotas oftálmicas").
            'anchor_routine' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'rating'         => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'started_at'     => ['type' => 'DATE', 'null' => true],
            'finished_at'    => ['type' => 'DATE', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('books');

        // ---- Sesiones de lectura ----
        // Deliberadamente NO hay comparativa día-contra-día ni campo de
        // objetivo diario global: la Capa 2 (anti-ansiedad) depende de que
        // el esquema no invite a comparar days entre sí.
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'book_id'            => ['type' => 'INT', 'unsigned' => true],
            'session_date'       => ['type' => 'DATE'],
            'minutes'            => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'page_reached'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'note'               => ['type' => 'VARCHAR', 'constraint' => 280, 'null' => true],
            // Veces que pulsó "Perdí el hilo" durante el registro (opcional, sin juicio).
            'lost_thread_count'  => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            // Descarga rápida de un pensamiento intrusivo, sin resolverlo ahí.
            'parked_thought'     => ['type' => 'TEXT', 'null' => true],
            // true = registro de "hoy no toca": una decisión tomada, no un hueco/fallo.
            'skipped'            => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('book_id');
        $this->forge->addForeignKey('book_id', 'books', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('reading_sessions');

        // ---- Retos anuales (opcional, capa 2/3 de producto) ----
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'year'          => ['type' => 'YEAR'],
            'target_books'  => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('reading_goals');
    }

    public function down()
    {
        $this->forge->dropTable('reading_sessions', true);
        $this->forge->dropTable('reading_goals', true);
        $this->forge->dropTable('books', true);
    }
}
