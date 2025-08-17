<?php
namespace App\Models;

use CodeIgniter\Model;

class GimnasioPlanBloquesModel extends Model
{
    protected $table      = 'gimnasio_plan_bloques';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'plan_id','lote_id','e1rm_snapshot', 'orden','bloque_tipo','top_pct_min','top_pct_max','top_reps_min','top_reps_max',
        'bo_pct_rel_top','bo_sets','bo_reps','notas', 'estado',
    ];
    protected $useTimestamps = true;

    public function byPlanOrdered(int $planId): array
    {
        return $this->where('plan_id',$planId)->orderBy('orden','ASC')->findAll();
    }
}
