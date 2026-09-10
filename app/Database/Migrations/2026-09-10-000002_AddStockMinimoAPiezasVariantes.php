<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Existencias (fase 60): minimo de piezas por variante para el aviso de
 * "quedan pocas". 0 = sin minimo (solo se avisa del cero). Por encima del
 * minimo, verde; entre 1 y el minimo, amarillo; a cero, rojo.
 */
class AddStockMinimoAPiezasVariantes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_variantes', [
            'stock_minimo' => ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0, 'after' => 'sku'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_variantes', 'stock_minimo');
    }
}
