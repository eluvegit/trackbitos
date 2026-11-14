<?php

namespace App\Controllers;

use App\Services\GimnasioStats;

class Gimnasio extends BaseController
{
    public function index()
    {
        helper('gimnasio');
        $vista = gim_vista('gimnasio/index');
        if ($vista === 'gimnasio/index') {
            return view($vista);
        }

        $db    = \Config\Database::connect();
        $stats = new GimnasioStats();
        $dias  = $stats->dias(date('Y-m-d', strtotime('-54 weeks')));

        $ultima = $db->table('gimnasio_entrenamientos')->orderBy('fecha', 'DESC')->limit(1)->get()->getRowArray();
        if ($ultima) {
            $ultima['_nombres'] = array_column($db->table('gimnasio_entrenamiento_ejercicios ee')
                ->select('ge.nombre')
                ->join('gimnasio_ejercicios ge', 'ge.id = ee.ejercicio_id')
                ->where('ee.entrenamiento_id', $ultima['id'])
                ->orderBy('ee.orden')->get()->getResultArray(), 'nombre');
            $ultima['_dia'] = $dias[$ultima['fecha']] ?? null;
        }

        // Mesociclo con bloques pendientes: el siguiente que toca
        $siguienteBloque = null;
        $plan = $db->table('gimnasio_plan_bloques b')
            ->select('b.*, p.nombre AS plan_nombre, p.ejercicio, p.e1rm_base, p.redondeo_kg')
            ->join('gimnasio_planes p', 'p.id = b.plan_id')
            ->where('b.estado !=', 'hecho')
            ->orderBy('b.updated_at', 'DESC')->orderBy('b.orden', 'ASC')
            ->limit(1)->get()->getRowArray();
        if ($plan) {
            // El de menor orden pendiente de ese plan
            $siguienteBloque = $db->table('gimnasio_plan_bloques')
                ->where('plan_id', $plan['plan_id'])->where('estado !=', 'hecho')
                ->orderBy('orden', 'ASC')->limit(1)->get()->getRowArray();
            if ($siguienteBloque) {
                $e1rm = (float) ($siguienteBloque['e1rm_snapshot'] ?: $plan['e1rm_base']);
                $paso = (float) ($plan['redondeo_kg'] ?: 5);
                $siguienteBloque['_plan'] = $plan['plan_nombre'];
                $siguienteBloque['_ejercicio'] = $plan['ejercicio'];
                $siguienteBloque['_top'] = round($e1rm * (float) $siguienteBloque['top_pct_min'] / $paso) * $paso;
            }
        }

        return view($vista, [
            'dias'            => $dias,
            'constancia'      => $stats->constancia(),
            'hoy'             => $dias[date('Y-m-d')] ?? null,
            'ultima'          => $ultima,
            'principales'     => $stats->principales(),
            'semana'          => $stats->semanaPorGrupo(),
            'siguienteBloque' => $siguienteBloque,
        ]);
    }
}
