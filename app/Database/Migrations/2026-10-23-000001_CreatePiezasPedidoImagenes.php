<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Imágenes de referencia de un pedido: lo que manda quien lo encarga (una
 * foto, una captura) antes de que exista ninguna pieza en el catálogo — no
 * hay variante a la que colgárselas todavía, así que van con el pedido.
 * Varias por pedido, en tabla propia igual que piezas_placa_imagenes.
 */
class CreatePiezasPedidoImagenes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'pedido_id'    => ['type' => 'INT', 'unsigned' => true],
            'ruta_imagen'  => ['type' => 'VARCHAR', 'constraint' => 500],
            'hash_imagen'  => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'tamano_bytes' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'notas'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'orden'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'subida_en'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('pedido_id');
        $this->forge->addForeignKey('pedido_id', 'piezas_pedidos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_pedido_imagenes');
    }

    public function down()
    {
        $this->forge->dropTable('piezas_pedido_imagenes', true);
    }
}
