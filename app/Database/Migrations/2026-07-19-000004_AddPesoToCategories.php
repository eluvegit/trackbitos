<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPesoToCategories extends Migration
{
    public function up()
    {
        $this->forge->addColumn('categories', [
            // 0 = excluida del reparto de "¿Qué hago ahora?", 1-5 = cuánto peso tiene
            'peso' => [
                'type'       => 'INT',
                'constraint' => 2,
                'default'    => 3,
                'null'       => false,
                'after'      => 'group_order',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('categories', 'peso');
    }
}
