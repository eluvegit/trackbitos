<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Orden manual de las líneas dentro de su pedido (se arrastran en la ficha
 * del pedido). Las que ya existen quedan a 0 y se ordenan entre sí por id,
 * que es el orden en que se veían hasta ahora — no hace falta rellenarlas.
 */
class AddOrdenAPedidosLineas extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_pedidos_lineas', [
            'orden' => ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0, 'after' => 'pedido_id'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_pedidos_lineas', 'orden');
    }
}
