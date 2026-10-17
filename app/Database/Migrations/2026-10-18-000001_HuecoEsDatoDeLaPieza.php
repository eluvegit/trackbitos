<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * El hueco pasa a ser un dato de la pieza (piezas_variantes.hueco_predeterminado_id)
 * en vez de deducirse del stock. Rellena ese campo con el hueco donde ya está
 * su stock (el de más unidades si hubiera varios; si no, el que ya tuviera
 * fijado) y lleva ahí todas sus altas.
 */
class HuecoEsDatoDeLaPieza extends Migration
{
    public function up()
    {
        $porStock = [];
        $filas = $this->db->query(
            'SELECT variante_id, ubicacion_id, SUM(delta) AS total FROM piezas_stock_movimientos
             WHERE delta > 0 AND ubicacion_id IS NOT NULL GROUP BY variante_id, ubicacion_id ORDER BY total DESC'
        )->getResultArray();
        foreach ($filas as $f) {
            $porStock[(int) $f['variante_id']] ??= (int) $f['ubicacion_id'];
        }

        $variantes = $this->db->table('piezas_variantes')->select('id, hueco_predeterminado_id')->get()->getResultArray();
        foreach ($variantes as $v) {
            $vid     = (int) $v['id'];
            $huecoId = $porStock[$vid] ?? ($v['hueco_predeterminado_id'] !== null ? (int) $v['hueco_predeterminado_id'] : null);
            if ($huecoId === null) {
                continue;
            }

            $this->db->table('piezas_variantes')->where('id', $vid)->update(['hueco_predeterminado_id' => $huecoId]);
            $this->db->table('piezas_stock_movimientos')->where('variante_id', $vid)->where('delta >', 0)->update(['ubicacion_id' => $huecoId]);
        }
    }

    public function down()
    {
    }
}
