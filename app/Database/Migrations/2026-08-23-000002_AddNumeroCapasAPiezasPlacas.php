<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cuántas capas llevaba la impresión (fase 51): un dato que sí se puede leer
 * directamente del laminador/máquina, a diferencia del peso antes/después
 * del tanque, que logísticamente casi nunca se llega a pesar y por eso se
 * quita de la bitácora (spec: si hace falta, va como texto suelto en notas).
 */
class AddNumeroCapasAPiezasPlacas extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_placas', [
            'numero_capas' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'minutos_reales'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_placas', 'numero_capas');
    }
}
