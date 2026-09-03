<?php

namespace App\Models;

use CodeIgniter\Model;

class SiloPiezaAtributoModel extends Model
{
    protected $table         = 'silo_pieza_atributo';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['pieza_id', 'vocabulario_id'];

    /** Atributos de una pieza con el nombre/tipo de vocabulario ya resueltos. */
    public function deLaPieza(int $piezaId): array
    {
        return $this->select('silo_pieza_atributo.id, silo_pieza_atributo.vocabulario_id, silo_vocabulario.tipo, silo_vocabulario.nombre')
            ->join('silo_vocabulario', 'silo_vocabulario.id = silo_pieza_atributo.vocabulario_id')
            ->where('pieza_id', $piezaId)
            ->orderBy('silo_vocabulario.tipo', 'ASC')
            ->orderBy('silo_vocabulario.nombre', 'ASC')
            ->findAll();
    }

    public function reemplazarDeLaPieza(int $piezaId, array $vocabularioIds): void
    {
        $this->where('pieza_id', $piezaId)->delete();
        foreach (array_unique($vocabularioIds) as $vocabularioId) {
            $this->insert(['pieza_id' => $piezaId, 'vocabulario_id' => (int) $vocabularioId]);
        }
    }
}
