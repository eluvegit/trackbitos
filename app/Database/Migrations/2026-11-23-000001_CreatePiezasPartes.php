<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Partes de una pieza (cabeza, torso, brazos…). La unidad sigue siendo la
 * pieza entera; cada parte lleva un saldo respecto a esas unidades
 * (−3 = faltan 3, +2 = sobran 2) para saber cuántas hay completas y qué
 * imprimir. Las filas con placa_version_id salen de la bitácora y solo
 * cuentan si la placa está dada de alta.
 */
class CreatePiezasPartes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'variante_id' => ['type' => 'INT', 'unsigned' => true],
            'nombre'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'por_unidad'  => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'orden'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'creado_en'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('variante_id');
        $this->forge->addForeignKey('variante_id', 'piezas_variantes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_partes');

        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'parte_id'         => ['type' => 'INT', 'unsigned' => true],
            'delta'            => ['type' => 'INT'],
            'motivo'           => ['type' => 'ENUM', 'constraint' => ['rota', 'suelta', 'ajuste', 'placa']],
            'nota'             => ['type' => 'TEXT', 'null' => true],
            'placa_version_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'creado_en'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('parte_id');
        $this->forge->addUniqueKey(['parte_id', 'placa_version_id']);
        $this->forge->addForeignKey('parte_id', 'piezas_partes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('placa_version_id', 'piezas_placas_versiones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_partes_movimientos');
    }

    public function down()
    {
        $this->forge->dropTable('piezas_partes_movimientos', true);
        $this->forge->dropTable('piezas_partes', true);
    }
}
