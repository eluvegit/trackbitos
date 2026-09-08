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

    /**
     * Piezas que ya viven en Nivel 1 (Copia 1) pero todavía no tienen sitio
     * en la copia indicada (2 = por año, 3 = por categoría) — lo que queda
     * "pendiente de almacenar" ahora que el reparto no inventa unidades.
     * Se recoloca al dar de alta más unidades y pulsar "Recalcular reparto".
     *
     * @return array{bytes: int, piezas: int}
     */
    public function pendienteDeCopia(int $copia): array
    {
        $sql = 'SELECT COALESCE(SUM(p.tamano_bytes), 0) AS bytes, COUNT(*) AS piezas
                FROM silo_piezas p
                WHERE EXISTS (SELECT 1 FROM silo_ubicaciones uc1 WHERE uc1.pieza_id = p.id AND uc1.copia = 1)
                  AND NOT EXISTS (SELECT 1 FROM silo_ubicaciones ucn WHERE ucn.pieza_id = p.id AND ucn.copia = ?)';

        $fila = $this->db->query($sql, [$copia])->getRowArray();

        return ['bytes' => (int) ($fila['bytes'] ?? 0), 'piezas' => (int) ($fila['piezas'] ?? 0)];
    }
}
