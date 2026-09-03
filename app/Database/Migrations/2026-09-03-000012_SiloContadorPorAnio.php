<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * El ID de negocio pasa de correlativo global de 6 dígitos a `AAnnnn`: dos
 * dígitos del año en que se generó el contenido + 4 correlativos que
 * reinician cada año (plan Silo §3, revisado). El contador deja de ser
 * fila única y pasa a una fila por año, incrementada bajo bloqueo de fila.
 * Las piezas antiguas conservan su ID de 6 dígitos: se parsean igual, el
 * primer token separado por espacio sigue siendo el ID.
 */
class SiloContadorPorAnio extends Migration
{
    public function up()
    {
        $this->forge->dropTable('silo_contador', true);

        $this->forge->addField([
            'anio'  => ['type' => 'SMALLINT', 'unsigned' => true],
            'valor' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('anio', true);
        $this->forge->createTable('silo_contador');
    }

    public function down()
    {
        $this->forge->dropTable('silo_contador', true);

        $this->forge->addField([
            'id'    => ['type' => 'INT', 'unsigned' => true],
            'valor' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('silo_contador');
        $this->db->table('silo_contador')->insert(['id' => 1, 'valor' => 0]);
    }
}
