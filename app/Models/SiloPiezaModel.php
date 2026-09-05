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
        $builder = $this->select('silo_piezas.*, cat.nombre AS categoria_nombre')
            ->join('silo_vocabulario cat', 'cat.id = silo_piezas.categoria_id', 'left')
            ->orderBy('silo_piezas.nombre_carpeta', 'ASC');

        if (!empty($filtros['q'])) {
            $q = $filtros['q'];
            $builder->groupStart()
                ->like('silo_piezas.id_negocio', $q)
                ->orLike('silo_piezas.nombre_carpeta', $q)
                ->groupEnd();
        }

        if (!empty($filtros['categoria_id'])) {
            $builder->where('silo_piezas.categoria_id', (int) $filtros['categoria_id']);
        }

        return $this->adjuntarAtributos($builder->findAll());
    }

    /**
     * Piezas que viven en una unidad, estilo "contenido de esta carpeta".
     * `$orden`: 'nombre' (por defecto, alfabético = orden de alta dentro
     * del año gracias al correlativo del ID, como un explorador) o 'fecha'
     * (cronológico de verdad — útil sobre todo en una unidad de Nivel 2
     * "Año", donde el ID de alta no coincide con el orden real de las
     * fechas; petición 2026-09-05). Sin fecha va al final, no al principio.
     */
    public function deLaUnidad(int $unidadId, string $orden = 'nombre'): array
    {
        $query = $this->select('silo_piezas.*, cat.nombre AS categoria_nombre')
            ->join('silo_ubicaciones', 'silo_ubicaciones.pieza_id = silo_piezas.id')
            ->join('silo_vocabulario cat', 'cat.id = silo_piezas.categoria_id', 'left')
            ->where('silo_ubicaciones.unidad_id', $unidadId)
            ->groupBy('silo_piezas.id');

        if ($orden === 'fecha') {
            $query->orderBy('silo_piezas.fecha IS NULL', 'ASC', false)
                  ->orderBy('silo_piezas.fecha', 'ASC');
        } else {
            $query->orderBy('silo_piezas.nombre_carpeta', 'ASC');
        }

        return $this->adjuntarAtributos($query->findAll());
    }

    /**
     * Adjunta a cada pieza su lista de atributos [{tipo, nombre}] (persona,
     * lugar, tema, evento) en una sola consulta, para pintar el nombre de
     * carpeta como badges en los listados.
     */
    private function adjuntarAtributos(array $piezas): array
    {
        if ($piezas === []) {
            return $piezas;
        }

        $ids   = array_column($piezas, 'id');
        $filas = (new SiloPiezaAtributoModel())
            ->select('silo_pieza_atributo.pieza_id, silo_vocabulario.tipo, silo_vocabulario.nombre')
            ->join('silo_vocabulario', 'silo_vocabulario.id = silo_pieza_atributo.vocabulario_id')
            ->whereIn('silo_pieza_atributo.pieza_id', $ids)
            ->orderBy('silo_vocabulario.tipo', 'ASC')
            ->orderBy('silo_vocabulario.nombre', 'ASC')
            ->findAll();

        $porPieza = [];
        foreach ($filas as $f) {
            $porPieza[(int) $f['pieza_id']][] = ['tipo' => $f['tipo'], 'nombre' => $f['nombre']];
        }

        foreach ($piezas as &$p) {
            $p['atributos'] = $porPieza[(int) $p['id']] ?? [];
        }

        return $piezas;
    }
}
