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

    /**
     * Próximo código libre siguiendo la nomenclatura H1, H2... para poder
     * añadir huecos sin tener que teclear el nombre cada vez. Reutiliza el
     * primer número libre (no siempre el máximo + 1): si se borra H2 de
     * H1-H4, el siguiente hueco que se cree vuelve a ser H2 en vez de H5,
     * para que la numeración no quede llena de huecos sueltos al cabo de
     * varios altas/bajas. Esto no renombra ningún hueco existente — solo
     * decide el número del que se está creando ahora.
     */
    public function siguienteCodigo(int $estucheId): string
    {
        $usados = [];
        foreach ($this->deEstuche($estucheId) as $hueco) {
            if (preg_match('/^H(\d+)$/i', $hueco['codigo'], $m)) {
                $usados[(int) $m[1]] = true;
            }
        }

        for ($i = 1; isset($usados[$i]); $i++) {
        }

        return 'H' . $i;
    }
}
