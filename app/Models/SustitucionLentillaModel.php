<?php

namespace App\Models;

use CodeIgniter\Model;

class SustitucionLentillaModel extends Model
{
    protected $table         = 'sustituciones';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['elemento', 'fecha', 'notas', 'created_at'];
    protected $useTimestamps = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $dateFormat       = 'datetime';
    protected $protectFields    = true;

    // Puedes dejar esto si en el futuro usas validaciones
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
}
