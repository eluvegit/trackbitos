<?php
namespace App\Models;

use CodeIgniter\Model;

class ComidasAlimentosControlModel extends Model
{
    protected $table = 'comidas_alimentos_control';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'alimento_id', 'periodo_dias', 'min_veces', 'max_veces', 'unidad', 'created_at', 'updated_at'
    ];

    // Obtiene todos los alimentos controlados con info del alimento
    public function getAllWithInfo()
    {
        return $this->select('comidas_alimentos_control.*, a.nombre, a.kcal, a.proteina_g, a.carbohidratos_g, a.grasas_g')
                    ->join('comidas_alimentos a', 'a.id = comidas_alimentos_control.alimento_id')
                    ->orderBy('a.nombre', 'ASC')
                    ->findAll();
    }

    // Última ingesta de un alimento
    public function getUltimaIngesta($alimento_id)
    {
        $db = \Config\Database::connect();
        $query = $db->table('comidas_diario')
                    ->select('dia_id, hora')
                    ->where('item_tipo', 'alimento')
                    ->where('item_id', $alimento_id)
                    ->orderBy('dia_id', 'DESC')
                    ->orderBy('hora', 'DESC')
                    ->limit(1)
                    ->get();

        return $query->getRowArray();
    }

    // Veces consumidas en los últimos periodo_dias
    public function getVecesEnPeriodo($alimento_id, $periodo_dias)
    {
        $db = \Config\Database::connect();
        $query = $db->table('comidas_diario')
                    ->where('item_tipo', 'alimento')
                    ->where('item_id', $alimento_id)
                    ->where('dia_id >=', date('Y-m-d', strtotime("-$periodo_dias days")))
                    ->countAllResults();
        return $query;
    }
}
