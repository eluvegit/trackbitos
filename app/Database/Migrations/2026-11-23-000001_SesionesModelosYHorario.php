<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sesiones: perfiles de modelos con opciones de ropa + hora y ubicaciones.
 *
 * - sesion_modelos: perfil reutilizable de un modelo (no es de una sesión:
 *   el mismo perfil se añade a todas las sesiones en las que sale).
 * - sesion_modelo_ropa: opciones de ropa/looks del perfil (texto + foto opcional).
 * - sesion_modelos_sesion: qué modelos salen en cada sesión.
 * - sesion_ropa_elegida: qué opciones de ropa se usan en ESA sesión.
 * - sesiones.hora_sesion: hora de quedada/inicio.
 * - rodajes_ubicaciones.hora: hora prevista en cada ubicación del rodaje
 *   vinculado (las ubicaciones son las del rodaje, no se duplican).
 */
class SesionesModelosYHorario extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('hora_sesion', 'sesiones')) {
            $this->forge->addColumn('sesiones', [
                'hora_sesion' => ['type' => 'TIME', 'null' => true, 'after' => 'fecha_sesion'],
            ]);
        }
        if (!$this->db->fieldExists('hora', 'rodajes_ubicaciones')) {
            $this->forge->addColumn('rodajes_ubicaciones', [
                'hora' => ['type' => 'TIME', 'null' => true, 'after' => 'notas'],
            ]);
        }

        $this->forge->addField([
            'id'        => ['type' => 'INT', 'auto_increment' => true],
            'nombre'    => ['type' => 'VARCHAR', 'constraint' => 150],
            'contacto'  => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'notas'     => ['type' => 'TEXT', 'null' => true],
            'creado_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('sesion_modelos', true);

        $this->forge->addField([
            'id'        => ['type' => 'INT', 'auto_increment' => true],
            'modelo_id' => ['type' => 'INT'],
            'texto'     => ['type' => 'VARCHAR', 'constraint' => 150],
            'imagen'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'orden'     => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('modelo_id', 'sesion_modelos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sesion_modelo_ropa', true);

        $this->forge->addField([
            'id'        => ['type' => 'INT', 'auto_increment' => true],
            'sesion_id' => ['type' => 'INT'],
            'modelo_id' => ['type' => 'INT'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['sesion_id', 'modelo_id']);
        $this->forge->addForeignKey('sesion_id', 'sesiones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('modelo_id', 'sesion_modelos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sesion_modelos_sesion', true);

        $this->forge->addField([
            'sesion_id' => ['type' => 'INT'],
            'ropa_id'   => ['type' => 'INT'],
        ]);
        $this->forge->addKey(['sesion_id', 'ropa_id'], true);
        $this->forge->addForeignKey('sesion_id', 'sesiones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ropa_id', 'sesion_modelo_ropa', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sesion_ropa_elegida', true);
    }

    public function down()
    {
        $this->forge->dropTable('sesion_ropa_elegida', true);
        $this->forge->dropTable('sesion_modelos_sesion', true);
        $this->forge->dropTable('sesion_modelo_ropa', true);
        $this->forge->dropTable('sesion_modelos', true);
        if ($this->db->fieldExists('hora', 'rodajes_ubicaciones')) {
            $this->forge->dropColumn('rodajes_ubicaciones', 'hora');
        }
        if ($this->db->fieldExists('hora_sesion', 'sesiones')) {
            $this->forge->dropColumn('sesiones', 'hora_sesion');
        }
    }
}
