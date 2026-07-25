<?php

namespace App\Models;

use CodeIgniter\Model;

class BraintogramMensajeModel extends Model
{
    protected $table         = 'braintogram_mensajes';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'update_id',
        'tipo',
        'chat_id',
        'chat_type',
        'from_id',
        'from_username',
        'from_nombre',
        'texto',
        'fecha_telegram',
        'ip_origen',
        'secret_valido',
        'chat_autorizado',
        'rate_limited',
        'raw_json',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}
