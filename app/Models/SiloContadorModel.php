<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Contador "de verdad" detrás del ID de negocio de Silo: fila única,
 * incrementada bajo bloqueo de fila para que dos altas simultáneas nunca
 * se lleven el mismo número — mismo patrón que
 * PiezaSkuContadorModel::siguiente().
 */
class SiloContadorModel extends Model
{
    protected $table         = 'silo_contador';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['valor'];

    public function siguiente(): int
    {
        $db = $this->db;
        $db->transStart();
        $this->db->query('SELECT valor FROM silo_contador WHERE id = 1 FOR UPDATE');
        $this->set('valor', 'valor + 1', false)->where('id', 1)->update();
        $fila = $this->where('id', 1)->get()->getRowArray();
        $db->transComplete();

        return (int) $fila['valor'];
    }
}
