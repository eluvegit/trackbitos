<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cuánto ocupa este STL en la placa, ahora en milímetros y no en
 * cuadrículas (fase 53): Chitubox da la caja de ocupación exacta de la
 * pieza con su propia rejilla configurable, así que medir en mm es tan
 * fácil como medir en cuadrículas y no depende de a qué tamaño de rejilla
 * se decida tener la plataforma configurada en el laminador ese día.
 *
 * No hay conversión de datos: las medidas viejas en cuadrículas eran de una
 * rejilla arbitraria (6×10) que no corresponde a ningún mm real de la
 * plataforma, así que no hay forma fiable de traducirlas — quedan todas
 * como "sin medir" hasta que se vuelvan a medir en Chitubox.
 */
class ReplaceCuadrosPorMilimetrosEnStls extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_version_stls', [
            'ancho_mm' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'unsigned' => true, 'null' => true, 'after' => 'tamano_bytes'],
            'fondo_mm' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'unsigned' => true, 'null' => true, 'after' => 'ancho_mm'],
        ]);
        $this->forge->dropColumn('piezas_version_stls', ['cuadros_ancho', 'cuadros_fondo']);
    }

    public function down()
    {
        $this->forge->addColumn('piezas_version_stls', [
            'cuadros_ancho' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true, 'after' => 'tamano_bytes'],
            'cuadros_fondo' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true, 'after' => 'cuadros_ancho'],
        ]);
        $this->forge->dropColumn('piezas_version_stls', ['ancho_mm', 'fondo_mm']);
    }
}
