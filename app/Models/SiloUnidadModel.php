<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Unidad física de Silo (Maestro/USB/disco). El alta la resuelve
 * SiloService::crearUnidad() (calcula `numero` y genera `fichero_control`);
 * este modelo es solo acceso a datos.
 */
class SiloUnidadModel extends Model
{
    protected $table         = 'silo_unidades';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = '';

    protected $allowedFields = [
        'nivel', 'numero', 'etiqueta', 'identificacion_fisica', 'tipo_fisico', 'ruta_montaje', 'agrupador', 'capacidad_bytes',
        'ultima_sincronizacion', 'fichero_control',
    ];

    public function porNivel(int $nivel): array
    {
        return $this->where('nivel', $nivel)->orderBy('numero', 'ASC')->findAll();
    }

    /** Siguiente número de orden dentro de un nivel (1ª, 2ª, 3ª unidad...). */
    public function siguienteNumero(int $nivel): int
    {
        $ultimo = $this->where('nivel', $nivel)->orderBy('numero', 'DESC')->first();

        return $ultimo ? ((int) $ultimo['numero'] + 1) : 1;
    }

    /** Unidades ya destinadas a un mismo "cubo" de propagación (mismo año o misma categoría), en orden de creación. */
    public function buscarPorAgrupador(int $nivel, string $agrupador): array
    {
        return $this->where('nivel', $nivel)->where('agrupador', $agrupador)->orderBy('numero', 'ASC')->findAll();
    }

    /** Resuelve una unidad por la ruta de montaje que reporta el agente `.py` en el handshake. */
    public function porRutaMontaje(string $rutaMontaje): ?array
    {
        return $this->where('ruta_montaje', $rutaMontaje)->first();
    }
}
