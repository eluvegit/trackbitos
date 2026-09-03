<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Pieza de Silo: material fotográfico/vídeo resultante ya editado,
 * seleccionado o entregado. `nombre_carpeta` se calcula una vez en el alta
 * (SiloService::formatearNombreCarpeta()) y no se vuelve a tocar al
 * reclasificar — reclasificar es solo cambiar `categoria_id`/atributos.
 */
class SiloPiezaModel extends Model
{
    protected $table         = 'silo_piezas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = 'actualizado_en';

    protected $allowedFields = [
        'id_negocio', 'fecha', 'tipo', 'fuente',
        'categoria_id', 'subido', 'subido_en', 'fecha_generacion',
        'tamano_bytes', 'bloque_semantico', 'nombre_carpeta', 'notas',
    ];

    protected $validationRules = [
        'id_negocio'     => 'required|max_length[20]|is_unique[silo_piezas.id_negocio,id,{id}]',
        'nombre_carpeta' => 'required|max_length[500]',
    ];

    /**
     * Búsqueda de listado: texto libre sobre id_negocio/nombre_carpeta +
     * filtro opcional por categoría. No usa FULLTEXT (como
     * enlaces_items) porque el volumen esperado en esta fase es bajo;
     * revisar si hace falta cuando el catálogo crezca.
     */
    public function buscar(array $filtros = []): array
    {
        $builder = $this->orderBy('nombre_carpeta', 'ASC');

        if (!empty($filtros['q'])) {
            $q = $filtros['q'];
            $builder->groupStart()
                ->like('id_negocio', $q)
                ->orLike('nombre_carpeta', $q)
                ->groupEnd();
        }

        if (!empty($filtros['categoria_id'])) {
            $builder->where('categoria_id', (int) $filtros['categoria_id']);
        }

        return $builder->findAll();
    }

    /** Piezas que viven en una unidad, estilo "contenido de esta carpeta" (orden alfabético, como un explorador). */
    public function deLaUnidad(int $unidadId): array
    {
        return $this->select('silo_piezas.*')
            ->join('silo_ubicaciones', 'silo_ubicaciones.pieza_id = silo_piezas.id')
            ->where('silo_ubicaciones.unidad_id', $unidadId)
            ->groupBy('silo_piezas.id')
            ->orderBy('silo_piezas.nombre_carpeta', 'ASC')
            ->findAll();
    }
}
