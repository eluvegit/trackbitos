<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cuándo acaba cada placa enviada y cuántos minutos separan el final de una
 * impresora del de la otra, para que la segunda placa del día se calcule
 * para acabar ese tiempo después de la primera.
 */
class AddDesfaseImpresoras extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_placas', [
            'acaba_en' => ['type' => 'DATETIME', 'null' => true, 'after' => 'poner_en'],
        ]);
        $this->forge->addColumn('piezas_config', [
            'desfase_impresoras' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_placas', 'acaba_en');
        $this->forge->dropColumn('piezas_config', 'desfase_impresoras');
    }
}
