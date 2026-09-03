<?php

namespace App\Models;

use CodeIgniter\Model;

class SiloProxyModel extends Model
{
    protected $table         = 'silo_proxies';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = '';

    protected $allowedFields = ['pieza_id', 'fichero_id', 'tipo', 'url', 'orden'];

    public function deLaPieza(int $piezaId): array
    {
        return $this->where('pieza_id', $piezaId)->orderBy('tipo', 'ASC')->orderBy('orden', 'ASC')->findAll();
    }
}
