<?php

namespace App\Models;

use CodeIgniter\Model;

/** Línea de una placa de tirada: cuántas lleva de una pieza y cuántas fallaron. */
class PiezaTiradaLineaModel extends Model
{
    protected $table      = 'piezas_tiradas_lineas';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = ['placa_id', 'pieza_id', 'cantidad', 'fallidas'];

    /** Líneas de las placas de una tirada, agrupadas por placa_id. */
    public function porPlaca(int $tiradaId): array
    {
        $filas = $this->select('piezas_tiradas_lineas.*')
            ->join('piezas_tiradas_placas p', 'p.id = piezas_tiradas_lineas.placa_id')
            ->where('p.tirada_id', $tiradaId)->orderBy('piezas_tiradas_lineas.id')->findAll();
        $por = [];
        foreach ($filas as $f) {
            $por[(int) $f['placa_id']][] = $f;
        }

        return $por;
    }
}
