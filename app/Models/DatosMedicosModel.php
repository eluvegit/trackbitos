<?php

namespace App\Models;

use CodeIgniter\Model;

class DatosMedicosModel extends Model
{
    protected $table      = 'datos_medicos';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'graduacion_lent_izq',
        'graduacion_lent_der',
        'graduacion_gaf_izq',
        'graduacion_gaf_der',
        'radio_curvatura_izq',
        'radio_curvatura_der',
        'diametro_izq',
        'diametro_der',
        'notas'
    ];
    protected $useTimestamps = true;
}
