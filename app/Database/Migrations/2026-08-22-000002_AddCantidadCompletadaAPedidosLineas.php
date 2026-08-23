<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Marcado manual de piezas completadas dentro de un pedido, independiente de
 * qué placa las imprimió: a veces una pieza sale mal y no cuenta aunque haya
 * salido de una placa, así que es una valoración a mano y no un cálculo que
 * cruce placas contra líneas de pedido.
 */
class AddCantidadCompletadaAPedidosLineas extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_pedidos_lineas', [
            'cantidad_completada' => ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0, 'after' => 'cantidad'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_pedidos_lineas', 'cantidad_completada');
    }
}
