<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Calculadora de tiempo estimado del índice de Piezas: cuánto se tarda en
 * imprimir una placa a partir de su número de capas. No hay constante suelta
 * guardada — se guarda la referencia medida a mano (X capas tardaron Y
 * minutos) y de ahí sale el minuto/capa. Más los minutos fijos de
 * preparación que se suman siempre. Igual que las pautas, vive en la fila
 * única de PiezaConfigModel: no hay nada que consultar aparte de estos tres
 * números.
 */
class AddCalculadoraTiempoToPiezasConfig extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_config', [
            'calc_capas_referencia'    => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'pautas_promocion'],
            'calc_minutos_referencia'  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'after' => 'calc_capas_referencia'],
            'calc_minutos_preparacion' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'after' => 'calc_minutos_referencia'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_config', [
            'calc_capas_referencia',
            'calc_minutos_referencia',
            'calc_minutos_preparacion',
        ]);
    }
}
