<?php

namespace App\Models;

use CodeIgniter\Model;

class SiloFicheroModel extends Model
{
    protected $table         = 'silo_ficheros';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = '';

    protected $allowedFields = ['pieza_id', 'nombre', 'tipo', 'tamano_bytes', 'hash'];

    public function deLaPieza(int $piezaId): array
    {
        return $this->where('pieza_id', $piezaId)->orderBy('nombre', 'ASC')->findAll();
    }

    public function sumaTamano(int $piezaId): int
    {
        $fila = $this->selectSum('tamano_bytes')->where('pieza_id', $piezaId)->first();

        return (int) ($fila['tamano_bytes'] ?? 0);
    }

    /**
     * Ranking global de ficheros por tamaño en disco — vista "qué es lo
     * que más ocupa" (/silo/ranking). Cada fila trae la carpeta (pieza) a
     * la que pertenece para poder enlazarla. `$tipo` opcional acota a
     * foto/video/otro. Solo ficheros con tamaño conocido (los que aún no
     * ha medido el agente no cuentan).
     *
     * @return array<int, array>
     */
    public function ranking(int $limite = 100, ?string $tipo = null): array
    {
        $builder = $this
            ->select('silo_ficheros.id, silo_ficheros.nombre, silo_ficheros.tipo, silo_ficheros.tamano_bytes, silo_ficheros.hash, silo_ficheros.pieza_id, p.id_negocio, p.nombre_carpeta')
            ->join('silo_piezas p', 'p.id = silo_ficheros.pieza_id')
            ->where('silo_ficheros.tamano_bytes IS NOT NULL')
            ->orderBy('silo_ficheros.tamano_bytes', 'DESC')
            ->limit(max(1, $limite));

        if ($tipo !== null && $tipo !== '') {
            $builder->where('silo_ficheros.tipo', $tipo);
        }

        return $builder->findAll();
    }

    /**
     * Totales para la cabecera del ranking: nº de ficheros y bytes por
     * tipo (foto/video/otro), más una entrada `total`. Solo cuenta los
     * ficheros con tamaño conocido.
     *
     * @return array<string, array{ficheros: int, bytes: int}>
     */
    public function totalesPorTipo(): array
    {
        $filas = $this
            ->select('tipo, COUNT(*) AS ficheros, SUM(tamano_bytes) AS bytes')
            ->where('tamano_bytes IS NOT NULL')
            ->groupBy('tipo')
            ->findAll();

        $out    = [];
        $totFic = 0;
        $totByt = 0;
        foreach ($filas as $f) {
            $out[$f['tipo']] = ['ficheros' => (int) $f['ficheros'], 'bytes' => (int) $f['bytes']];
            $totFic += (int) $f['ficheros'];
            $totByt += (int) $f['bytes'];
        }
        $out['total'] = ['ficheros' => $totFic, 'bytes' => $totByt];

        return $out;
    }
}
