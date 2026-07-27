<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * A diferencia del resto de modelos del módulo, la PK es `chat_id` (BIGINT,
 * el propio identificador del chat de Telegram), no un `id` autoincremental
 * — así lo pide la spec, un chat solo puede tener una sesión activa a la vez.
 */
class TelegramSesionActivaModel extends Model
{
    protected $table            = 'telegram_sesion_activa';
    protected $primaryKey       = 'chat_id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'chat_id',
        'sesion_id',
        'situacion_id',
        'activada_at',
    ];

    protected $validationRules = [
        'chat_id'   => 'required',
        'sesion_id' => 'required|is_natural_no_zero',
    ];
}
