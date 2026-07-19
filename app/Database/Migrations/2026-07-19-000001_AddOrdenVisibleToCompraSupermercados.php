<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOrdenVisibleToCompraSupermercados extends Migration
{
    public function up()
    {
        $this->forge->addColumn('compra_supermercados', [
            'orden' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'null'       => false,
                'after'      => 'descripcion',
            ],
            'visible' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
                'after'      => 'orden',
            ],
        ]);

        // Mantiene el orden actual (por id) como punto de partida
        $this->db->query('UPDATE compra_supermercados SET orden = id');
    }

    public function down()
    {
        $this->forge->dropColumn('compra_supermercados', ['orden', 'visible']);
    }
}
