<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ReworkPausadaYEntregaModelos extends Migration
{
    public function up()
    {
        // 'aparcada' -> 'pausada': mismo booleano, nombre menos agresivo en la UI.
        $this->forge->modifyColumn('sesiones', [
            'aparcada' => [
                'name'       => 'pausada',
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
        ]);

        // 'entregado_modelos' (booleano) -> 'entrega_modelos' (estado de 3
        // valores: aún no aplica / pendiente de entregar / ya entregado).
        $this->forge->addColumn('sesiones', [
            'entrega_modelos' => [
                'type'       => 'ENUM',
                'constraint' => ['no_aplica', 'pendiente', 'entregado'],
                'default'    => 'no_aplica',
                'after'      => 'entregado_modelos',
            ],
        ]);

        $this->db->query("UPDATE sesiones SET entrega_modelos = IF(entregado_modelos = 1, 'entregado', 'pendiente')");

        $this->forge->dropColumn('sesiones', 'entregado_modelos');

        // Borrador del mensaje completo a enviar a la modelo (enlaces,
        // horario, ubicación...), para no tener que redactarlo cada vez.
        $this->forge->addColumn('sesiones', [
            'mensaje_modelos' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'entrega_modelos',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('sesiones', 'mensaje_modelos');

        $this->forge->addColumn('sesiones', [
            'entregado_modelos' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'pausada',
            ],
        ]);

        $this->db->query("UPDATE sesiones SET entregado_modelos = IF(entrega_modelos = 'entregado', 1, 0)");

        $this->forge->dropColumn('sesiones', 'entrega_modelos');

        $this->forge->modifyColumn('sesiones', [
            'pausada' => [
                'name'       => 'aparcada',
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
        ]);
    }
}
