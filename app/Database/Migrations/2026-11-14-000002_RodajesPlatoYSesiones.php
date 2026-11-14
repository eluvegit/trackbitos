<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rodajes ↔ Sesiones + modo plató.
 *
 * - rodajes_proyectos.sesion_id: vínculo opcional con una sesión de stock
 *   (módulo Sesiones). Los dos módulos siguen siendo independientes; el
 *   vínculo solo sirve para pasar información de uno a otro.
 * - rodajes_escenas.situacion_id: a qué situación de esa sesión corresponde
 *   la escena (evita duplicados al sincronizar en cualquier sentido).
 * - rodajes_escenas.toma_*: estado de la toma en el plató
 *   (NULL = pendiente | hecha | repetir | descartada) + nota rápida.
 */
class RodajesPlatoYSesiones extends Migration
{
    public function up()
    {
        $this->forge->addColumn('rodajes_proyectos', [
            'sesion_id' => ['type' => 'INT', 'null' => true, 'after' => 'descripcion'],
        ]);
        $this->db->query('ALTER TABLE rodajes_proyectos ADD INDEX idx_rodajes_proyectos_sesion (sesion_id)');

        $this->forge->addColumn('rodajes_escenas', [
            'situacion_id'   => ['type' => 'INT', 'null' => true, 'after' => 'proyecto_id'],
            'toma_estado'    => ['type' => 'VARCHAR', 'constraint' => 12, 'null' => true],
            'toma_estado_at' => ['type' => 'DATETIME', 'null' => true],
            'toma_nota'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->db->query('ALTER TABLE rodajes_escenas ADD INDEX idx_rodajes_escenas_situacion (situacion_id)');
    }

    public function down()
    {
        $this->forge->dropColumn('rodajes_escenas', ['situacion_id', 'toma_estado', 'toma_estado_at', 'toma_nota']);
        $this->forge->dropColumn('rodajes_proyectos', ['sesion_id']);
    }
}
