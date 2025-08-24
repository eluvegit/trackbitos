<?php namespace App\Models;

use CodeIgniter\Model;

class ComidasDiasModel extends Model
{
    protected $table         = 'comidas_dias';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'fecha','entreno','entreno_tipo'
    ];

    protected $validationRules = [
        'fecha'   => 'required|valid_date[Y-m-d]',
    ];
}
