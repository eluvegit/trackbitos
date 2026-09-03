<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Contador "de verdad" detrás del ID de negocio de Silo: una fila por año,
 * incrementada bajo bloqueo de fila para que dos altas simultáneas nunca
 * se lleven el mismo número — mismo patrón que
 * PiezaSkuContadorModel::siguiente().
 */
class SiloContadorModel extends Model
{
    protected $table         = 'silo_contador';
    protected $primaryKey    = 'anio';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['anio', 'valor'];

    public function siguiente(int $anio): int
    {
        $db = $this->db;
        $db->transStart();
        $this->db->query('SELECT valor FROM silo_contador WHERE anio = ? FOR UPDATE', [$anio]);

        $existe = $this->where('anio', $anio)->get()->getRowArray();
        if ($existe === null) {
            $this->db->table('silo_contador')->insert(['anio' => $anio, 'valor' => 1]);
            $valor = 1;
        } else {
            $this->db->table('silo_contador')
                ->set('valor', 'valor + 1', false)
                ->where('anio', $anio)
                ->update();
            $valor = (int) $this->where('anio', $anio)->get()->getRowArray()['valor'];
        }

        $db->transComplete();

        return $valor;
    }
}
