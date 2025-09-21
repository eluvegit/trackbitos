<?php
namespace App\Models;

use CodeIgniter\Model;

class RodajesEscenaModel extends Model
{
    protected $table            = 'rodajes_escenas';
    protected $primaryKey       = 'id';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $returnType       = 'array';

    protected $allowedFields    = [
        'proyecto_id',
        // ESCENA
        'escena_bloque','escena_tomas','escena_ubicacion','escena_descripcion',
        'escena_objetivo','escena_accion','escena_efecto_especial',
        'escena_cont_previa','escena_cont_posterior',
        // CÁMARA
        'camara_modelo','camara_optica','camara_apertura','camara_fps',
        'camara_velocidad','camara_iso','camara_wb','camara_nd',
        'camara_tipo_plano','camara_angulo','camara_movimiento','camara_soporte',
        // CONSTRUCCIÓN DEL PLANO
        'plano_ref_lugar_texto','plano_ref_inspiracion_texto','plano_esquema_iluminacion',
        'plano_hora_dia','plano_objetos','plano_actores',
        'plano_toma_alternativa','plano_notas',
        // SONIDO
        'sonido_ambiente','sonido_antiviento','sonido_dialogo_escrito',
        // ORDEN
        'orden'
    ];

    protected $validationRules = [
        'proyecto_id' => 'required|is_natural_no_zero'
    ];
}
