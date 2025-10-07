<?php
namespace App\Models;
use CodeIgniter\Model;

class EnlacesItemBloquesModel extends Model
{
    protected $table         = 'enlaces_item_bloques';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['item_id','tipo','contenido','url','fichero','orden'];
    protected $useTimestamps = true;

    protected $validationRules = [
        'item_id' => 'required|is_natural_no_zero',
        'tipo'    => 'required|in_list[texto,imagen,embed,archivo]',
        'orden'   => 'required|is_natural_no_zero'
    ];
}
