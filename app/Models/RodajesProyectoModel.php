<?php
namespace App\Models;

use CodeIgniter\Model;

class RodajesProyectoModel extends Model
{
    protected $table            = 'rodajes_proyectos';
    protected $primaryKey       = 'id';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $returnType       = 'array';

    protected $allowedFields    = [
        'titulo',
        'codigo',
        'descripcion'
    ];

    protected $validationRules  = [
        'titulo' => 'required|min_length[3]|max_length[150]'
    ];
}
