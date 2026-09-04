<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * `tipo_fisico`: qué dibujo pintar en la tarjeta de la unidad en
 * `/silo/unidades` (USB, disco interno, disco externo). Puramente
 * cosmético/identificativo — igual que `identificacion_fisica` — ninguna
 * lógica de negocio lo usa. Nulo = sin especificar, cae al icono genérico.
 */
class AddTipoFisicoASiloUnidades extends Migration
{
    public function up()
    {
        $this->forge->addColumn('silo_unidades', [
            'tipo_fisico' => [
                'type'       => 'ENUM',
                'constraint' => ['usb', 'hdd_interno', 'hdd_externo'],
                'null'       => true,
                'after'      => 'identificacion_fisica',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('silo_unidades', 'tipo_fisico');
    }
}
