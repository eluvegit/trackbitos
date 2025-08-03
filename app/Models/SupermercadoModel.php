<?php

namespace App\Models;

use CodeIgniter\Model;

class SupermercadoModel extends Model
{
    protected $table = 'supermercados';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nombre'];
    protected $useTimestamps = true;
}
