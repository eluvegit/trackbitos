<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RestoreIdeaEstadoEnSesiones extends Migration
{
    /**
     * Vuelve a hacer de 'idea' un estado más del pipeline de sesiones
     * (idea → planificacion → edicion → subiendo → completado), con la
     * misma funcionalidad y datos que cualquier otro estado (moodboard,
     * situaciones, equipo, model releases...); su única particularidad es
     * que no aparece por defecto en el listado. Esto revierte la migración
     * 2026-07-27-000003_CreateIdeasTables, que había retirado 'idea' del
     * ENUM para vivir en una tabla `ideas` separada — se demostró una mala
     * interpretación: una idea no es una entidad distinta, es una sesión
     * que aún no ha empezado a planificarse.
     */
    public function up()
    {
        $estadosConIdea = ['idea', 'planificacion', 'edicion', 'subiendo', 'completado'];

        $this->forge->modifyColumn('sesiones', [
            'estado_foto' => [
                'name'       => 'estado_foto',
                'type'       => 'ENUM',
                'constraint' => $estadosConIdea,
                'null'       => true,
            ],
            'estado_video' => [
                'name'       => 'estado_video',
                'type'       => 'ENUM',
                'constraint' => $estadosConIdea,
                'null'       => true,
            ],
        ]);

        $this->forge->modifyColumn('sesion_historial_estados', [
            'estado' => [
                'name'       => 'estado',
                'type'       => 'ENUM',
                'constraint' => $estadosConIdea,
                'null'       => true,
            ],
        ]);

        // Migra cualquier idea existente (tabla separada) a una sesión real
        // en estado 'idea', junto con su moodboard, antes de retirar las
        // tablas de ideas.
        $db    = $this->db;
        $ideas = $db->table('ideas')->get()->getResultArray();

        foreach ($ideas as $idea) {
            $db->table('sesiones')->insert([
                'titulo'          => $idea['titulo'],
                'notas'           => $idea['notas'],
                'estado_foto'     => (int) $idea['tiene_foto'] === 1 ? 'idea' : null,
                'estado_video'    => (int) $idea['tiene_video'] === 1 ? 'idea' : null,
                'pausada'         => 0,
                'entrega_modelos' => 'no_aplica',
                'creada_at'       => $idea['creada_at'],
                'actualizada_at'  => $idea['actualizada_at'],
            ]);
            $sesionId = $db->insertID();

            $moodboard = $db->table('idea_moodboard_items')->where('idea_id', $idea['id'])->get()->getResultArray();
            foreach ($moodboard as $item) {
                if ($item['origen'] === 'archivo' && !empty($item['ruta_archivo'])) {
                    $origenAbs  = rtrim(FCPATH, '/') . '/' . $item['ruta_archivo'];
                    $nuevaRuta  = 'uploads/sesiones/' . $sesionId . '/' . basename($item['ruta_archivo']);
                    $destinoAbs = rtrim(FCPATH, '/') . '/' . $nuevaRuta;
                    if (is_file($origenAbs)) {
                        @mkdir(dirname($destinoAbs), 0775, true);
                        @rename($origenAbs, $destinoAbs);
                        $item['ruta_archivo'] = $nuevaRuta;
                    }
                }

                $db->table('moodboard_items')->insert([
                    'sesion_id'    => $sesionId,
                    'situacion_id' => null,
                    'origen'       => $item['origen'],
                    'ruta_archivo' => $item['ruta_archivo'],
                    'url_externa'  => $item['url_externa'],
                    'nota'         => $item['nota'],
                    'orden'        => $item['orden'],
                    'creado_at'    => $item['creado_at'],
                ]);
            }
        }

        $this->forge->dropTable('idea_moodboard_items', true);
        $this->forge->dropTable('ideas', true);
    }

    public function down()
    {
        // Recrea las tablas de ideas (sin restaurar datos: la migración de
        // vuelta a sesiones no es reversible automáticamente).
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'auto_increment' => true],
            'titulo'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'notas'          => ['type' => 'TEXT', 'null' => true],
            'tiene_foto'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'tiene_video'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'creada_at'      => ['type' => 'DATETIME', 'null' => true],
            'actualizada_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('ideas');

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'auto_increment' => true],
            'idea_id'      => ['type' => 'INT'],
            'origen'       => ['type' => 'ENUM', 'constraint' => ['archivo', 'enlace']],
            'ruta_archivo' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'url_externa'  => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'nota'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'orden'        => ['type' => 'INT', 'default' => 0],
            'creado_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('idea_id', 'ideas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('idea_moodboard_items');

        $estadosSinIdea = ['planificacion', 'edicion', 'subiendo', 'completado'];

        $this->forge->modifyColumn('sesiones', [
            'estado_foto' => [
                'name'       => 'estado_foto',
                'type'       => 'ENUM',
                'constraint' => $estadosSinIdea,
                'null'       => true,
            ],
            'estado_video' => [
                'name'       => 'estado_video',
                'type'       => 'ENUM',
                'constraint' => $estadosSinIdea,
                'null'       => true,
            ],
        ]);

        $this->forge->modifyColumn('sesion_historial_estados', [
            'estado' => [
                'name'       => 'estado',
                'type'       => 'ENUM',
                'constraint' => $estadosSinIdea,
                'null'       => true,
            ],
        ]);
    }
}
