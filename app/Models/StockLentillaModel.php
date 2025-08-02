<?php

namespace App\Models;

use CodeIgniter\Model;

class StockLentillaModel extends Model
{
    protected $table      = 'stock_lentillas';
    protected $primaryKey = 'id';
    protected $allowedFields = ['item', 'cantidad', 'updated_at'];
    protected $returnType = 'array';
    public    $useTimestamps = false;
}
