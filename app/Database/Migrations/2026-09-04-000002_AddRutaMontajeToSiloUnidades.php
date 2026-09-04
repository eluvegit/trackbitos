<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * `ruta_montaje`: dónde aparece el disco en ESTA máquina cuando se conecta
 * (ej. `D:\Maestro`, `/Volumes/Maestro`) — a diferencia de
 * `identificacion_fisica` (para que el humano reconozca el disco), esto lo
 * usa el agente `.py` en el handshake para saber qué unidad de la BD
 * corresponde a qué ruta montada. Cambia de máquina en máquina, así que es
 * solo una ayuda por defecto, no una verdad universal.
 */
class AddRutaMontajeToSiloUnidades extends Migration
{
    public function up()
    {
        $this->forge->addColumn('silo_unidades', [
            'ruta_montaje' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'identificacion_fisica'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('silo_unidades', 'ruta_montaje');
    }
}
