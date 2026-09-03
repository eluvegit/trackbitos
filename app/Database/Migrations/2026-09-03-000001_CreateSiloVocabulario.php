<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Vocabulario abierto de Silo: categoría, evento, lugar, persona y tema
 * comparten una única tabla porque todos surgen igual (texto libre sobre la
 * marcha, sin catálogo cerrado previo) — lo que cambia entre ellos es
 * cardinalidad y función, no cómo se crean (ver plan Silo §4).
 *
 * `UNIQUE(tipo, slug)` y no solo `UNIQUE(slug)`: el mismo texto puede
 * legítimamente existir como Evento y como Tema a la vez sin confundirse
 * (p. ej. "sesion danza" como evento de una carpeta y "danza" como tema
 * transversal de varias) — el diseño solo pide cerrado a duplicados de
 * grafía DENTRO del mismo tipo.
 */
class CreateSiloVocabulario extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tipo'      => ['type' => 'ENUM', 'constraint' => ['categoria', 'evento', 'lugar', 'persona', 'tema']],
            'nombre'    => ['type' => 'VARCHAR', 'constraint' => 150],
            'slug'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'creado_en' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tipo', 'slug']);
        $this->forge->createTable('silo_vocabulario');
    }

    public function down()
    {
        $this->forge->dropTable('silo_vocabulario', true);
    }
}
