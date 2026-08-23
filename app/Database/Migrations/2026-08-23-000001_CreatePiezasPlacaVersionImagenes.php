<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Capturas de cómo quedó UNA pieza dentro de la placa (no la plataforma
 * entera, que ya tiene su tabla en piezas_placa_imagenes): la mejor
 * posición impresa, con notas de cómo estaba puesta y un veredicto rápido
 * (bien/regular/mal). Varias por fila, para poder guardar momentos
 * distintos — la orientación antes de imprimir, el resultado ya curado —
 * sin que una pise a la otra.
 */
class CreatePiezasPlacaVersionImagenes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'placa_version_id' => ['type' => 'INT', 'unsigned' => true],
            'ruta_imagen'      => ['type' => 'VARCHAR', 'constraint' => 500],
            'hash_imagen'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'tamano_bytes'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'notas'            => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            // Texto corto y no un id a catálogo: son tres valores fijos que
            // no van a crecer, igual que el veredicto de la placa entera.
            'resultado'        => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'orden'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'subida_en'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('placa_version_id');
        $this->forge->addForeignKey('placa_version_id', 'piezas_placas_versiones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_placa_version_imagenes');
    }

    public function down()
    {
        $this->forge->dropTable('piezas_placa_version_imagenes', true);
    }
}
