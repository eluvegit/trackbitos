<?php
namespace App\Models;

use CodeIgniter\Model;

class RodajesEscenaImagenModel extends Model
{
    protected $table            = 'rodajes_escena_imagenes';
    protected $primaryKey       = 'id';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $returnType       = 'array';

    protected $allowedFields    = [
        'escena_id',
        'categoria',
        'ruta',
        'titulo'
    ];

    protected $validationRules = [
        'escena_id' => 'required|is_natural_no_zero',
        'categoria' => 'required|in_list[lugar_objetos,inspiracion]',
        'ruta'      => 'required|min_length[3]'
    ];
}
