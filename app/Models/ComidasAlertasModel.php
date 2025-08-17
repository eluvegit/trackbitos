<?php namespace App\Models;

use CodeIgniter\Model;

class ComidasAlertasModel extends Model
{
    protected $table         = 'comidas_alertas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'dia_id','tipo','nutriente_id','mensaje','nivel','created_at'
    ];
    protected $useTimestamps = false;
}
