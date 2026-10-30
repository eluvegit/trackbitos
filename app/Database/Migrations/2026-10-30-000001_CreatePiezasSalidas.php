<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Albaranes de salida (una foto = un albarán) y cajas intermedias.
 *
 * caja en movimientos: piezas impresas que aún no se han guardado en su
 * hueco ('impresas') o apartadas para un encargo ('pedido'). Siguen siendo
 * stock; guardar es el par -n caja / +n hueco con motivo 'guardado', y pasar
 * de una caja a otra, el par con motivo 'cambio_caja'.
 *
 * Una salida en borrador no toca el stock; al confirmar crea un movimiento
 * 'salida' por línea con su salida_id. Reabrir borra esos movimientos.
 */
class CreatePiezasSalidas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'     => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nombre' => ['type' => 'VARCHAR', 'constraint' => 60],
            'orden'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('piezas_salida_destinos');
        $this->db->table('piezas_salida_destinos')->insertBatch([
            ['nombre' => 'Esther', 'orden' => 1],
            ['nombre' => 'Pintura', 'orden' => 2],
        ]);

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'fecha'         => ['type' => 'DATE'],
            'destino_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'estado'        => ['type' => 'ENUM', 'constraint' => ['borrador', 'confirmada'], 'default' => 'borrador'],
            'ruta_imagen'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'hash_imagen'   => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'tamano_bytes'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'nota'          => ['type' => 'TEXT', 'null' => true],
            'confirmada_en' => ['type' => 'DATETIME', 'null' => true],
            'creado_en'     => ['type' => 'DATETIME', 'null' => true],
            'actualizado_en' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('fecha');
        $this->forge->addForeignKey('destino_id', 'piezas_salida_destinos', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('piezas_salidas');

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'salida_id'   => ['type' => 'INT', 'unsigned' => true],
            'variante_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'descripcion' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'cantidad'    => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'origen'      => ['type' => 'ENUM', 'constraint' => ['impresas', 'pedido', 'hueco'], 'default' => 'hueco'],
            'x'           => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'y'           => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'orden'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('salida_id');
        $this->forge->addForeignKey('salida_id', 'piezas_salidas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('variante_id', 'piezas_variantes', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('piezas_salida_lineas');

        $this->forge->addColumn('piezas_stock_movimientos', [
            'caja'      => ['type' => 'ENUM', 'constraint' => ['impresas', 'pedido'], 'null' => true, 'after' => 'ubicacion_id'],
            'salida_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'placa_version_id'],
        ]);
        $this->forge->addKey('salida_id');
        $this->forge->addForeignKey('salida_id', 'piezas_salidas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->processIndexes('piezas_stock_movimientos');

        $this->db->query("ALTER TABLE piezas_stock_movimientos MODIFY motivo
            ENUM('alta_manual','baja_manual','ajuste','impresion','guardado','cambio_caja','salida') NOT NULL");
    }

    public function down()
    {
        $this->db->query("DELETE FROM piezas_stock_movimientos WHERE motivo IN ('guardado','cambio_caja','salida')");
        $this->db->query("ALTER TABLE piezas_stock_movimientos MODIFY motivo
            ENUM('alta_manual','baja_manual','ajuste','impresion') NOT NULL");
        $this->forge->dropForeignKey('piezas_stock_movimientos', 'piezas_stock_movimientos_salida_id_foreign');
        $this->forge->dropColumn('piezas_stock_movimientos', ['caja', 'salida_id']);
        $this->forge->dropTable('piezas_salida_lineas', true);
        $this->forge->dropTable('piezas_salidas', true);
        $this->forge->dropTable('piezas_salida_destinos', true);
    }
}
