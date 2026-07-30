<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPeriodoDiasToRecordatorios extends Migration
{
    /**
     * Hasta ahora un recordatorio solo se podía repetir en bloques de meses
     * completos (mínimo 1 mes). Se añade periodo_dias para poder combinarlo
     * con periodo_meses y expresar cualquier plazo (p. ej. 1 mes y medio =
     * meses=1, dias=15; o 14 días = meses=null, dias=14).
     */
    public function up()
    {
        $this->forge->addColumn('recordatorios', [
            'periodo_dias' => [
                'type'       => 'INT',
                'null'       => true,
                'after'      => 'periodo_meses',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('recordatorios', 'periodo_dias');
    }
}
