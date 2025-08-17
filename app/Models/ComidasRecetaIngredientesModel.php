<?php namespace App\Models;

use CodeIgniter\Model;

class ComidasRecetaIngredientesModel extends Model
{
    protected $table         = 'comidas_receta_ingredientes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'receta_id','alimento_id','gramos','notas'
    ];
}
