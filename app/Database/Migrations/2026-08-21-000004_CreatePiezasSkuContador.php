<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Contador global e inmutable para el SKU automático de cada variante
 * (fase 44). Fila única (id=1): se incrementa bajo bloqueo, nunca se
 * reutiliza un número aunque se borre la variante que lo llevaba — es lo
 * que garantiza que dos variantes nunca compartan SKU sin tener que
 * comprobar nada contra las que queden vivas.
 *
 * El SKU visible no es este número tal cual: sale de mezclarlo con una
 * constante fija (ver PiezaSkuService) para que no se lea como un
 * correlativo. Aquí solo vive el contador "de verdad".
 */
class CreatePiezasSkuContador extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'    => ['type' => 'INT', 'unsigned' => true],
            'valor' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('piezas_sku_contador');

        $this->db->table('piezas_sku_contador')->insert(['id' => 1, 'valor' => 0]);
    }

    public function down()
    {
        $this->forge->dropTable('piezas_sku_contador', true);
    }
}
