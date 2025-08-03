<?php

namespace App\Models;

use CodeIgniter\Model;

class CompraPrecioModel extends Model
{
    protected $table = 'compra_precios';
    protected $primaryKey = 'id';
    protected $allowedFields = ['producto_id', 'formato', 'precio', 'fecha'];
    protected $useTimestamps = true;
}
