<?php
namespace App\Models;

use CodeIgniter\Model;

class LentillasAvisosModel extends Model
{
    protected $table            = 'lentillas_avisos';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['item', 'periodo_dias', 'created_at', 'updated_at'];
}
