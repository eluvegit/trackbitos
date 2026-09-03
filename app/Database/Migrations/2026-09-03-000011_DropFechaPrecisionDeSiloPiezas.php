<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * `fecha_precision` solo servía para reconstruir el nombre de carpeta con
 * la granularidad tecleada (AAAAMM / AAAA). Se asume fecha completa, así
 * que la columna sobra: el nombre de carpeta ya está congelado en
 * `nombre_carpeta` y la propagación por año usa `fecha` directamente.
 */
class DropFechaPrecisionDeSiloPiezas extends Migration
{
    public function up()
    {
        $this->forge->dropColumn('silo_piezas', 'fecha_precision');
    }

    public function down()
    {
        $this->forge->addColumn('silo_piezas', [
            'fecha_precision' => [
                'type'       => 'ENUM',
                'constraint' => ['dia', 'mes', 'anio', 'sin_fecha'],
                'default'    => 'sin_fecha',
                'after'      => 'fecha',
            ],
        ]);
    }
}
