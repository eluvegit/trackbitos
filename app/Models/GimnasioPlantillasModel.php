<?php

namespace App\Models;

use CodeIgniter\Model;

class GimnasioPlantillasModel extends Model
{
    protected $table            = 'gimnasio_plantillas';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['nombre', 'notas'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
