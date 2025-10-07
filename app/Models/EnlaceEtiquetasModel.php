<?php
namespace App\Models;
use CodeIgniter\Model;

class EnlaceEtiquetasModel extends Model
{
    protected $table         = 'enlaces_item_etiquetas';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['item_id','etiqueta_id'];
    public    $useAutoIncrement = true;
}
