<?php

namespace App\Models;

use CodeIgniter\Model;

class IdeaMoodboardItemModel extends Model
{
    protected $table         = 'idea_moodboard_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_at';
    protected $updatedField  = '';

    protected $allowedFields = [
        'idea_id',
        'origen',
        'ruta_archivo',
        'url_externa',
        'nota',
        'orden',
    ];

    protected $validationRules = [
        'idea_id' => 'required|is_natural_no_zero',
        'origen'  => 'required|in_list[archivo,enlace]',
    ];
}
