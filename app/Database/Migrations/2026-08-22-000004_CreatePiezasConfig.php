<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fila única de ajustes del módulo (id=1, sin autoincrement): hoy solo
 * guarda qué tarea de Journal está enlazada a "Pendientes de crear", pero es
 * el sitio donde ira cualquier ajuste global futuro que no pertenezca a
 * ninguna pieza, placa o pedido en concreto.
 */
class CreatePiezasConfig extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true],
            'tarea_journal_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'actualizado_en'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('piezas_config');
    }

    public function down()
    {
        $this->forge->dropTable('piezas_config');
    }
}
