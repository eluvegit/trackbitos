<?php

namespace App\Models;

use CodeIgniter\Model;

class CompraProductoModel extends Model
{
    protected $table = 'compra_productos';
    protected $primaryKey = 'id';
    protected $allowedFields = ['supermercado_id', 'nombre', 'imagen'];
    protected $useTimestamps = true;
}
