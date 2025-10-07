<?php
namespace App\Models;
use CodeIgniter\Model;

class EnlacesCategoriasModel extends Model
{
    protected $table         = 'enlaces_categorias';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['nombre','slug'];
    protected $useTimestamps = true;
}
