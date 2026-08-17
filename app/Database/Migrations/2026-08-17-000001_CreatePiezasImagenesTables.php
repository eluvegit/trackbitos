<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Imágenes del módulo Piezas, añadidas tras las 7 fases originales.
 *
 * Dos tablas, cada una colgando de donde tiene sentido en la jerarquía
 * (spec 1.1): las referencias (fotos del original con medidas de calibre)
 * son comunes a toda la familia, así que no se duplican por variante. Los
 * renders son el resultado visual de una versión concreta, así que cuelgan
 * de la versión — es lo que permite ver la evolución en el historial.
 *
 * A diferencia de los .blend (sección 8 del spec, guardados solo por el
 * script porque hace falta identidad de máquina), estas imágenes se suben
 * desde el propio navegador: no hay disco que cuadrar, solo algo que mirar.
 */
class CreatePiezasImagenesTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'familia_id'   => ['type' => 'INT', 'unsigned' => true],
            'ruta_imagen'  => ['type' => 'VARCHAR', 'constraint' => 500],
            'hash_imagen'  => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'tamano_bytes' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'notas'        => ['type' => 'TEXT', 'null' => true],
            'subida_en'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('familia_id');
        $this->forge->addForeignKey('familia_id', 'piezas_familias', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_referencias');

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'version_id'   => ['type' => 'INT', 'unsigned' => true],
            'ruta_imagen'  => ['type' => 'VARCHAR', 'constraint' => 500],
            'hash_imagen'  => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'tamano_bytes' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'notas'        => ['type' => 'TEXT', 'null' => true],
            'subida_en'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('version_id');
        $this->forge->addForeignKey('version_id', 'piezas_versiones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_renders');
    }

    public function down()
    {
        $this->forge->dropTable('piezas_renders', true);
        $this->forge->dropTable('piezas_referencias', true);
    }
}
