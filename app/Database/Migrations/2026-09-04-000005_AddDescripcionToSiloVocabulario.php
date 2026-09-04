<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * `descripcion`: texto libre opcional para dejar por escrito qué significa
 * cada entrada de vocabulario (sobre todo categorías, donde el criterio de
 * cuándo usar una u otra no es evidente por el nombre solo — plan Silo §4).
 * No lo usa ninguna lógica de clasificación automática, es documentación
 * para el humano al reclasificar.
 */
class AddDescripcionToSiloVocabulario extends Migration
{
    public function up()
    {
        $this->forge->addColumn('silo_vocabulario', [
            'descripcion' => ['type' => 'TEXT', 'null' => true, 'after' => 'nombre'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('silo_vocabulario', 'descripcion');
    }
}
