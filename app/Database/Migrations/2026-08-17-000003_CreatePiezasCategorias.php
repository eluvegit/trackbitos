<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Categorías de piezas: el nivel que faltaba POR ENCIMA de la pieza
 * (spec 11.1). No sustituye a la variante — son ejes distintos: la
 * categoría dice de qué tipo es la pieza (cuerpo, accesorio, diorama),
 * la variante dice qué línea de diseño de esa pieza estás mirando.
 *
 * Tabla y no un simple campo de texto en piezas_familias: con texto libre
 * una errata crea una categoría nueva en silencio ("Accesorios" y
 * "accesorios" conviviendo), y renombrar una obliga a repasar todas las
 * piezas. Aquí renombrar es un UPDATE de una fila.
 *
 * `orden` porque el alfabético no es el orden en que se piensan las
 * carpetas: la idea es reproducir la organización que ya existe en disco
 * (spec 11.1), no imponer una nueva.
 */
class CreatePiezasCategorias extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nombre'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'orden'     => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'creado_en' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('nombre');
        $this->forge->createTable('piezas_categorias');

        // NULL = sin clasificar, y es un estado legítimo, no un error: una
        // pieza recién creada puede no saber todavía dónde va. El índice las
        // agrupa aparte al final en vez de esconderlas.
        $this->forge->addColumn('piezas_familias', [
            'categoria_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'nombre'],
        ]);
        $this->forge->addKey('categoria_id');
        // SET NULL y no CASCADE: borrar una categoría descoloca las piezas,
        // no las destruye. Perder un modelo 3D por reorganizar carpetas
        // sería un desastre desproporcionado a la acción.
        $this->forge->addForeignKey('categoria_id', 'piezas_categorias', 'id', 'CASCADE', 'SET NULL');
        $this->forge->processIndexes('piezas_familias');
    }

    public function down()
    {
        $this->forge->dropForeignKey('piezas_familias', 'piezas_familias_categoria_id_foreign');
        $this->forge->dropColumn('piezas_familias', 'categoria_id');
        $this->forge->dropTable('piezas_categorias');
    }
}
