<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * La nevera: una variante puede estar hecha y no ser basura (no va a la
 * papelera) pero sí estorbar en el listado principal mientras está
 * incompleta o no funciona bien. Mientras esté vacío es una variante
 * normal; en cuanto se pone, desaparece del índice, la galería y el
 * selector de "añadir componente", pero sigue contando en Existencias y
 * Estadísticas — no es un borrado, solo un aparcamiento sin caducidad
 * (a diferencia de `borrado_en`, nadie la purga).
 */
class AddCongeladoEnAPiezasVariantes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_variantes', [
            'congelado_en' => ['type' => 'DATETIME', 'null' => true, 'after' => 'borrado_en'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_variantes', 'congelado_en');
    }
}
