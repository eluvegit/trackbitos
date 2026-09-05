<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Precio de la resina para el coste por pieza: un solo par de números
 * globales, no dato de ninguna pieza en concreto, así que va en la fila
 * única de PiezaConfigModel como la calculadora de tiempo.
 *
 * El coste sale del VOLUMEN con soportes de cada trozo por el precio por
 * litro. La densidad solo se usa para rellenar el hueco cuando de un trozo
 * se apuntó el peso pero no el volumen (o al revés). Ambos `null` mientras
 * no se configuren: sin precio no hay coste, solo se enseñan volumen y peso.
 */
class AddPrecioResinaAPiezasConfig extends Migration
{
    public function up()
    {
        $this->forge->addColumn('piezas_config', [
            'precio_resina_eur_litro' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'after' => 'calc_minutos_preparacion'],
            'densidad_resina_g_ml'    => ['type' => 'DECIMAL', 'constraint' => '5,3', 'null' => true, 'after' => 'precio_resina_eur_litro'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('piezas_config', ['precio_resina_eur_litro', 'densidad_resina_g_ml']);
    }
}
