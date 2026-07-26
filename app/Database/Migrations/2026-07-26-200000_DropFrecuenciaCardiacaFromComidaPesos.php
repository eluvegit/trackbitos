<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropFrecuenciaCardiacaFromComidaPesos extends Migration
{
    public function up()
    {
        $this->forge->dropColumn('comida_pesos', 'frecuencia_cardiaca');
    }

    public function down()
    {
        $this->forge->addColumn('comida_pesos', [
            'frecuencia_cardiaca' => [
                'type'       => 'SMALLINT',
                'constraint' => 3,
                'null'       => true,
                'after'      => 'valoracion_fisica',
            ],
        ]);
    }
}
