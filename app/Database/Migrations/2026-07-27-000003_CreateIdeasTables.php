<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIdeasTables extends Migration
{
    public function up()
    {
        // ---- Ideas: apuntes de futuras sesiones sin forma aún ----
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

        // ---- Moodboard de una idea (sin situaciones: es un apunte simple) ----
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

        // ---- Migra a la tabla ideas cualquier sesión que se quedara en
        // estado 'idea' mientras vivió dentro de sesiones, antes de retirar
        // ese estado del ENUM. Solo afecta a datos de prueba de este mismo
        // desarrollo, no a un esquema en producción con datos reales. ----
        $db    = $this->db;
        $filas = $db->table('sesiones')
            ->groupStart()
                ->where('estado_foto', 'idea')
                ->orWhere('estado_video', 'idea')
            ->groupEnd()
            ->get()->getResultArray();

        foreach ($filas as $fila) {
            $db->table('ideas')->insert([
                'titulo'         => $fila['titulo'],
                'notas'          => $fila['notas'],
                'tiene_foto'     => $fila['estado_foto'] !== null ? 1 : 0,
                'tiene_video'    => $fila['estado_video'] !== null ? 1 : 0,
                'creada_at'      => $fila['creada_at'],
                'actualizada_at' => $fila['actualizada_at'],
            ]);
            $ideaId = $db->insertID();

            $moodboard = $db->table('moodboard_items')->where('sesion_id', $fila['id'])->get()->getResultArray();
            foreach ($moodboard as $item) {
                $db->table('idea_moodboard_items')->insert([
                    'idea_id'      => $ideaId,
                    'origen'       => $item['origen'],
                    'ruta_archivo' => $item['ruta_archivo'],
                    'url_externa'  => $item['url_externa'],
                    'nota'         => $item['nota'],
                    'orden'        => $item['orden'],
                    'creado_at'    => $item['creado_at'],
                ]);
            }

            // Cascada: borra situaciones, moodboard_items, sesion_equipo,
            // model_releases, sesion_historial_estados y telegram_sesion_activa.
            $db->table('sesiones')->delete(['id' => $fila['id']]);
        }

        // Ahora que ninguna fila usa 'idea', se retira del ENUM: las ideas
        // viven solo en su propia tabla a partir de aquí.
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

    public function down()
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

        $this->forge->dropTable('idea_moodboard_items', true);
        $this->forge->dropTable('ideas', true);
    }
}
