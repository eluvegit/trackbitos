<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pautas de promoción (recordatorios de checklist antes de promocionar una
 * variante): una línea de texto por pauta, guardadas juntas en un solo
 * campo — igual que tarea_journal_id, esto vive en la fila única de
 * PiezaConfigModel en vez de en una tabla propia, porque no hay nada que
 * consultar aparte del texto completo.
 */
class AddPautasPromocionToPiezasConfig extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_config', [
            'pautas_promocion' => ['type' => 'TEXT', 'null' => true, 'after' => 'tarea_journal_id'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_config', 'pautas_promocion');
    }
}
