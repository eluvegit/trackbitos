<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * El contador "de verdad" detrás del SKU automático (fase 44): fila única,
 * incrementada bajo bloqueo de fila para que dos altas simultáneas nunca se
 * lleven el mismo número — mismo patrón que `PiezaSesionModel::abrir()` con
 * su `FOR UPDATE`, aplicado aquí a una sola fila en vez de una por rama.
 *
 * El número que devuelve no se reutiliza jamás, ni siquiera si la variante
 * que lo llevaba se borra: no depende de contar filas vivas, solo de sumar.
 */
class PiezaSkuContadorModel extends Model
{
    protected $table         = 'piezas_sku_contador';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['valor'];

    /** Lectura sin bloqueo ni incremento: solo para previsualizar. */
    public function actual(): int
    {
        return (int) ($this->where('id', 1)->first()['valor'] ?? 0);
    }

    public function siguiente(): int
    {
        $db = $this->db;
        $db->transStart();
        $this->db->query('SELECT valor FROM piezas_sku_contador WHERE id = 1 FOR UPDATE');
        $this->set('valor', 'valor + 1', false)->where('id', 1)->update();
        $fila = $this->where('id', 1)->get()->getRowArray();
        $db->transComplete();

        return (int) $fila['valor'];
    }
}
