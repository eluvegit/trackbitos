<?php namespace App\Models;

use CodeIgniter\Model;

class BuscappUsuarioModel extends Model
{
    protected $table         = 'buscapp_usuarios';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'telefono_e164',
        'nombre',
        'avatar_url',
        'fcm_token',
        'telegram_chat_id',
        'api_token',
        'creado_en',
        'ultimo_acceso',
    ];

    protected $useTimestamps = false;

    public function porToken(string $token): ?array
    {
        return $this->where('api_token', $token)->first();
    }
}
