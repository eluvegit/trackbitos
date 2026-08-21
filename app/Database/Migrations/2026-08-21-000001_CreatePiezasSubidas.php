<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Histórico de subidas dentro de una misma sesión: hasta ahora cada `subir`
 * pisaba el .blend anterior de la sesión (piezas_sesiones.ruta_blend es un
 * único fichero por sesión). Esta tabla guarda cada subida como fila y
 * fichero aparte, para poder recuperar cualquier punto intermedio, no solo
 * el último antes de cerrar.
 */
class CreatePiezasSubidas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'sesion_id'    => ['type' => 'INT', 'unsigned' => true],
            'numero'       => ['type' => 'SMALLINT', 'unsigned' => true],
            'ruta_blend'   => ['type' => 'VARCHAR', 'constraint' => 500],
            'hash_blend'   => ['type' => 'VARCHAR', 'constraint' => 64],
            'tamano_bytes' => ['type' => 'INT', 'unsigned' => true],
            'hash_padre'   => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'log'          => ['type' => 'TEXT', 'null' => true],
            'subida_en'    => ['type' => 'DATETIME'],
            'purgada'      => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('sesion_id');
        $this->forge->addUniqueKey(['sesion_id', 'numero']);
        $this->forge->addForeignKey('sesion_id', 'piezas_sesiones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_subidas');
    }

    public function down()
    {
        $this->forge->dropTable('piezas_subidas', true);
    }
}
