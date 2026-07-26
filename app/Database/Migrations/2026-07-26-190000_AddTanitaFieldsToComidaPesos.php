<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTanitaFieldsToComidaPesos extends Migration
{
    public function up()
    {
        $this->forge->addColumn('comida_pesos', [
            'imc' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'after'      => 'peso',
            ],
            'grasa_corporal_pct' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'after'      => 'imc',
            ],
            'grasa_visceral' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'after'      => 'grasa_corporal_pct',
            ],
            'masa_muscular_kg' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'after'      => 'grasa_visceral',
            ],
            'masa_osea_kg' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'after'      => 'masa_muscular_kg',
            ],
            'metabolismo_basal_kcal' => [
                'type'       => 'SMALLINT',
                'constraint' => 6,
                'null'       => true,
                'after'      => 'masa_osea_kg',
            ],
            'edad_metabolica' => [
                'type'       => 'SMALLINT',
                'constraint' => 3,
                'null'       => true,
                'after'      => 'metabolismo_basal_kcal',
            ],
            'agua_corporal_pct' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'after'      => 'edad_metabolica',
            ],
            'valoracion_fisica' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'null'       => true,
                'after'      => 'agua_corporal_pct',
            ],
            'frecuencia_cardiaca' => [
                'type'       => 'SMALLINT',
                'constraint' => 3,
                'null'       => true,
                'after'      => 'valoracion_fisica',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('comida_pesos', [
            'imc',
            'grasa_corporal_pct',
            'grasa_visceral',
            'masa_muscular_kg',
            'masa_osea_kg',
            'metabolismo_basal_kcal',
            'edad_metabolica',
            'agua_corporal_pct',
            'valoracion_fisica',
            'frecuencia_cardiaca',
        ]);
    }
}
