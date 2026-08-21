<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fotos/capturas de la plataforma del laminador (Chitubox, Lychee...) para
 * cada placa: de dónde partía la impresión y cómo se orientó/soportó, no
 * solo el resultado ya curado. Una placa compleja puede necesitar varias
 * (vista general + detalle de un soporte concreto), así que van en tabla
 * propia, igual que los enlaces — no una sola columna.
 */
class CreatePiezasPlacaImagenes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'placa_id'     => ['type' => 'INT', 'unsigned' => true],
            'ruta_imagen'  => ['type' => 'VARCHAR', 'constraint' => 500],
            'hash_imagen'  => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'tamano_bytes' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'notas'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'orden'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'subida_en'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('placa_id');
        $this->forge->addForeignKey('placa_id', 'piezas_placas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_placa_imagenes');
    }

    public function down()
    {
        $this->forge->dropTable('piezas_placa_imagenes', true);
    }
}
