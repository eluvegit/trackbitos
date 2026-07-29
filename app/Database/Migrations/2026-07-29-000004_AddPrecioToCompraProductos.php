<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrecioToCompraProductos extends Migration
{
    public function up()
    {
        $this->forge->addColumn('compra_productos', [
            'precio' => [
                'type'       => 'DECIMAL',
                'constraint' => '8,2',
                'null'       => true,
                'after'      => 'favorito',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('compra_productos', 'precio');
    }
}
