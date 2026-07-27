<?php

namespace App\Models;

use CodeIgniter\Model;

class IdeaModel extends Model
{
    protected $table         = 'ideas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creada_at';
    protected $updatedField  = 'actualizada_at';

    protected $allowedFields = [
        'titulo',
        'notas',
        'tiene_foto',
        'tiene_video',
    ];

    protected $validationRules = [
        'titulo' => 'required|max_length[150]',
    ];
}
