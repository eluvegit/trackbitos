<?php namespace App\Models;

use CodeIgniter\Model;

class ComidasIngestasModel extends Model
{
    protected $table         = 'comidas_ingestas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'dia_id','tipo','item_tipo','item_id',
        'cantidad_gramos','porcion_id','porciones',
        'notas','hora'
    ];

    protected $validationRules = [
        'dia_id'   => 'required|is_natural_no_zero',
        'tipo'     => 'required|in_list[desayuno,almuerzo,merienda,cena,comida_nocturna]',
        'item_tipo'=> 'required|in_list[alimento,receta]',
        'item_id'  => 'required|is_natural_no_zero',
    ];
}
