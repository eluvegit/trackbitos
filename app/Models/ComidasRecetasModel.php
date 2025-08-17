<?php namespace App\Models;

use CodeIgniter\Model;

class ComidasRecetasModel extends Model
{
    protected $table         = 'comidas_recetas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'user_id','nombre','descripcion',
    ];

    protected $validationRules = [
        'user_id' => 'required|is_natural_no_zero',
        'nombre'  => 'required|min_length[2]|max_length[180]',
    ];
}
