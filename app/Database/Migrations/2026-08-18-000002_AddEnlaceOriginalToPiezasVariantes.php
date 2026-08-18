<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Dónde vive el máster de máxima calidad de una variante (p. ej. la malla
 * en bruto que sale de una generación por IA, sin decimar ni limpiar de
 * texturas): normalmente vive fuera del tracker (Drive o similar), porque
 * no necesita bloqueo entre máquinas ni versionado — solo hace falta poder
 * volver a él si algún día se quiere re-derivar otra calidad. Este campo es
 * solo el enlace; el fichero en sí nunca pasa por aquí.
 */
class AddEnlaceOriginalToPiezasVariantes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_variantes', [
            'enlace_original' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'notas'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_variantes', 'enlace_original');
    }
}
