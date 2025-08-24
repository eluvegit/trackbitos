<?php namespace App\Models;

use CodeIgniter\Model;

class ComidasRecetasModel extends Model
{
    protected $table         = 'comidas_recetas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nombre','descripcion',
    ];

    protected $validationRules = [
        'nombre'  => 'required|min_length[2]|max_length[180]',
    ];
}
