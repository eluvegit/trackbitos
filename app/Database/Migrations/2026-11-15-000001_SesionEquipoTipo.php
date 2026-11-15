<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * "Qué llevar" de una sesión en dos listas: equipo (cámara, luces…) y
 * props (atrezzo, vestuario, objetos). Lo existente queda como 'equipo'.
 */
class SesionEquipoTipo extends Migration
{
    public function up()
    {
        $this->forge->addColumn('sesion_equipo', [
            'tipo' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false, 'default' => 'equipo', 'after' => 'item'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('sesion_equipo', 'tipo');
    }
}
