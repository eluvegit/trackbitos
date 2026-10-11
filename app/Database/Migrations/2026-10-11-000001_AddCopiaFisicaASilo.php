<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Propagación física (Fase 3, docs/silo-ingesta-propagacion.md), 2026-10-11:
 *   · silo_ubicaciones.copiado_en — cuándo el agente dejó esa Copia 2/3
 *     completa en su USB (`silo --copiar`). NULL = solo planificada en BD,
 *     o el Maestro cambió los ficheros de la pieza después y hay que
 *     volver a pasar.
 *   · silo_unidades.espejo_de — una unidad de nivel 1 que es copia exacta
 *     (mismos nombres de carpeta) de otro Maestro. No se escanea ni se
 *     ingesta: solo recibe.
 *   · silo_unidades.hash_origen — `hash_indice` del Maestro la última vez
 *     que se espejó completo; si el Maestro ya tiene otro, el espejo va
 *     por detrás.
 */
class AddCopiaFisicaASilo extends Migration
{
    public function up()
    {
        $this->forge->addColumn('silo_ubicaciones', [
            'copiado_en' => ['type' => 'DATETIME', 'null' => true, 'after' => 'ruta_relativa'],
        ]);
        $this->forge->addColumn('silo_unidades', [
            'espejo_de'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'nivel'],
            'hash_origen' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'hash_indice'],
        ]);
        $this->db->query('ALTER TABLE silo_unidades ADD CONSTRAINT silo_unidades_espejo_de_foreign FOREIGN KEY (espejo_de) REFERENCES silo_unidades(id) ON DELETE SET NULL');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE silo_unidades DROP FOREIGN KEY silo_unidades_espejo_de_foreign');
        $this->forge->dropColumn('silo_unidades', ['espejo_de', 'hash_origen']);
        $this->forge->dropColumn('silo_ubicaciones', 'copiado_en');
    }
}
