<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Qué "cubos" (año de Nivel 2, o categoría de Nivel 3) vive en cada unidad
 * física. Antes era 1:1 vía `silo_unidades.agrupador`; a partir de la
 * planificación de Nivel 2 por capacidad de USB (petición 2026-09-05,
 * SiloPropagacionService::calcularPlanNivel2()) una unidad puede agrupar
 * varios años consecutivos para no desperdiciar espacio, así que hace falta
 * una relación 1:N. `agrupador` se mantiene sin tocar para Nivel 3
 * (categorías, sin cambios en esta petición) y como valor heredado/legado.
 */
class CreateSiloUnidadBuckets extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'unidad_id' => ['type' => 'INT', 'unsigned' => true],
            'bucket'    => ['type' => 'VARCHAR', 'constraint' => 20], // año ("2003") o "sin_fecha"
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['unidad_id', 'bucket']);
        $this->forge->addKey('bucket');
        $this->forge->addForeignKey('unidad_id', 'silo_unidades', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('silo_unidad_buckets');

        // Backfill: las unidades de Nivel 2/3 ya existentes con `agrupador`
        // mantienen su único bucket también en la tabla nueva.
        $this->db->query(
            "INSERT INTO silo_unidad_buckets (unidad_id, bucket)
             SELECT id, agrupador FROM silo_unidades
             WHERE nivel IN (2, 3) AND agrupador IS NOT NULL AND agrupador <> ''"
        );
    }

    public function down()
    {
        $this->forge->dropTable('silo_unidad_buckets', true);
    }
}
