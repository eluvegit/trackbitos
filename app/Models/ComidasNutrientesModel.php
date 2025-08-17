<?php namespace App\Models;

use CodeIgniter\Model;

class ComidasNutrientesModel extends Model
{
    protected $table         = 'comidas_nutrientes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nombre','clave','unidad','categoria','es_macro','orden','activo'
    ];

    protected $validationRules = [
        'nombre' => 'required',
        'clave'  => 'required|alpha_dash',
        'unidad' => 'required',
    ];
}
