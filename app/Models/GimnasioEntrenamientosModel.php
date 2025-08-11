<?php

namespace App\Models;

use CodeIgniter\Model;

class GimnasioEntrenamientosModel extends Model
{
    protected $table            = 'gimnasio_entrenamientos';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['fecha', 'notas_generales', 'lesiones', 'sin_molestias'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
