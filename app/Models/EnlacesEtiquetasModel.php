<?php
namespace App\Models;
use CodeIgniter\Model;

class EnlacesEtiquetasModel extends Model
{
    protected $table         = 'enlaces_etiquetas';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['nombre','slug'];
    protected $useTimestamps = true;
}
