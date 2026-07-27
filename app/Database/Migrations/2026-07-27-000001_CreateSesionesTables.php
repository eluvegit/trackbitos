<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSesionesTables extends Migration
{
    public function up()
    {
        // ---- Sesiones ----
        // Una sesión puede tener parte de fotografía, de vídeo, o ambas, cada
        // una con su propio ciclo de vida (estado_foto / estado_video). NULL
        // significa que esa parte no aplica a esta sesión.
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'auto_increment' => true],
            'titulo'             => ['type' => 'VARCHAR', 'constraint' => 150],
            'estado_foto'        => [
                'type'       => 'ENUM',
                'constraint' => ['planificacion', 'edicion', 'subiendo', 'completado'],
                'null'       => true,
            ],
            'estado_video'       => [
                'type'       => 'ENUM',
                'constraint' => ['planificacion', 'edicion', 'subiendo', 'completado'],
                'null'       => true,
            ],
            'aparcada'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'entregado_modelos'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'fecha_sesion'       => ['type' => 'DATE', 'null' => true],
            'notas'              => ['type' => 'TEXT', 'null' => true],
            'creada_at'          => ['type' => 'DATETIME', 'null' => true],
            'actualizada_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('sesiones');

        // ---- Historial de cambios de estado (por parte: foto o vídeo) ----
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'auto_increment' => true],
            'sesion_id'   => ['type' => 'INT'],
            'parte'       => ['type' => 'ENUM', 'constraint' => ['foto', 'video']],
            'estado'      => [
                'type'       => 'ENUM',
                'constraint' => ['planificacion', 'edicion', 'subiendo', 'completado'],
                'null'       => true,
            ],
            'cambiado_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('sesion_id', 'sesiones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sesion_historial_estados');

        // ---- Situaciones ----
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'auto_increment' => true],
            'sesion_id' => ['type' => 'INT'],
            'nombre'    => ['type' => 'VARCHAR', 'constraint' => 150],
            'orden'     => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('sesion_id', 'sesiones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('situaciones');

        // ---- Moodboard items ----
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'auto_increment' => true],
            'sesion_id'     => ['type' => 'INT'],
            'situacion_id'  => ['type' => 'INT', 'null' => true],
            'origen'        => ['type' => 'ENUM', 'constraint' => ['archivo', 'enlace']],
            'ruta_archivo'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'url_externa'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'nota'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'orden'         => ['type' => 'INT', 'default' => 0],
            'creado_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('sesion_id', 'sesiones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('situacion_id', 'situaciones', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('moodboard_items');

        // ---- Equipo a llevar ----
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'auto_increment' => true],
            'sesion_id' => ['type' => 'INT'],
            'item'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'marcado'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'orden'     => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('sesion_id', 'sesiones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sesion_equipo');

        // ---- Model releases ----
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'auto_increment' => true],
            'sesion_id'     => ['type' => 'INT'],
            'nombre_modelo' => ['type' => 'VARCHAR', 'constraint' => 150],
            'ruta_archivo'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'fecha'         => ['type' => 'DATE', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('sesion_id', 'sesiones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('model_releases');

        // ---- Sesión activa por chat de Telegram (captura de moodboard) ----
        $this->forge->addField([
            'chat_id'      => ['type' => 'BIGINT'],
            'sesion_id'    => ['type' => 'INT'],
            'situacion_id' => ['type' => 'INT', 'null' => true],
            'activada_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('chat_id', true);
        $this->forge->addForeignKey('sesion_id', 'sesiones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('telegram_sesion_activa');
    }

    public function down()
    {
        $this->forge->dropTable('telegram_sesion_activa', true);
        $this->forge->dropTable('model_releases', true);
        $this->forge->dropTable('sesion_equipo', true);
        $this->forge->dropTable('moodboard_items', true);
        $this->forge->dropTable('situaciones', true);
        $this->forge->dropTable('sesion_historial_estados', true);
        $this->forge->dropTable('sesiones', true);
    }
}
