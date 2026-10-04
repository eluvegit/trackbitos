<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Catálogo externo de ejercicios (dataset hasaneyldrm/exercises-dataset,
 * 1.324 ejercicios con GIF e instrucciones). Vive en su propia tabla para no
 * mezclarse con `gimnasio_ejercicios`: el selector del registro sigue viendo
 * solo los ejercicios propios. Un ejercicio propio puede apuntar a uno del
 * catálogo vía `catalogo_id` para heredar su GIF e instrucciones.
 */
class CreateGimnasioCatalogo extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'ext_id'           => ['type' => 'VARCHAR', 'constraint' => 10],
            'nombre'           => ['type' => 'VARCHAR', 'constraint' => 120],
            'parte'            => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'equipo'           => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'objetivo'         => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'secundarios'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'instrucciones_es' => ['type' => 'TEXT', 'null' => true],
            'pasos_es'         => ['type' => 'TEXT', 'null' => true],
            'instrucciones_en' => ['type' => 'TEXT', 'null' => true],
            'imagen'           => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'gif'              => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('ext_id');
        $this->forge->createTable('gimnasio_catalogo', true);

        $this->forge->addColumn('gimnasio_ejercicios', [
            'catalogo_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'grupo_muscular'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('gimnasio_ejercicios', 'catalogo_id');
        $this->forge->dropTable('gimnasio_catalogo', true);
    }
}
