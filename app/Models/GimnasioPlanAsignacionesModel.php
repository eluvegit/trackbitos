<?php
namespace App\Models;

use CodeIgniter\Model;

class GimnasioPlanAsignacionesModel extends Model
{
    protected $table      = 'gimnasio_plan_asignaciones';
    protected $primaryKey = 'id';
    protected $allowedFields = ['plan_id','bloque_id','entrenamiento_id','fecha_prevista','estado'];
    protected $useTimestamps = true;
}
