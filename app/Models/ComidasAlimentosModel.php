<?php namespace App\Models;

use CodeIgniter\Model;

class ComidasAlimentosModel extends Model
{
    protected $table            = 'comidas_alimentos';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'user_id','nombre','marca', 'es_receta','receta_id', 'descripcion','densidad_g_ml','es_liquido',
        'kcal','proteina_g','carbohidratos_g','azucares_g','fibra_g',
        'grasas_g','grasas_saturadas_g','sodio_mg','omega3_mg','omega6_mg',
        'nutrientes_extra'
    ];

    protected $validationRules = [
        'user_id' => 'required|is_natural_no_zero',
        'nombre'  => 'required|min_length[2]|max_length[180]',
    ];
}
