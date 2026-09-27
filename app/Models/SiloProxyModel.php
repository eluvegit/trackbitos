<?php

namespace App\Models;

use CodeIgniter\Model;

class SiloProxyModel extends Model
{
    protected $table         = 'silo_proxies';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = '';

    protected $allowedFields = ['pieza_id', 'fichero_id', 'tipo', 'url', 'orden'];

    public function deLaPieza(int $piezaId): array
    {
        return $this->where('pieza_id', $piezaId)->orderBy('tipo', 'ASC')->orderBy('orden', 'ASC')->findAll();
    }

    /**
     * Proxies de varias piezas de una sola consulta, agrupados por pieza_id
     * (fotos primero, luego fotogramas de vídeo). Para la vista de
     * miniaturas del índice sin hacer una consulta por carpeta.
     *
     * @return array<int, array<int, array>>
     */
    public function dePiezas(array $piezaIds): array
    {
        $piezaIds = array_values(array_unique(array_map('intval', $piezaIds)));
        if (!$piezaIds) {
            return [];
        }

        $filas = $this->whereIn('pieza_id', $piezaIds)
            ->orderBy('pieza_id', 'ASC')->orderBy('tipo', 'ASC')->orderBy('orden', 'ASC')
            ->findAll();

        $porPieza = [];
        foreach ($filas as $f) {
            $porPieza[(int) $f['pieza_id']][] = $f;
        }

        return $porPieza;
    }

    /**
     * ¿Ya tiene algún proxy REAL (generado por el agente `.py` con ffmpeg,
     * `url` bajo `assets/silo/proxies/...`)? Los simulados de antes (URL
     * `https://picsum.photos/...`) no cuentan — así `Agente::escaneo()` sabe
     * a qué piezas pedirle al agente que genere proxies de verdad todavía.
     */
    public function tieneProxiesReales(int $piezaId): bool
    {
        return $this->where('pieza_id', $piezaId)
            ->like('url', 'assets/silo/proxies/', 'after')
            ->countAllResults() > 0;
    }
}
