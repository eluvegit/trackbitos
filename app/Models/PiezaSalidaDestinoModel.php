<?php

namespace App\Models;

use CodeIgniter\Model;

class PiezaSalidaDestinoModel extends Model
{
    protected $table         = 'piezas_salida_destinos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['nombre', 'orden'];

    /** @return array<int,string>  id => nombre */
    public function lista(): array
    {
        return array_column($this->orderBy('orden')->orderBy('nombre')->findAll(), 'nombre', 'id');
    }
}
