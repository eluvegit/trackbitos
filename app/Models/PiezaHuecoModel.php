<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Compartimento dentro de un estuche (App\Models\PiezaEstucheModel) — la
 * ubicación real donde se guarda una pieza. El código que se rotula
 * físicamente es estuche.codigo + hueco.codigo, p. ej. "E1H2".
 */
class PiezaHuecoModel extends Model
{
    protected $table         = 'piezas_huecos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = '';

    protected $allowedFields = ['estuche_id', 'codigo', 'notas'];

    protected $validationRules = [
        'codigo' => 'required|max_length[20]',
    ];

    /** Todos los huecos de un estuche, en orden de código. */
    public function deEstuche(int $estucheId): array
    {
        return $this->where('estuche_id', $estucheId)->orderBy('codigo', 'ASC')->findAll();
    }

    /** "E1H2": el código combinado estuche+hueco, tal y como se rotula. */
    public static function codigoCompleto(array $estuche, array $hueco): string
    {
        return $estuche['codigo'] . $hueco['codigo'];
    }
}
