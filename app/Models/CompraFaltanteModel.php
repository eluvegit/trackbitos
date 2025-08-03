<?php

namespace App\Models;

use CodeIgniter\Model;

class CompraFaltanteModel extends Model
{
    protected $table = 'compra_faltantes';
    protected $primaryKey = 'id';
    protected $allowedFields = ['producto_id'];
    protected $useTimestamps = false;
}
