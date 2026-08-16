<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;

/**
 * Cada vez que se abre Blender para trabajar en una rama. Genera un .blend
 * numerado. OJO: nombre de tabla/modelo prefijado ("Pieza...") a propósito
 * — ya existe un SesionModel/tabla "sesiones" para el módulo de rodajes
 * fotográficos, completamente distinto. No confundir ni reutilizar.
 */
class PiezaSesionModel extends Model
{
    protected $table         = 'piezas_sesiones';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'rama_id', 'numero', 'maquina_id', 'abierta_en', 'cerrada_en',
        'ruta_blend', 'hash_blend', 'tamano_bytes', 'hash_padre',
        'subida_en', 'log', 'purgada',
    ];

    /**
     * Comprueba a nivel de variante (no solo de rama): con la invariante 2
     * ya garantizada, solo puede haber una rama abierta por variante, pero
     * esta consulta no depende de esa garantía para ser correcta.
     */
    public function hayAbiertaParaVariante(int $varianteId): bool
    {
        return (bool) $this->select('piezas_sesiones.id')
            ->join('piezas_ramas', 'piezas_ramas.id = piezas_sesiones.rama_id')
            ->where('piezas_ramas.variante_id', $varianteId)
            ->where('piezas_sesiones.cerrada_en', null)
            ->first();
    }

    /**
     * Invariante 3: como mucho una sesión sin cerrar por variante. Actúa
     * como bloqueo de máquina — se niega y explica si ya hay una abierta.
     */
    public function abrir(int $ramaId, int $maquinaId): array
    {
        $rama = (new PiezaRamaModel())->find($ramaId);
        if (!$rama) {
            throw new RuntimeException("Rama {$ramaId} no encontrada.");
        }

        $db = $this->db;
        $db->transStart();
        $db->query(
            'SELECT s.id FROM piezas_sesiones s JOIN piezas_ramas r ON r.id = s.rama_id '
            . 'WHERE r.variante_id = ? FOR UPDATE',
            [$rama['variante_id']]
        );

        if ($this->hayAbiertaParaVariante($rama['variante_id'])) {
            $db->transComplete();

            throw new RuntimeException(
                'Ya hay una sesión sin cerrar para esta variante (bloqueo de máquina). '
                . 'Cierra esa sesión antes de abrir otra.'
            );
        }

        $id = $this->insert([
            'rama_id'    => $ramaId,
            'numero'     => $this->siguienteNumero($ramaId),
            'maquina_id' => $maquinaId,
            'abierta_en' => date('Y-m-d H:i:s'),
        ], true);

        $db->transComplete();
        if ($db->transStatus() === false) {
            throw new RuntimeException('No se pudo abrir la sesión: fallo de transacción.');
        }

        return $this->find($id);
    }

    public function ultimaSubida(int $ramaId): ?array
    {
        return $this->where('rama_id', $ramaId)
            ->where('subida_en IS NOT NULL')
            ->orderBy('numero', 'DESC')
            ->first();
    }

    public function cerrar(int $sesionId): array
    {
        $this->update($sesionId, ['cerrada_en' => date('Y-m-d H:i:s')]);

        return $this->find($sesionId);
    }

    public function siguienteNumero(int $ramaId): int
    {
        $fila = $this->where('rama_id', $ramaId)->selectMax('numero')->first();

        return ((int) ($fila['numero'] ?? 0)) + 1;
    }
}
