<?php
namespace App\Models;

use CodeIgniter\Model;

class GimnasioPlanesModel extends Model
{
    protected $table      = 'gimnasio_planes';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nombre','ejercicio','e1rm_base','redondeo_kg'];
    protected $useTimestamps = true;
}
