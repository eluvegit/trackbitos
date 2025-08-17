<?php namespace App\Models;

use CodeIgniter\Model;

class ComidasUnidadesModel extends Model
{
    protected $table         = 'comidas_unidades';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'nombre','abreviatura','es_masa','es_volumen'
    ];
}
