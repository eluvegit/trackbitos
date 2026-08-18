<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * "Compuesta de": qué otras piezas (en una versión concreta) están
 * presentes en la escena de esta variante — un torso que se modeló dejando
 * el brazo ya hecho al lado para partir de él, un "Mini playmobil" que es
 * literalmente varias piezas de cuerpo juntas.
 *
 * Deliberadamente aparte de `origen_version_id` (piezas_variantes): ese
 * campo es el que usa el sistema de sincronización para la cadena de hashes
 * (spec 4.4, "tras promocionar") — tiene que ser uno solo, porque es de qué
 * fichero concreto se partió. Esto es una lista, puramente informativa: no
 * afecta a ningún invariante, no se recalcula, no se promociona ni se
 * fusiona sola. Solo dice "esto también estaba en la escena".
 */
class CreatePiezasComposiciones extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'variante_id'          => ['type' => 'INT', 'unsigned' => true],
            'version_componente_id' => ['type' => 'INT', 'unsigned' => true],
            'notas'                => ['type' => 'TEXT', 'null' => true],
            'creado_en'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('variante_id');
        // Una misma versión no se declara dos veces como componente de la
        // misma variante: no aporta nada repetido, y evita el "¿ya había
        // añadido esta?" al usarlo desde el móvil sin mirar la lista entera.
        $this->forge->addUniqueKey(['variante_id', 'version_componente_id']);
        $this->forge->addForeignKey('variante_id', 'piezas_variantes', 'id', 'CASCADE', 'CASCADE');
        // CASCADE y no RESTRICT: si la pieza referenciada acaba purgada de
        // la papelera (invariante 6), la fila que la nombraba deja de tener
        // sentido — ya no queda pieza de la que hablar.
        $this->forge->addForeignKey('version_componente_id', 'piezas_versiones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_composiciones');
    }

    public function down()
    {
        $this->forge->dropTable('piezas_composiciones', true);
    }
}
