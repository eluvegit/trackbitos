<?php namespace App\Models;

use CodeIgniter\Model;

class BuscappTelegramaModel extends Model
{
    protected $table         = 'buscapp_telegramas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'uuid_cliente',
        'emisor_id',
        'grupo_id',
        'modo',
        'tipo',
        'mensaje',
        'urgencia',
        'caduca_en',
        'enviado_en',
    ];

    protected $useTimestamps = false;
}
