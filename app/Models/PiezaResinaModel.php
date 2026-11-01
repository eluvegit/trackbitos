<?php

namespace App\Models;

use CodeIgniter\Model;

class PiezaResinaModel extends Model
{
    protected $table         = 'piezas_resinas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = '';

    protected $allowedFields = ['nombre', 'notas', 'archivada'];

    protected $validationRules = [
        'nombre' => 'required|max_length[120]',
    ];

    public function paraSelector(?int $actual = null): array
    {
        $q = $this->groupStart()->where('archivada', 0);
        if ($actual) {
            $q->orWhere('id', $actual);
        }

        return $q->groupEnd()->orderBy('nombre')->findAll();
    }

    /** La resina de la última placa de cada impresora: la que sigue en su cubeta. */
    public function enCadaImpresora(): array
    {
        $en = [];
        foreach (PiezaPlacaModel::IMPRESORAS as $imp) {
            $fila = db_connect()->table('piezas_placas')->select('resina_id')
                ->where('impresora', $imp)->where('resina_id IS NOT NULL')
                ->orderBy('creado_en', 'DESC')->orderBy('id', 'DESC')->get(1)->getRowArray();
            if ($fila) {
                $en[(int) $fila['resina_id']][] = $imp;
            }
        }

        return $en;
    }

    public function idPorNombre(string $nombre): ?int
    {
        $nombre = mb_substr(trim($nombre), 0, 120);
        if ($nombre === '') {
            return null;
        }
        $existente = $this->where('nombre', $nombre)->first();
        if ($existente) {
            if ($existente['archivada']) {
                $this->update($existente['id'], ['archivada' => 0]);
            }

            return (int) $existente['id'];
        }

        return (int) $this->insert(['nombre' => $nombre], true) ?: null;
    }
}
