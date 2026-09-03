<?php

namespace App\Models;

use CodeIgniter\Model;

class SiloFicheroModel extends Model
{
    protected $table         = 'silo_ficheros';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = '';

    protected $allowedFields = ['pieza_id', 'nombre', 'tipo', 'tamano_bytes', 'hash'];

    public function deLaPieza(int $piezaId): array
    {
        return $this->where('pieza_id', $piezaId)->orderBy('nombre', 'ASC')->findAll();
    }

    public function sumaTamano(int $piezaId): int
    {
        $fila = $this->selectSum('tamano_bytes')->where('pieza_id', $piezaId)->first();

        return (int) ($fila['tamano_bytes'] ?? 0);
    }
}
