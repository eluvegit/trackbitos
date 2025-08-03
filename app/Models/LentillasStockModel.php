<?php

namespace App\Models;

use CodeIgniter\Model;

class LentillasStockModel extends Model
{
    protected $table      = 'lentillas_stock';
    protected $primaryKey = 'id';
    protected $allowedFields = ['item', 'cantidad', 'updated_at'];
    protected $returnType = 'array';
    public    $useTimestamps = false;
}
