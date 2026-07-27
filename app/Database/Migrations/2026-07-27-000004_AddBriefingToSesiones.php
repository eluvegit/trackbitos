<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBriefingToSesiones extends Migration
{
    public function up()
    {
        // Desarrollo de la idea y descripción de la sesión (detalles a tener
        // en cuenta), distinto de 'notas' — pensado para volcarse en el
        // informe/briefing que se le pasa a la modelo.
        $this->forge->addColumn('sesiones', [
            'briefing' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'notas',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('sesiones', 'briefing');
    }
}
