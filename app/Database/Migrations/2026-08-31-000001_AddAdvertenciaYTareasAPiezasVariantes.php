<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Dos apuntes por variante que hasta ahora no tenían sitio:
 *
 * - `advertencia`: una pieza puede estar validada (funciona y sirve) y aun
 *   así no ser perfecta. Un texto corto describe la pega; en el índice sale
 *   como un triángulo amarillo translúcido junto al número de versión.
 * - `tareas`: la lista de cosas pendientes de hacerle a la pieza, una por
 *   línea. Se escribe y se consulta desde un modal del índice. Distinto de
 *   `notas`, que es descripción libre, no un "por hacer".
 */
class AddAdvertenciaYTareasAPiezasVariantes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_variantes', [
            'advertencia' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'notas'],
            'tareas'      => ['type' => 'TEXT', 'null' => true, 'after' => 'advertencia'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_variantes', ['advertencia', 'tareas']);
    }
}
