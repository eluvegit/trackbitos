<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOrdenToCompraProductos extends Migration
{
    public function up()
    {
        $this->forge->addColumn('compra_productos', [
            'orden' => [
                'type'       => 'INT',
                'default'    => 0,
                'after'      => 'zona_id',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('compra_productos', 'orden');
    }
}
