<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fotos del perfil de un modelo (Sesiones → Modelos). La primera hace de
 * avatar en la tarjeta. Archivos en public/uploads/sesiones/modelos/{id}/perfil.
 */
class SesionModeloFotos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'auto_increment' => true],
            'modelo_id' => ['type' => 'INT'],
            'imagen'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'orden'     => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('modelo_id', 'sesion_modelos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('sesion_modelo_fotos', true);
    }

    public function down()
    {
        $this->forge->dropTable('sesion_modelo_fotos', true);
    }
}
