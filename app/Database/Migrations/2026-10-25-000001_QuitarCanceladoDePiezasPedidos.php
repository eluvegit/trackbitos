<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fases nuevas del pedido: nuevo -> diseñando -> para impresión -> hecho.
 * El estado "cancelado" desaparece (no se usaba); los que hubiera pasan a
 * completado para que no se queden fuera del tablero. `estado` es VARCHAR,
 * así que "para_impresion" no necesita tocar la columna. El down no puede
 * saber cuáles eran cancelados, así que no deshace nada.
 */
class QuitarCanceladoDePiezasPedidos extends Migration
{
    public function up()
    {
        $this->db->table('piezas_pedidos')
            ->where('estado', 'cancelado')
            ->update(['estado' => 'completado']);
    }

    public function down()
    {
    }
}
