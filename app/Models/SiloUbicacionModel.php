<?php

namespace App\Models;

use CodeIgniter\Model;

class SiloUbicacionModel extends Model
{
    protected $table         = 'silo_ubicaciones';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'fecha_registro';
    protected $updatedField  = '';

    protected $allowedFields = ['pieza_id', 'unidad_id', 'copia', 'ruta_relativa'];

    protected $validationRules = [
        'pieza_id'      => 'required|is_natural_no_zero',
        'unidad_id'     => 'required|is_natural_no_zero',
        'copia'         => 'required|in_list[1,2,3]',
        'ruta_relativa' => 'required|max_length[500]',
    ];

    /** Ubicaciones de una pieza, con la unidad ya resuelta (nivel/número/etiqueta) para pintar directo. */
    public function deLaPieza(int $piezaId): array
    {
        return $this->select('silo_ubicaciones.*, silo_unidades.nivel, silo_unidades.numero, silo_unidades.etiqueta AS unidad_etiqueta')
            ->join('silo_unidades', 'silo_unidades.id = silo_ubicaciones.unidad_id')
            ->where('pieza_id', $piezaId)
            ->orderBy('copia', 'ASC')
            ->findAll();
    }

    /** Cuántas piezas distintas tiene una unidad — para decidir si borrarla necesita confirmación reforzada. */
    public function contarPorUnidad(int $unidadId): int
    {
        return $this->where('unidad_id', $unidadId)->countAllResults();
    }

    /** Bytes ya ocupados en una unidad — para que la propagación sepa si le cabe una pieza más. */
    public function sumaTamanoPorUnidad(int $unidadId): int
    {
        $fila = $this->select('SUM(silo_piezas.tamano_bytes) AS total')
            ->join('silo_piezas', 'silo_piezas.id = silo_ubicaciones.pieza_id')
            ->where('unidad_id', $unidadId)
            ->first();

        return (int) ($fila['total'] ?? 0);
    }
}
