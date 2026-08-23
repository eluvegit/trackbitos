<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cuántas cuadrículas de la plataforma (rejilla de 6x10, margen lateral ya
 * descontado) ocupa este STL — a ojo, medido por quien lo sube, no leído del
 * fichero. Con esto PiezaEmpaquetadoService puede calcular cuántas placas
 * hacen falta para una tanda y qué va en cada una. Null es "sin medir
 * todavía", un valor válido: no bloquea nada, solo deja ese STL fuera del
 * cálculo hasta que se rellene.
 */
class AddCuadrosAPiezasVersionStls extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_version_stls', [
            'cuadros_ancho' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true, 'after' => 'tamano_bytes'],
            'cuadros_fondo' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true, 'after' => 'cuadros_ancho'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_version_stls', ['cuadros_ancho', 'cuadros_fondo']);
    }
}
