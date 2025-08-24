<?php namespace App\Models;

use CodeIgniter\Model;

class ComidasFavoritosModel extends Model
{
    protected $table         = 'comidas_favoritos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'item_tipo','item_id','tipo_comida_preferido'
    ];
}
