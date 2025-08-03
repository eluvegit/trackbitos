<?php

namespace App\Models;

use CodeIgniter\Model;

class CompraCompradoModel extends Model
{
    protected $table = 'compra_comprados';
    protected $primaryKey = 'id';
    protected $allowedFields = ['producto_id'];
    protected $useTimestamps = false;
}
