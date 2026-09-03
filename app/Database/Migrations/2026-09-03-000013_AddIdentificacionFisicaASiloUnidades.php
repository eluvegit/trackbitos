<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * `identificacion_fisica`: texto libre para reconocer QUÉ disco/USB real es
 * cada unidad definida aquí cuando lo tienes delante — nº de serie,
 * etiqueta del volumen, marca/modelo, color, dónde está guardado... No lo
 * usa ninguna lógica, es solo para que "Mi PC" sea identificable de un
 * vistazo.
 */
class AddIdentificacionFisicaASiloUnidades extends Migration
{
    public function up()
    {
        $this->forge->addColumn('silo_unidades', [
            'identificacion_fisica' => ['type' => 'TEXT', 'null' => true, 'after' => 'etiqueta'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('silo_unidades', 'identificacion_fisica');
    }
}
