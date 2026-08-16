<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Asiento de sincronización descarga->subida (invariante 8, spec 4.4).
 * Append-only: nunca se borra, solo se cierra. Este modelo guarda las
 * consultas del cuadre; la lógica de "qué se acepta y qué se rechaza" vive
 * en App\Services\PiezaSyncService, que es quien las ejecuta en transacción.
 */
class PiezaDescargaModel extends Model
{
    protected $table         = 'piezas_descargas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'sesion_id', 'variante_id', 'rama_id', 'maquina_id',
        'motivo', 'descargado_en', 'hash_entregado',
        'cerrada', 'cerrada_en', 'cerrada_por', 'cerrada_sesion_id', 'motivo_forzado',
    ];

    public function abiertasParaVariante(int $varianteId): array
    {
        return $this->where('variante_id', $varianteId)->where('cerrada', 0)->findAll();
    }

    /**
     * El asiento que una subida tiene que cuadrar: la descarga abierta de
     * esta máquina en esta rama. La más reciente si hubiera varias — no
     * debería haberlas, pero si las hay, la última es la que corresponde al
     * fichero que el usuario tiene ahora mismo en el disco.
     */
    public function abiertaDeMaquina(int $maquinaId, int $ramaId): ?array
    {
        return $this->where('maquina_id', $maquinaId)
            ->where('rama_id', $ramaId)
            ->where('cerrada', 0)
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * Descargas sin cerrar en cualquier máquina que no sea la actual: el
     * aviso de "hay una copia viva en el otro equipo" (spec 4.4), que se
     * comprueba antes de entregar nada.
     */
    public function abiertasDeOtrasMaquinas(int $varianteId, int $maquinaId): array
    {
        return $this->where('variante_id', $varianteId)
            ->where('cerrada', 0)
            ->where('maquina_id !=', $maquinaId)
            ->findAll();
    }

    /**
     * Único camino para cerrar un asiento. $por explica con qué prueba se
     * cerró: 'subida' (llegó el fichero), 'sin_cambios' (el hash local
     * seguía siendo el entregado) o 'forzado' (no hay prueba, hay motivo).
     */
    public function cerrar(int $descargaId, string $por, ?int $cerradaSesionId = null, ?string $motivoForzado = null): array
    {
        $this->update($descargaId, [
            'cerrada'           => 1,
            'cerrada_en'        => date('Y-m-d H:i:s'),
            'cerrada_por'       => $por,
            'cerrada_sesion_id' => $cerradaSesionId,
            'motivo_forzado'    => $motivoForzado,
        ]);

        return $this->find($descargaId);
    }
}
