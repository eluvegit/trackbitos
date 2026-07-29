<?php

namespace App\Models;

use CodeIgniter\Model;

class CompraProductoModel extends Model
{
    protected $table = 'compra_productos';
    protected $primaryKey = 'id';
    protected $allowedFields = ['supermercado_id', 'zona_id', 'nombre', 'imagen', 'favorito', 'orden'];
    protected $useTimestamps = true;
}
