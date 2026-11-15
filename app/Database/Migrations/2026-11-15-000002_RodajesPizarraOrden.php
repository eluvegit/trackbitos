<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pizarra de Rodajes: cada vista (ubicación, montaje, producción) guarda su
 * propio orden de tarjetas para no pisar el orden narrativo, que sigue siendo
 * rodajes_escenas.orden. La posición es una secuencia única por vista y
 * proyecto; el orden de las columnas sale de la primera tarjeta de cada una.
 */
class RodajesPizarraOrden extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'escena_id' => ['type' => 'INT', 'unsigned' => true],
            'vista'     => ['type' => 'VARCHAR', 'constraint' => 20],
            'posicion'  => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['escena_id', 'vista']);
        $this->forge->createTable('rodajes_pizarra_orden', true);
    }

    public function down()
    {
        $this->forge->dropTable('rodajes_pizarra_orden', true);
    }
}
