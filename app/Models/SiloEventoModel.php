<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Log de eventos de Silo (panel de alertas, todavía sin vista propia):
 * carpetas saltadas en el escaneo, IDs de negocio duplicados, resúmenes de
 * cada pasada. Ver Silo\Agente::escaneo() y App\Services\SiloService.
 */
class SiloEventoModel extends Model
{
    protected $table         = 'silo_eventos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = '';

    protected $allowedFields = ['tipo', 'unidad_id', 'pieza_id', 'referencia', 'motivo', 'detalle'];

    public function registrar(string $tipo, array $datos = []): int
    {
        return (int) $this->insert(array_merge(['tipo' => $tipo], $datos), true);
    }

    public function deUnidad(int $unidadId, int $limite = 100): array
    {
        return $this->where('unidad_id', $unidadId)->orderBy('id', 'DESC')->findAll($limite);
    }

    public function recientes(int $limite = 50): array
    {
        return $this->orderBy('id', 'DESC')->findAll($limite);
    }
}
