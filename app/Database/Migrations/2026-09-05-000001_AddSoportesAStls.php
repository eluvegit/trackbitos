<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Volumen y peso CON SOPORTES de cada trozo, tal y como los da el laminador
 * (Chitubox / Lychee) al preparar la pieza para imprimir. Van junto a
 * `ancho_mm` / `fondo_mm` porque son la misma clase de dato: una medida que
 * se lee del laminador con la pieza ya orientada, no algo que se saque del
 * .stl. Con esto y el precio de la resina (piezas_config) sale el coste de
 * resina por pieza.
 *
 * `null` en cualquiera de los dos = "sin apuntar": ese trozo se queda fuera
 * del coste hasta que alguien lo mida, igual que con las medidas de placa.
 */
class AddSoportesAStls extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_version_stls', [
            'volumen_soportes_ml' => ['type' => 'DECIMAL', 'constraint' => '8,2', 'unsigned' => true, 'null' => true, 'after' => 'fondo_mm'],
            'peso_soportes_g'     => ['type' => 'DECIMAL', 'constraint' => '8,2', 'unsigned' => true, 'null' => true, 'after' => 'volumen_soportes_ml'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_version_stls', ['volumen_soportes_ml', 'peso_soportes_g']);
    }
}
