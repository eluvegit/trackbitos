<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIdeaEstadoToSesiones extends Migration
{
    private const ESTADOS_CON_IDEA = ['idea', 'planificacion', 'edicion', 'subiendo', 'completado'];
    private const ESTADOS_SIN_IDEA = ['planificacion', 'edicion', 'subiendo', 'completado'];

    public function up()
    {
        // 'idea' es un estado previo a 'planificacion': apuntes de sesiones
        // futuras que aún no tienen forma, ocultable del kanban del día a día.
        $this->forge->modifyColumn('sesiones', [
            'estado_foto' => [
                'name'       => 'estado_foto',
                'type'       => 'ENUM',
                'constraint' => self::ESTADOS_CON_IDEA,
                'null'       => true,
            ],
            'estado_video' => [
                'name'       => 'estado_video',
                'type'       => 'ENUM',
                'constraint' => self::ESTADOS_CON_IDEA,
                'null'       => true,
            ],
        ]);

        $this->forge->modifyColumn('sesion_historial_estados', [
            'estado' => [
                'name'       => 'estado',
                'type'       => 'ENUM',
                'constraint' => self::ESTADOS_CON_IDEA,
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('sesiones', [
            'estado_foto' => [
                'name'       => 'estado_foto',
                'type'       => 'ENUM',
                'constraint' => self::ESTADOS_SIN_IDEA,
                'null'       => true,
            ],
            'estado_video' => [
                'name'       => 'estado_video',
                'type'       => 'ENUM',
                'constraint' => self::ESTADOS_SIN_IDEA,
                'null'       => true,
            ],
        ]);

        $this->forge->modifyColumn('sesion_historial_estados', [
            'estado' => [
                'name'       => 'estado',
                'type'       => 'ENUM',
                'constraint' => self::ESTADOS_SIN_IDEA,
                'null'       => true,
            ],
        ]);
    }
}
