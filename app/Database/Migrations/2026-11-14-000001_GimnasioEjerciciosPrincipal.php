<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ejercicios "principales" marcados con estrella: son los que salen en
 * Estadísticas y en el resumen de cada sesión. Antes eran fijos (press banca,
 * peso muerto, sentadilla) y se buscaban por nombre; aquí se marcan esos
 * mismos de partida para que nada cambie hasta que se toquen las estrellas.
 */
class GimnasioEjerciciosPrincipal extends Migration
{
    public function up()
    {
        $this->forge->addColumn('gimnasio_ejercicios', [
            'principal' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0, 'after' => 'catalogo_id'],
        ]);

        $patrones = [
            ['press banca', 'press de banca'],
            ['peso muerto', 'deadlift'],
            ['sentadilla', 'sentadillas', 'back squat', 'squat'],
        ];
        foreach ($patrones as $lista) {
            $b = $this->db->table('gimnasio_ejercicios')->select('id');
            $b->groupStart();
            foreach ($lista as $p) {
                $b->orLike('LOWER(nombre)', $p);
            }
            $b->groupEnd();
            $fila = $b->orderBy('id', 'ASC')->get()->getRowArray();
            if ($fila) {
                $this->db->table('gimnasio_ejercicios')->where('id', $fila['id'])->update(['principal' => 1]);
            }
        }
    }

    public function down()
    {
        $this->forge->dropColumn('gimnasio_ejercicios', 'principal');
    }
}
