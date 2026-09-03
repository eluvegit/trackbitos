<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Contador global del ID de negocio de Silo (ancho fijo con ceros a la
 * izquierda, ver plan Silo §3). Fila única (id=1), incrementada bajo
 * bloqueo de fila — mismo patrón que `piezas_sku_contador`
 * (PiezaSkuContadorModel::siguiente()). El número nunca se reutiliza aunque
 * se borre la pieza que lo llevaba.
 */
class CreateSiloContador extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'    => ['type' => 'INT', 'unsigned' => true],
            'valor' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('silo_contador');

        $this->db->table('silo_contador')->insert(['id' => 1, 'valor' => 0]);
    }

    public function down()
    {
        $this->forge->dropTable('silo_contador', true);
    }
}
