<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Dónde vive físicamente cada pieza (Maestro, USB/disco por año, copia
 * temática). Alta manual en esta fase — sin escaneo automático ni fichero
 * oculto de unidad (plan Silo §9, fases posteriores). Sin UNIQUE: una
 * pieza puede repetirse en varias unidades/copias con rutas distintas
 * (las tres copias son completas, ver diseño §2).
 */
class CreateSiloUbicaciones extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'pieza_id'       => ['type' => 'INT', 'unsigned' => true],
            'unidad_fisica'  => ['type' => 'VARCHAR', 'constraint' => 100],
            'copia'          => ['type' => 'TINYINT', 'unsigned' => true],
            'ruta_relativa'  => ['type' => 'VARCHAR', 'constraint' => 500],
            'fecha_registro' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('pieza_id');
        $this->forge->addForeignKey('pieza_id', 'silo_piezas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('silo_ubicaciones');
    }

    public function down()
    {
        $this->forge->dropTable('silo_ubicaciones', true);
    }
}
