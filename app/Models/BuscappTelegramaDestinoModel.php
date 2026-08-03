<?php namespace App\Models;

use CodeIgniter\Model;

class BuscappTelegramaDestinoModel extends Model
{
    protected $table         = 'buscapp_telegrama_destinos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'telegrama_id',
        'receptor_id',
        'canal',
        'estado',
        'respuesta',
        'entregado_en',
        'respondido_en',
    ];

    protected $useTimestamps = false;

    /**
     * Regla de escasez §3.1 bis: no se puede crear un nuevo telegrama de
     * $emisorId a $receptorId mientras tenga uno pendiente (enviado/entregado/
     * visto, sin responder ni caducar).
     */
    public function tienePendiente(int $emisorId, int $receptorId): bool
    {
        return $this->select('buscapp_telegrama_destinos.id')
            ->join('buscapp_telegramas', 'buscapp_telegramas.id = buscapp_telegrama_destinos.telegrama_id')
            ->where('buscapp_telegramas.emisor_id', $emisorId)
            ->where('buscapp_telegrama_destinos.receptor_id', $receptorId)
            ->whereIn('buscapp_telegrama_destinos.estado', ['enviado', 'entregado', 'visto'])
            ->first() !== null;
    }
}
