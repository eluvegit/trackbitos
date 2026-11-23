<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Perfil reutilizable de un modelo (Sesiones → pestaña Modelos). Sus
 * opciones de ropa están en sesion_modelo_ropa; qué modelos salen en cada
 * sesión, en sesion_modelos_sesion; y qué ropa se usa en cada una, en
 * sesion_ropa_elegida.
 */
class SesionModeloPerfilModel extends Model
{
    protected $table         = 'sesion_modelos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_at';
    protected $updatedField  = '';
    protected $allowedFields = ['nombre', 'contacto', 'notas'];

    /**
     * Modelos de una sesión con su ropa; cada opción de ropa lleva
     * 'elegida' (1/0) para ESA sesión.
     */
    public function deSesion(int $sesionId, ?int $soloModelo = null): array
    {
        $q = $this->select('sesion_modelos.*')
            ->join('sesion_modelos_sesion ms', 'ms.modelo_id = sesion_modelos.id')
            ->where('ms.sesion_id', $sesionId)
            ->orderBy('ms.id', 'ASC');
        if ($soloModelo !== null) $q->where('sesion_modelos.id', $soloModelo);
        $modelos = $q->findAll();
        if (!$modelos) return [];

        $ropa = $this->db->table('sesion_modelo_ropa r')
            ->select('r.*, (e.ropa_id IS NOT NULL) AS elegida', false)
            ->join('sesion_ropa_elegida e', 'e.ropa_id = r.id AND e.sesion_id = ' . (int) $sesionId, 'left')
            ->whereIn('r.modelo_id', array_column($modelos, 'id'))
            ->orderBy('r.orden', 'ASC')->orderBy('r.id', 'ASC')
            ->get()->getResultArray();

        $porModelo = [];
        foreach ($ropa as $r) $porModelo[(int) $r['modelo_id']][] = $r;

        $fotos = [];
        foreach ($this->db->table('sesion_modelo_fotos')->whereIn('modelo_id', array_column($modelos, 'id'))
            ->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray() as $f) {
            $fotos[(int) $f['modelo_id']][] = $f;
        }

        foreach ($modelos as &$m) {
            $m['ropa']  = $porModelo[(int) $m['id']] ?? [];
            $m['fotos'] = $fotos[(int) $m['id']] ?? [];
        }

        return $modelos;
    }
}
