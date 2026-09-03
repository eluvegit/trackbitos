<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Soporte de propagación automática a Copia 2/3 (plan Silo §2): `agrupador`
 * identifica a qué "cubo" pertenece una unidad de nivel 2 (el año, ej.
 * "2026" o "sin_fecha") o nivel 3 (la categoría, en slug) — así la
 * propagación sabe qué unidad ya existente reutilizar en vez de crear una
 * nueva cada vez. `capacidad_bytes` es opcional: sin límite conocido, todo
 * el cubo cabe en una sola unidad; con límite, la propagación reparte en
 * varias cuando no cabe (§2 "una unidad o varias si no caben").
 */
class AddPropagacionASiloUnidades extends Migration
{
    public function up()
    {
        $this->forge->addColumn('silo_unidades', [
            'agrupador'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'etiqueta'],
            'capacidad_bytes' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'agrupador'],
        ]);
        $this->forge->addKey(['nivel', 'agrupador']);
        $this->forge->processIndexes('silo_unidades');
    }

    public function down()
    {
        $this->forge->dropColumn('silo_unidades', ['agrupador', 'capacidad_bytes']);
    }
}
