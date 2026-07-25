<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGimnasioPlantillasTables extends Migration
{
    public function up()
    {
        // ---- Plantillas (rutinas preestablecidas) ----
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'nombre'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'notas'      => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('gimnasio_plantillas');

        // ---- Ejercicios dentro de una plantilla, en orden ----
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'plantilla_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'ejercicio_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'orden'        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'default' => 0],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('plantilla_id');
        $this->forge->addKey('ejercicio_id');
        $this->forge->createTable('gimnasio_plantilla_ejercicios');

        // ---- Series predefinidas de cada ejercicio de la plantilla ----
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'plantilla_ejercicio_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'series'                 => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'repeticiones'           => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'peso'                   => ['type' => 'FLOAT', 'default' => 0],
            'rpe'                    => ['type' => 'TINYINT', 'constraint' => 4, 'null' => true],
            'nota'                   => ['type' => 'TEXT', 'null' => true],
            'orden'                  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'default' => 0],
            'created_at'             => ['type' => 'DATETIME', 'null' => true],
            'updated_at'             => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('plantilla_ejercicio_id');
        $this->forge->createTable('gimnasio_plantilla_series');
    }

    public function down()
    {
        $this->forge->dropTable('gimnasio_plantilla_series', true);
        $this->forge->dropTable('gimnasio_plantilla_ejercicios', true);
        $this->forge->dropTable('gimnasio_plantillas', true);
    }
}
