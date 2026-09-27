<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Log de eventos de Silo: carpetas saltadas en el escaneo, IDs de negocio
 * duplicados, errores de ingesta, carpetas desaparecidas y el resumen de
 * cada pasada. Ver Silo\Agente::escaneo() y App\Services\SiloService.
 * Se pinta en /silo/avisos (Web::avisos).
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

    /** Lo que merece atención de un escaneo: si hay alguno, el panel lo cuenta como problema. */
    public const TIPOS_PROBLEMA = ['id_duplicado', 'error_ingesta', 'hash_distinto', 'catalogo_mas_nuevo'];

    /**
     * Avisos de "la BD viva va por detrás de la réplica de un disco" de los
     * últimos días: se registran en el handshake (una vez por réplica), no
     * en cada escaneo, así que el panel los enseña aparte, arriba del todo.
     */
    public function catalogoMasNuevoRecientes(int $dias = 7): array
    {
        return $this->where('tipo', 'catalogo_mas_nuevo')
            ->where('creado_en >=', date('Y-m-d H:i:s', strtotime("-{$dias} days")))
            ->orderBy('id', 'DESC')->findAll();
    }

    /**
     * Estado ACTUAL por unidad: su último evento `escaneo` y los eventos que
     * registró esa misma pasada. Agente::escaneo() registra los de cada
     * entrada durante la pasada y el `escaneo` (resumen) al final, así que
     * son los de esa unidad con id entre el `escaneo` anterior y el último.
     * Sirve para que lo repetido en cada pasada (las mismas carpetas
     * saltadas) salga una vez y lo ya corregido deje de salir.
     *
     * @return array<int, array{escaneo: array, eventos: array}> por unidad_id
     */
    public function ultimoEscaneoPorUnidad(): array
    {
        $ultimos = $this->select('unidad_id, MAX(id) AS id')
            ->where('tipo', 'escaneo')->where('unidad_id IS NOT NULL')
            ->groupBy('unidad_id')->findAll();

        $porUnidad = [];
        foreach ($ultimos as $u) {
            $unidadId = (int) $u['unidad_id'];
            $escaneo  = $this->find((int) $u['id']);
            $anterior = $this->selectMax('id')
                ->where('tipo', 'escaneo')->where('unidad_id', $unidadId)->where('id <', $escaneo['id'])
                ->first();

            $porUnidad[$unidadId] = [
                'escaneo' => $escaneo,
                'eventos' => $this->where('unidad_id', $unidadId)
                    ->where('id >', (int) ($anterior['id'] ?? 0))->where('id <', $escaneo['id'])
                    ->where('tipo !=', 'escaneo')
                    ->orderBy('id', 'ASC')->findAll(),
            ];
        }

        return $porUnidad;
    }

    /** Nº de problemas (duplicados + errores) en el último escaneo de cada unidad, para el botón del índice. */
    public function contarProblemasActuales(): int
    {
        $total = 0;
        foreach ($this->ultimoEscaneoPorUnidad() as $u) {
            foreach ($u['eventos'] as $e) {
                $total += in_array($e['tipo'], self::TIPOS_PROBLEMA, true) ? 1 : 0;
            }
        }

        return $total;
    }

    /**
     * Historial para el panel, lo más reciente primero. Cada escaneo vuelve
     * a registrar lo mismo mientras no se corrija, así que las filas
     * idénticas (mismo tipo, unidad, carpeta, motivo y detalle) salen
     * agrupadas en una: `veces`, `primera` y `ultima` vez, y `ultimo_id`.
     * Sin `$tipo` deja fuera las carpetas saltadas (se repiten en cada
     * pasada y taparían lo demás); con `$tipo`, solo ese tipo.
     */
    public function historial(?string $tipo, int $limite = 150): array
    {
        $builder = $this->db->table('silo_eventos e')
            ->select('e.tipo, e.unidad_id, e.referencia, e.motivo, e.detalle, u.nivel, u.numero')
            ->select('COUNT(*) AS veces, MIN(e.creado_en) AS primera, MAX(e.creado_en) AS ultima, MAX(e.id) AS ultimo_id')
            ->join('silo_unidades u', 'u.id = e.unidad_id', 'left')
            ->groupBy('e.tipo, e.unidad_id, e.referencia, e.motivo, e.detalle, u.nivel, u.numero');

        $tipo !== null
            ? $builder->where('e.tipo', $tipo)
            : $builder->where('e.tipo !=', 'carpeta_saltada');

        return $builder->orderBy('ultimo_id', 'DESC')->limit($limite)->get()->getResultArray();
    }
}
