<?php namespace App\Models;

use CodeIgniter\Model;

class ComidasDiasModel extends Model
{
    protected $table         = 'comidas_dias';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'user_id','fecha','entreno','entreno_tipo'
    ];

    protected $validationRules = [
        'user_id' => 'required|is_natural_no_zero',
        'fecha'   => 'required|valid_date[Y-m-d]',
    ];
}
