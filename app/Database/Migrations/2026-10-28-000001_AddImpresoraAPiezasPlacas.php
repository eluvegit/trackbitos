<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * A qué impresora (OLD/NEW) se mandó la placa y para cuándo, para avisar si
 * ese día ya hay otra en la misma máquina y no duplicar placas.
 */
class AddImpresoraAPiezasPlacas extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_placas', [
            'impresora' => ['type' => 'VARCHAR', 'constraint' => 3, 'null' => true, 'after' => 'enviada_a'],
            'poner_en'  => ['type' => 'DATETIME', 'null' => true, 'after' => 'impresora'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_placas', ['impresora', 'poner_en']);
    }
}
