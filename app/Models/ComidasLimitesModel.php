<?php namespace App\Models;

use CodeIgniter\Model;

class ComidasLimitesModel extends Model
{
    protected $table         = 'comidas_limites';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'nutriente_id','tipo','umbral','nivel','mensaje',
        'created_at','updated_at'
    ];
}
