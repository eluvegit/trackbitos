<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Distingue una placa nacida de "Repartir en otra placa" de una nacida de
 * "Cargar en la placa actual": las dos usan `origen_placa_id`, pero solo la
 * primera tiene sentido "deshacer" (juntar sus piezas de vuelta con la
 * origen y borrarla) — repetir una placa entera no es algo que deshacer.
 */
class AddEsRepartoAPiezasPlacas extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_placas', [
            'es_reparto' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'null' => false, 'default' => 0, 'after' => 'origen_placa_id'],
        ]);

        // Backfill: las que ya se llaman "... (parte)" y tienen origen son,
        // por la convención de nombre que usa placaRepartir() al crearlas,
        // reparto y no repetición.
        $this->db->query(
            "UPDATE piezas_placas SET es_reparto = 1 WHERE origen_placa_id IS NOT NULL AND nombre LIKE '%(parte)%'"
        );
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_placas', 'es_reparto');
    }
}
