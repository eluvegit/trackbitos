<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cuándo y a quién se mandó por correo la placa ("Enviar placa"), para saber
 * si ya está avisada sin mirar el buzón.
 */
class AddEnviadaAPiezasPlacas extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_placas', [
            'enviada_en' => ['type' => 'DATETIME', 'null' => true, 'after' => 'numero_capas'],
            'enviada_a'  => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true, 'after' => 'enviada_en'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_placas', ['enviada_en', 'enviada_a']);
    }
}
