<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabla puente muchos-a-muchos entre piezas y vocabulario (evento, lugar,
 * persona, tema — nunca categoría, que vive como columna directa en
 * silo_piezas por ser de cardinalidad única, ver plan Silo §4).
 */
class CreateSiloPiezaAtributo extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'pieza_id'       => ['type' => 'INT', 'unsigned' => true],
            'vocabulario_id' => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['pieza_id', 'vocabulario_id']);
        $this->forge->addForeignKey('pieza_id', 'silo_piezas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('vocabulario_id', 'silo_vocabulario', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('silo_pieza_atributo');
    }

    public function down()
    {
        $this->forge->dropTable('silo_pieza_atributo', true);
    }
}
