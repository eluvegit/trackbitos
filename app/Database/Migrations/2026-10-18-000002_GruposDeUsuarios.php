<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Grupos de Myth\Auth: 'admin' (toda la web) e 'inventario' (solo su
 * sección de lectura del inventario de piezas). Los usuarios que ya
 * existían pasan a 'admin', que es lo que tenían de hecho.
 */
class GruposDeUsuarios extends Migration
{
    private array $grupos = [
        'admin'      => 'Acceso completo',
        'inventario' => 'Solo lectura del inventario de piezas',
    ];

    public function up()
    {
        foreach ($this->grupos as $nombre => $descripcion) {
            if (! $this->db->table('auth_groups')->where('name', $nombre)->countAllResults()) {
                $this->db->table('auth_groups')->insert(['name' => $nombre, 'description' => $descripcion]);
            }
        }

        $adminId = (int) $this->db->table('auth_groups')->where('name', 'admin')->get()->getRowArray()['id'];

        foreach ($this->db->table('users')->select('id')->get()->getResultArray() as $u) {
            $ya = $this->db->table('auth_groups_users')->where('group_id', $adminId)->where('user_id', $u['id'])->countAllResults();
            if (! $ya) {
                $this->db->table('auth_groups_users')->insert(['group_id' => $adminId, 'user_id' => $u['id']]);
            }
        }
    }

    public function down()
    {
        $ids = array_column($this->db->table('auth_groups')->whereIn('name', array_keys($this->grupos))->get()->getResultArray(), 'id');
        if ($ids !== []) {
            $this->db->table('auth_groups_users')->whereIn('group_id', $ids)->delete();
            $this->db->table('auth_groups')->whereIn('id', $ids)->delete();
        }
    }
}
