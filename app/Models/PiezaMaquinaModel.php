<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Máquinas que hablan con la API de Piezas. El UUID lo genera y guarda el
 * cliente (script), nunca el navegador: es la única identidad fiable.
 */
class PiezaMaquinaModel extends Model
{
    protected $table         = 'piezas_maquinas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['uuid', 'nombre', 'hostname', 'so', 'primera_vez', 'ultima_vez'];

    protected $validationRules = [
        'uuid'   => 'required|max_length[36]',
        'nombre' => 'required|max_length[100]',
    ];

    /**
     * Alta automática la primera vez que se ve un UUID; "ping" (actualiza
     * ultima_vez) si ya existía. hostname/so solo proponen un nombre por
     * defecto, no se usan para identificar la máquina.
     */
    public function registrar(string $uuid, ?string $hostname, ?string $so): array
    {
        $ahora     = date('Y-m-d H:i:s');
        $existente = $this->where('uuid', $uuid)->first();

        if ($existente) {
            $this->update($existente['id'], ['ultima_vez' => $ahora, 'so' => $so ?: $existente['so']]);

            return $this->find($existente['id']);
        }

        $id = $this->insert([
            'uuid'        => $uuid,
            'nombre'      => $hostname ?: 'Máquina nueva',
            'hostname'    => $hostname,
            'so'          => $so,
            'primera_vez' => $ahora,
            'ultima_vez'  => $ahora,
        ], true);

        return $this->find($id);
    }
}
