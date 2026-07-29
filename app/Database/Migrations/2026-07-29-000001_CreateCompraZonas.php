<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompraZonas extends Migration
{
    public function up()
    {
        // ---- Zonas/pasillos de un supermercado, ordenables para definir el recorrido ----
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'auto_increment' => true],
            'supermercado_id' => ['type' => 'INT'],
            'nombre'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'orden'           => ['type' => 'INT', 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('supermercado_id', 'compra_supermercados', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('compra_zonas');

        // compra_productos ya existe fuera de las migraciones; se le añade la
        // columna de zona sin FK a nivel de BD (igual que supermercado_id),
        // el borrado de una zona limpia zona_id desde el controlador.
        $this->forge->addColumn('compra_productos', [
            'zona_id' => [
                'type'   => 'INT',
                'null'   => true,
                'after'  => 'supermercado_id',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('compra_productos', 'zona_id');
        $this->forge->dropTable('compra_zonas', true);
    }
}
