<?php namespace App\Models;

use CodeIgniter\Model;

class ComidasObjetivosModel extends Model
{
    protected $table         = 'comidas_objetivos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'user_id','nombre','fecha_inicio','fecha_fin','creado_desde'
    ];
}
