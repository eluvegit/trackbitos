<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pedidos entrantes desde sterclicks: una lista de compra con sku + cantidad
 * por línea. sku se guarda también en la línea (además de variante_id) porque
 * es el identificador con el que llegó el pedido y debe sobrevivir aunque la
 * variante se borre más adelante (variante_id queda NULL en ese caso).
 */
class CreatePiezasPedidos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'origen'         => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'sterclicks'],
            'estado'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'nuevo'],
            'referencia_externa' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'notas'          => ['type' => 'TEXT', 'null' => true],
            'creado_en'      => ['type' => 'DATETIME'],
            'actualizado_en' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('piezas_pedidos');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'pedido_id'  => ['type' => 'INT', 'unsigned' => true],
            'variante_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'sku'        => ['type' => 'VARCHAR', 'constraint' => 50],
            'cantidad'   => ['type' => 'INT', 'unsigned' => true],
            'notas'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('pedido_id', 'piezas_pedidos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('variante_id', 'piezas_variantes', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('piezas_pedidos_lineas');
    }

    public function down()
    {
        $this->forge->dropTable('piezas_pedidos_lineas', true);
        $this->forge->dropTable('piezas_pedidos', true);
    }
}
