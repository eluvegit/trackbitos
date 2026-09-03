<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ficheros reales dentro de una carpeta de Silo — hasta ahora `silo_piezas`
 * solo trazaba la carpeta como bloque; esto añade el nivel de detalle que
 * dará la API al escanear (nombre de fichero, tipo, tamaño, hash), plan
 * Silo §5 "Integridad" (nombres+tamaños es el nivel 1 de comprobación).
 */
class CreateSiloFicheros extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'pieza_id'     => ['type' => 'INT', 'unsigned' => true],
            'nombre'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'tipo'         => ['type' => 'ENUM', 'constraint' => ['foto', 'video', 'otro']],
            'tamano_bytes' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'hash'         => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'creado_en'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('pieza_id');
        $this->forge->addForeignKey('pieza_id', 'silo_piezas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('silo_ficheros');
    }

    public function down()
    {
        $this->forge->dropTable('silo_ficheros', true);
    }
}
