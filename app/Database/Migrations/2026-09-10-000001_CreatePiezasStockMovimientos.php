<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Existencias (fase 60): inventario de piezas fisicas producidas, contado
 * por variante. Dos clases de fila en piezas_stock_movimientos:
 *
 *  - origen='manual' (alta_manual / baja_manual / ajuste): alta y baja a
 *    mano, append-only, cada una con su motivo. Nunca se editan.
 *  - origen='placa' (impresion): reflejo de una linea de bitacora
 *    (cantidad - fallidas). UNA fila por placa_version_id (de ahi el
 *    UNIQUE); el boton "dar de alta" de la placa la crea, la reajusta o la
 *    borra segun cambien las cantidades, sin apilar movimientos nuevos.
 *
 * placa_version_id va en CASCADE: si se quita esa pieza de la bitacora (o
 * se borra la placa entera) su aportacion al stock desaparece con ella, que
 * es lo coherente para un reflejo.
 */
class CreatePiezasStockMovimientos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'variante_id'      => ['type' => 'INT', 'unsigned' => true],
            'version_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'origen'           => ['type' => 'ENUM', 'constraint' => ['manual', 'placa']],
            'delta'            => ['type' => 'INT'],
            'motivo'           => ['type' => 'ENUM', 'constraint' => ['alta_manual', 'baja_manual', 'ajuste', 'impresion']],
            'nota'             => ['type' => 'TEXT', 'null' => true],
            'placa_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'placa_version_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'creado_en'        => ['type' => 'DATETIME', 'null' => true],
            'actualizado_en'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('variante_id');
        $this->forge->addUniqueKey('placa_version_id');
        $this->forge->addForeignKey('variante_id', 'piezas_variantes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('version_id', 'piezas_versiones', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('placa_id', 'piezas_placas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('placa_version_id', 'piezas_placas_versiones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_stock_movimientos');

        $this->forge->addColumn('piezas_placas', [
            'inventario_sincronizado_en' => ['type' => 'DATETIME', 'null' => true, 'after' => 'impresa_en'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_placas', 'inventario_sincronizado_en');
        $this->forge->dropTable('piezas_stock_movimientos', true);
    }
}
