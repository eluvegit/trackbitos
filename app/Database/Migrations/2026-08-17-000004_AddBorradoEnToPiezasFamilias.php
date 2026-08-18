<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Papelera para piezas (invariante 6, aplicada ahora también a la familia
 * entera): borrar una pieza no la destruye, la marca con la fecha en que se
 * apartó. Mientras `borrado_en` esté vacío la pieza es una pieza normal; en
 * cuanto se pone, desaparece del índice y de la galería y pasa a listarse
 * solo en /piezas/papelera, con opción de restaurar. La purga definitiva
 * (fila + ficheros) llega a los 30 días vía `piezas:purgar`.
 */
class AddBorradoEnToPiezasFamilias extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_familias', [
            'borrado_en' => ['type' => 'DATETIME', 'null' => true, 'after' => 'creado_en'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_familias', 'borrado_en');
    }
}
