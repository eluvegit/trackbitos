<?php
namespace App\Models;

use CodeIgniter\Model;

class AvisoLentillaModel extends Model
{
    protected $table            = 'avisos_lentillas';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['item', 'periodo_dias', 'created_at', 'updated_at'];
}
