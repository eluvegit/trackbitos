<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Historial de placas (fase 36): cada vez que se descarga el zip de la
 * galería queda registrada sola, con fecha y qué piezas llevaba — así hay un
 * histórico sin tener que acordarse de guardar nada, y desde ahí se puede
 * volver a cargar la misma combinación en la placa actual para reimprimirla,
 * o borrar la entrada si solo era una prueba.
 *
 * `piezas_placas_versiones` cuelga de `version_id` con CASCADE a propósito:
 * si la variante entera se purga (invariante 6, 30 días), no tiene sentido
 * conservar la referencia a una versión que ya no existe.
 */
class CreatePiezasPlacas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nombre'     => ['type' => 'VARCHAR', 'constraint' => 150],
            'creado_en'  => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('piezas_placas');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'placa_id'   => ['type' => 'INT', 'unsigned' => true],
            'version_id' => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('placa_id');
        $this->forge->addForeignKey('placa_id', 'piezas_placas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('version_id', 'piezas_versiones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_placas_versiones');
    }

    public function down()
    {
        $this->forge->dropTable('piezas_placas_versiones');
        $this->forge->dropTable('piezas_placas');
    }
}
