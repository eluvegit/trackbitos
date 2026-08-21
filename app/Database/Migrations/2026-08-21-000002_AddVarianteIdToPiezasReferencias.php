<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Las referencias dejan de ser solo de la familia: ahora se suben ligadas a
 * la variante concreta que se estaba consultando, y solo se ven ahí — antes
 * cualquier foto subida desde una variante aparecía también en el resto de
 * variantes de la misma pieza, aunque no tuvieran nada que ver.
 *
 * Nullable a propósito: las referencias subidas antes de este cambio no
 * tienen forma de saber a qué variante pertenecían (si a alguna en
 * concreto). Se quedan con `variante_id` NULL y siguen viéndose desde
 * cualquier variante de la familia, como hasta ahora — solo las nuevas
 * quedan acotadas.
 */
class AddVarianteIdToPiezasReferencias extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_referencias', [
            'variante_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'familia_id'],
        ]);
        $this->forge->addForeignKey('variante_id', 'piezas_variantes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->processIndexes('piezas_referencias');
    }

    public function down()
    {
        $this->forge->dropForeignKey('piezas_referencias', 'piezas_referencias_variante_id_foreign');
        $this->forge->dropColumn('piezas_referencias', 'variante_id');
    }
}
