<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Papelera para piezas (invariante 6), ahora también por variante suelta:
 * hasta ahora solo se podía borrar la familia entera. Una pieza con varias
 * líneas de diseño puede tener alguna abandonada (un tamaño que no se pidió
 * nunca más, un prototipo descartado) sin que el resto de la pieza tenga
 * nada que ver con eso — borrar la familia entera para quitar esa única
 * variante se llevaría por delante las demás.
 *
 * Mismo criterio que `piezas_familias.borrado_en`: mientras esté vacío es
 * una variante normal; en cuanto se pone, desaparece del índice, la galería
 * y el catálogo del cliente, y pasa a listarse en /piezas/papelera con
 * opción de restaurar. La purga definitiva llega a los 30 días vía
 * `piezas:purgar`.
 */
class AddBorradoEnToPiezasVariantes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_variantes', [
            'borrado_en' => ['type' => 'DATETIME', 'null' => true, 'after' => 'creado_en'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_variantes', 'borrado_en');
    }
}
