<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Unidad física de Silo (Maestro/USB/disco), identificada por nivel (1
 * Maestro, 2 Año, 3 Temática — mismo valor que `silo_ubicaciones.copia`) y
 * un número de orden dentro de ese nivel (1ª, 2ª, 3ª unidad sellada, plan
 * Silo §2). `fichero_control` guarda el JSON del `.silo_unit.json` que se
 * copiaría en la raíz de la unidad física (plan Silo §7.1) — aquí solo se
 * genera y se puede descargar, no se escribe a ningún disco todavía.
 */
class CreateSiloUnidades extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                     => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nivel'                  => ['type' => 'TINYINT', 'unsigned' => true],
            'numero'                 => ['type' => 'SMALLINT', 'unsigned' => true],
            'etiqueta'               => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'sellada'                => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sellada_en'             => ['type' => 'DATETIME', 'null' => true],
            'ultima_sincronizacion'  => ['type' => 'DATETIME', 'null' => true],
            'fichero_control'        => ['type' => 'TEXT'],
            'creado_en'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['nivel', 'numero']);
        $this->forge->createTable('silo_unidades');
    }

    public function down()
    {
        $this->forge->dropTable('silo_unidades', true);
    }
}
