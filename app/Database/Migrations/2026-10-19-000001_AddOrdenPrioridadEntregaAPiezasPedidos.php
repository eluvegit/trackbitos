<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Para organizar el tablero de pedidos: orden manual dentro de su columna
 * (se arrastran las tarjetas), estrella de prioridad (los marcados van
 * siempre arriba) y fecha de entrega opcional. Los que ya existen quedan a
 * orden 0 y sin estrella, así que se siguen viendo como hasta ahora (más
 * recientes primero) hasta que se reordenen.
 */
class AddOrdenPrioridadEntregaAPiezasPedidos extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_pedidos', [
            'orden'         => ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0, 'after' => 'estado'],
            'prioritario'   => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0, 'after' => 'orden'],
            'fecha_entrega' => ['type' => 'DATE', 'null' => true, 'after' => 'prioritario'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_pedidos', ['orden', 'prioritario', 'fecha_entrega']);
    }
}
