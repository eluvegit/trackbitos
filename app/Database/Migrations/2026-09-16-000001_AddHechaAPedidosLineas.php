<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Casilla de "hecha" por línea de pedido: una nota de preparación propia del
 * usuario ("qué me falta por dejar listo antes de imprimir"), sin relación
 * con cantidad_completada (esa cuenta piezas ya impresas y válidas, después
 * de imprimir). Se puede marcar y desmarcar libremente.
 */
class AddHechaAPedidosLineas extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_pedidos_lineas', [
            'hecha' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'null' => false, 'default' => 0, 'after' => 'cantidad_completada'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_pedidos_lineas', 'hecha');
    }
}
