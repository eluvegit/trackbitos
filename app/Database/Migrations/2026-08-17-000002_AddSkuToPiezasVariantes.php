<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * SKU manual por variante: lo que alguien te dice cuando te pide una pieza
 * concreta, para poder buscarla sin tener que acordarte del nombre. Se
 * sincroniza a mano con el catálogo de fuera (Etsy, tienda...) — Trackbitos
 * no habla con esos sistemas, solo guarda el mismo código como referencia.
 */
class AddSkuToPiezasVariantes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_variantes', [
            'sku' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'after' => 'nombre'],
        ]);
        // NULL no cuenta para la unicidad en MySQL, así que las variantes
        // sin SKU (la mayoría, al principio) no chocan entre sí.
        $this->forge->addUniqueKey('sku', 'piezas_variantes_sku_unique');
        $this->forge->processIndexes('piezas_variantes');
    }

    public function down()
    {
        $this->forge->dropKey('piezas_variantes', 'piezas_variantes_sku_unique', true);
        $this->forge->dropColumn('piezas_variantes', 'sku');
    }
}
