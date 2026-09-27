<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Detección de cambios N0–N3 (docs/silo-ingesta-propagacion.md § "Detección
 * de cambios", implementada 2026-09-27):
 *   · silo_ficheros.mtime — segundos Unix de la última modificación que vio
 *     el agente (`stat`, sin leer el fichero). Con tamaño y hash es lo que
 *     usa SiloIngestaService para saber qué cambió de verdad en una carpeta.
 *   · silo_unidades.hash_indice — rollup del manifiesto que el agente dejó
 *     en el disco en la última sincronización (N0: si no casa con el del
 *     `.silo_unit.json` del disco, el agente reenvía todo).
 *   · silo_unidades.ultima_verificacion — último `silo --verificar` (N3,
 *     re-hash completo).
 */
class AddManifiestoASilo extends Migration
{
    public function up()
    {
        $this->forge->addColumn('silo_ficheros', [
            'mtime' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'hash'],
        ]);
        $this->forge->addColumn('silo_unidades', [
            'hash_indice'         => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'ultima_sincronizacion'],
            'ultima_verificacion' => ['type' => 'DATETIME', 'null' => true, 'after' => 'hash_indice'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('silo_ficheros', 'mtime');
        $this->forge->dropColumn('silo_unidades', ['hash_indice', 'ultima_verificacion']);
    }
}
