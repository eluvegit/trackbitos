<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFavoritoToCompraProductos extends Migration
{
    public function up()
    {
        $this->forge->addColumn('compra_productos', [
            'favorito' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'nombre',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('compra_productos', 'favorito');
    }
}
