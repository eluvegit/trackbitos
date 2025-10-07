<?php
namespace App\Models;
use CodeIgniter\Model;

class EnlaceCategoriasModel extends Model
{
    protected $table         = 'enlaces_item_categorias';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['item_id','categoria_id'];
    public    $useAutoIncrement = true;
}
