<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Permite líneas de pedido sin variante ni sku: piezas futuras que aún no
 * existen en el catálogo, apuntadas solo como texto libre hasta que se
 * diseñen. sku pasa a admitir NULL porque deja de ser obligatorio cuando la
 * línea se crea así.
 */
class AddDescripcionLibreAPedidosLineas extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('piezas_pedidos_lineas', [
            'sku' => ['name' => 'sku', 'type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
        ]);
        $this->forge->addColumn('piezas_pedidos_lineas', [
            'descripcion_libre' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'sku'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_pedidos_lineas', 'descripcion_libre');
        $this->forge->modifyColumn('piezas_pedidos_lineas', [
            'sku' => ['name' => 'sku', 'type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
        ]);
    }
}
