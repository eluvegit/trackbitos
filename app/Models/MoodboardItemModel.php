<?php

namespace App\Models;

use CodeIgniter\Model;

class MoodboardItemModel extends Model
{
    protected $table         = 'moodboard_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_at';
    protected $updatedField  = '';

    protected $allowedFields = [
        'sesion_id',
        'situacion_id',
        'origen',
        'ruta_archivo',
        'url_externa',
        'nota',
        'orden',
    ];

    protected $validationRules = [
        'sesion_id' => 'required|is_natural_no_zero',
        'origen'    => 'required|in_list[archivo,enlace]',
    ];
}
