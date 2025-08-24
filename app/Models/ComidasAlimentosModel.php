<?php namespace App\Models;

use CodeIgniter\Model;

class ComidasAlimentosModel extends Model
{
    protected $table         = 'comidas_alimentos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    // Ordenado como dejaste la tabla (meta → físicas → receta → macros → minerales → vitaminas → timestamps)
    protected $allowedFields = [
        // Identidad / meta
        'nombre','marca','descripcion',

        // Propiedades físicas
        'es_liquido','densidad_g_ml',

        // Recetas
        'es_receta','receta_id',

        // Macros (por 100 g)
        'kcal','proteina_g','carbohidratos_g','azucares_g','fibra_g',
        'grasas_g','grasas_saturadas_g','omega3_mg','omega6_mg','sodio_mg',

        // Minerales
        'calcio_mg','hierro_mg','magnesio_mg','fosforo_mg','potasio_mg',
        'zinc_mg','selenio_ug','cobre_mg','manganeso_mg','yodo_ug',

        // Vitaminas
        'vitamina_a_rae_ug','vitamina_c_mg','vitamina_d_ug','vitamina_e_mg','vitamina_k_ug',
    ];

    protected $validationRules = [
        'nombre' => 'required|min_length[2]|max_length[180]',
        // Opcionalmente: 'proteina_g' => 'permit_empty|numeric', etc.
    ];

    // ——— Normaliza comas/strings a números antes de guardar ——–
    protected $beforeInsert = ['normalizeNumbers'];
    protected $beforeUpdate = ['normalizeNumbers'];

    private array $numericFields = [
        'densidad_g_ml',
        'kcal','proteina_g','carbohidratos_g','azucares_g','fibra_g',
        'grasas_g','grasas_saturadas_g','omega3_mg','omega6_mg','sodio_mg',
        'calcio_mg','hierro_mg','magnesio_mg','fosforo_mg','potasio_mg',
        'zinc_mg','selenio_ug','cobre_mg','manganeso_mg','yodo_ug',
        'vitamina_a_rae_ug','vitamina_c_mg','vitamina_d_ug','vitamina_e_mg','vitamina_k_ug',
    ];

    protected function normalizeNumbers(array $data)
    {
        if (!isset($data['data']) || !is_array($data['data'])) return $data;

        foreach ($this->numericFields as $f) {
            if (!array_key_exists($f, $data['data'])) continue;
            $v = $data['data'][$f];
            if ($v === '' || $v === null) { $data['data'][$f] = 0; continue; }
            if (is_string($v)) $v = str_replace(',', '.', $v);
            $data['data'][$f] = is_numeric($v) ? (float)$v : 0;
        }

        // booleans
        $data['data']['es_liquido'] = !empty($data['data']['es_liquido']) ? 1 : 0;
        $data['data']['es_receta']  = !empty($data['data']['es_receta'])  ? 1 : 0;

        return $data;
    }
}
