<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Quita `sellada` / `sellada_en`: "sellar una unidad" no es una función del
 * sistema — que un disco no vuelva a escribirse es una decisión humana
 * sobre el backup, no un estado que Silo tenga que llevar.
 */
class DropSelladoDeSiloUnidades extends Migration
{
    public function up()
    {
        $this->forge->dropColumn('silo_unidades', ['sellada', 'sellada_en']);
    }

    public function down()
    {
        $this->forge->addColumn('silo_unidades', [
            'sellada'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'capacidad_bytes'],
            'sellada_en' => ['type' => 'DATETIME', 'null' => true, 'after' => 'sellada'],
        ]);
    }
}
