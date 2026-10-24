<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Una placa puede salir de varios pedidos: "Cargar a la placa" desde un
 * pedido ya no sustituye lo que hubiera, se suma, así que en una misma
 * tanda pueden juntarse piezas de dos o tres pedidos. La columna
 * piezas_placas.pedido_id solo admitía uno; pasa a tabla propia. Los enlaces
 * que ya había se copian antes de quitar la columna.
 */
class CreatePiezasPlacaPedidos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'placa_id'  => ['type' => 'INT', 'unsigned' => true],
            'pedido_id' => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['placa_id', 'pedido_id']);
        $this->forge->addKey('pedido_id');
        $this->forge->addForeignKey('placa_id', 'piezas_placas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('pedido_id', 'piezas_pedidos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_placa_pedidos');

        $this->db->query('INSERT INTO piezas_placa_pedidos (placa_id, pedido_id)
            SELECT id, pedido_id FROM piezas_placas WHERE pedido_id IS NOT NULL');

        $this->forge->dropForeignKey('piezas_placas', 'piezas_placas_pedido_id_foreign');
        $this->forge->dropColumn('piezas_placas', 'pedido_id');
    }

    public function down()
    {
        $this->forge->addColumn('piezas_placas', [
            'pedido_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'origen_placa_id'],
        ]);
        $this->forge->addForeignKey('pedido_id', 'piezas_pedidos', 'id', 'CASCADE', 'SET NULL');
        $this->forge->processIndexes('piezas_placas');

        // Vuelta atrás con pérdida: de cada placa solo cabe un pedido, el más bajo.
        $this->db->query('UPDATE piezas_placas p SET pedido_id =
            (SELECT MIN(pp.pedido_id) FROM piezas_placa_pedidos pp WHERE pp.placa_id = p.id)');

        $this->forge->dropTable('piezas_placa_pedidos', true);
    }
}
