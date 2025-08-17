<?php namespace App\Models;

use CodeIgniter\Model;

class ComidasObjetivoNutrienteModel extends Model
{
    protected $table         = 'comidas_objetivo_nutriente';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'objetivo_id','nutriente_id','meta','minimo','maximo'
    ];
}
