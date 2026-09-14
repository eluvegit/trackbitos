<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ubicaciones físicas donde se guardan las piezas ya impresas, a dos
 * niveles: estuche (la caja, p. ej. "E1") y hueco dentro de ese estuche
 * (el compartimento, p. ej. "H2"). Solo el hueco es una ubicación real de
 * guardado — un estuche por sí solo no aloja piezas, es un contenedor de
 * huecos. El código que se rotula físicamente es la unión de los dos:
 * "E1H2".
 *
 * No hay una cantidad propia por hueco: igual que el stock total de una
 * variante es la suma de sus movimientos (piezas_stock_movimientos), el
 * stock de una variante en un hueco es la suma de sus movimientos con ese
 * ubicacion_id (que aquí apunta a piezas_huecos, no al estuche). Así no
 * hay un segundo contador que se pueda desincronizar del historial — un
 * hueco compartido por varias piezas sale solo al agrupar por
 * ubicacion_id + variante_id.
 */
class CreatePiezasUbicaciones extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'codigo'    => ['type' => 'VARCHAR', 'constraint' => 20],
            'zona'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'notas'     => ['type' => 'TEXT', 'null' => true],
            'creado_en' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('codigo');
        $this->forge->createTable('piezas_estuches');

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'estuche_id'  => ['type' => 'INT', 'unsigned' => true],
            'codigo'      => ['type' => 'VARCHAR', 'constraint' => 20],
            'notas'       => ['type' => 'TEXT', 'null' => true],
            'creado_en'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('estuche_id');
        $this->forge->addUniqueKey(['estuche_id', 'codigo']);
        // CASCADE: un hueco no existe sin su estuche. Al borrarse en cascada
        // dispara a su vez el SET NULL de abajo sobre los movimientos que
        // lo referenciaban, así que el historial de stock no se pierde.
        $this->forge->addForeignKey('estuche_id', 'piezas_estuches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('piezas_huecos');

        // NULL = todavía sin asignar a ningún hueco, estado legítimo (igual
        // que categoria_id en piezas_familias). Apunta a piezas_huecos, no a
        // piezas_estuches: solo el hueco es una ubicación real de guardado.
        $this->forge->addColumn('piezas_stock_movimientos', [
            'ubicacion_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'version_id'],
        ]);
        $this->forge->addKey('ubicacion_id');
        $this->forge->addForeignKey('ubicacion_id', 'piezas_huecos', 'id', 'CASCADE', 'SET NULL');
        $this->forge->processIndexes('piezas_stock_movimientos');
    }

    public function down()
    {
        $this->forge->dropForeignKey('piezas_stock_movimientos', 'piezas_stock_movimientos_ubicacion_id_foreign');
        $this->forge->dropColumn('piezas_stock_movimientos', 'ubicacion_id');
        $this->forge->dropTable('piezas_huecos');
        $this->forge->dropTable('piezas_estuches');
    }
}
