<?php

namespace App\Models;

use CodeIgniter\Model;

class CompraSueltoModel extends Model
{
    protected $table = 'compra_sueltos';
    protected $primaryKey = 'id';
    protected $allowedFields = ['supermercado_id', 'nombre', 'comprado', 'created_at'];
    protected $useTimestamps = false;
}
