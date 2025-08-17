<?php namespace App\Models;

use CodeIgniter\Model;

class ComidasAlimentoNutrienteModel extends Model
{
    protected $table         = 'comidas_alimento_nutriente';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'alimento_id','nutriente_id','valor_por_100g','fuente'
    ];
}
