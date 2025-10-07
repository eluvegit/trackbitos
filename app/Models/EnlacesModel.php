<?php
namespace App\Models;
use CodeIgniter\Model;

class EnlacesModel extends Model
{
    protected $table         = 'enlaces_items';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['titulo','url','visto','relevancia','fecha','extra'];
    protected $useTimestamps = true;

    protected $validationRules = [
        'titulo'     => 'required|min_length[2]',
        'url'        => 'required',
        'relevancia' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[5]',
        'fecha'      => 'required|valid_date[Y-m-d]',
    ];
}
