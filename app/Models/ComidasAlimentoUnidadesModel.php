<?php 
// app/Models/ComidasAlimentoUnidadesModel.php
namespace App\Models;

use CodeIgniter\Model;

class ComidasAlimentoUnidadesModel extends Model
{
    protected $table      = 'comidas_alimento_unidades';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes  = false;

    protected $allowedFields = [
  'user_id','alimento_id','unidad_id','descripcion',
  'gramos_equivalentes','es_predeterminada'
];


    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'user_id'            => 'required|integer',
        'alimento_id'        => 'required|integer',
        'descripcion'        => 'max_length[120]',
        'gramos_equivalentes'=> 'required|decimal',
    ];
}
