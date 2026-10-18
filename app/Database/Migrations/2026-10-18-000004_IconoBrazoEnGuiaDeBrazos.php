<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * La guía de brazos nació con el icono de una mano (bi-hand-index-thumb);
 * pasa al icono propio del brazo (svg-brazo, ver guias_helper). Solo si
 * sigue con la mano: si ya se le puso otro a mano, no se toca.
 */
class IconoBrazoEnGuiaDeBrazos extends Migration
{
    public function up()
    {
        $this->db->table('piezas_guias')
            ->where('nombre', 'Guía de brazos')
            ->where('icono', 'bi-hand-index-thumb')
            ->update(['icono' => 'svg-brazo']);
    }

    public function down()
    {
    }
}
