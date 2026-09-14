<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Hueco al que va automáticamente lo que salga de una placa de esta
 * variante (App\Services\PiezaInventario::sincronizarPlaca()), para no
 * tener que reubicar a mano cada impresión. NULL = todavía sin fijar; la
 * primera vez que una pieza nueva sale de una placa, se pide en la propia
 * bitácora y a partir de ahí queda recordado aquí.
 */
class AddHuecoPredeterminadoAPiezasVariantes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_variantes', [
            'hueco_predeterminado_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'stock_minimo'],
        ]);
        $this->forge->addKey('hueco_predeterminado_id');
        // SET NULL: si se borra el hueco, la pieza se queda sin destino por
        // defecto (vuelve a "sin asignar" hasta que se fije otro), no se
        // arrastra el borrado a la variante.
        $this->forge->addForeignKey('hueco_predeterminado_id', 'piezas_huecos', 'id', 'CASCADE', 'SET NULL');
        $this->forge->processIndexes('piezas_variantes');
    }

    public function down()
    {
        $this->forge->dropForeignKey('piezas_variantes', 'piezas_variantes_hueco_predeterminado_id_foreign');
        $this->forge->dropColumn('piezas_variantes', 'hueco_predeterminado_id');
    }
}
