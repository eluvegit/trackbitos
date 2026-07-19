<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHogarTables extends Migration
{
    public function up()
    {
        // ---- Habitaciones ----
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'auto_increment' => true],
            'nombre'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'icono'      => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'house'],
            'orden'      => ['type' => 'INT', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('hogar_habitaciones');

        // ---- Tareas (checklist rutinario por habitación) ----
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'auto_increment' => true],
            'habitacion_id'   => ['type' => 'INT'],
            'nombre'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'orden'           => ['type' => 'INT', 'default' => 0],
            // Frecuencia orientativa en días para avisar cuando toca repetirla (opcional)
            'frecuencia_dias' => ['type' => 'INT', 'null' => true],
            // 0 = pendiente, 1 = hecha (se queda marcada hasta que se "renueva")
            'estado'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'ultima_vez'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('habitacion_id', 'hogar_habitaciones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('hogar_tareas');

        // ---- Historial de cada vez que se marca una tarea como hecha ----
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'auto_increment' => true],
            'tarea_id'      => ['type' => 'INT'],
            'completada_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('tarea_id', 'hogar_tareas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('hogar_tareas_logs');

        $this->seedDefaults();
    }

    public function down()
    {
        $this->forge->dropTable('hogar_tareas_logs', true);
        $this->forge->dropTable('hogar_tareas', true);
        $this->forge->dropTable('hogar_habitaciones', true);
    }

    /**
     * Habitaciones y tareas de ejemplo para empezar a usar el módulo ya mismo.
     */
    private function seedDefaults(): void
    {
        $now = date('Y-m-d H:i:s');

        $habitaciones = [
            'Cocina'          => 'cup-hot',
            'Baño'            => 'droplet-half',
            'Salón'           => 'tv',
            'Dormitorio'      => 'moon-stars',
            'Entrada / Pasillo' => 'door-open',
        ];

        $tareasPorHabitacion = [
            'Cocina' => [
                ['Fregar platos', 1],
                ['Limpiar encimeras', 2],
                ['Barrer y fregar el suelo', 3],
                ['Limpiar fogones / vitro', 7],
                ['Sacar la basura', 2],
                ['Limpiar la nevera por dentro', 30],
            ],
            'Baño' => [
                ['Limpiar inodoro', 3],
                ['Limpiar lavabo y espejo', 3],
                ['Limpiar ducha / bañera', 7],
                ['Fregar el suelo', 7],
                ['Cambiar toallas', 7],
            ],
            'Salón' => [
                ['Aspirar / barrer', 3],
                ['Quitar el polvo', 7],
                ['Ordenar', 3],
                ['Limpiar cristales', 30],
            ],
            'Dormitorio' => [
                ['Cambiar sábanas', 14],
                ['Aspirar', 7],
                ['Ordenar cajones y armario', 30],
                ['Quitar el polvo', 7],
            ],
            'Entrada / Pasillo' => [
                ['Barrer', 3],
                ['Fregar', 7],
                ['Ordenar zapatero', 14],
            ],
        ];

        $ordenHabitacion = 1;
        foreach ($habitaciones as $nombre => $icono) {
            $this->db->table('hogar_habitaciones')->insert([
                'nombre'     => $nombre,
                'icono'      => $icono,
                'orden'      => $ordenHabitacion++,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $habitacionId = $this->db->insertID();

            $ordenTarea = 1;
            foreach ($tareasPorHabitacion[$nombre] as [$tareaNombre, $frecuencia]) {
                $this->db->table('hogar_tareas')->insert([
                    'habitacion_id'   => $habitacionId,
                    'nombre'          => $tareaNombre,
                    'orden'           => $ordenTarea++,
                    'frecuencia_dias' => $frecuencia,
                    'estado'          => 0,
                    'ultima_vez'      => null,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }
        }
    }
}
