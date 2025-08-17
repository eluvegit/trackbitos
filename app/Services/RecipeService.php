<?php
// app/Services/RecipeService.php
namespace App\Services;

use App\Models\ComidasRecetasModel;
use App\Models\ComidasRecetaIngredientesModel;
use App\Models\ComidasAlimentosModel;

class RecipeService
{
    /**
     * Recalcula la receta (por 100 g) a partir de sus ingredientes y crea/actualiza
     * el "alimento virtual" en comidas_alimentos (es_receta=1, receta_id).
     * Devuelve el id del alimento virtual o null si no procede.
     */
    public function rebuildAlimentoFromReceta(int $recetaId, int $userId = 1): ?int
    {
        $recM  = new ComidasRecetasModel();
        $ingM  = new ComidasRecetaIngredientesModel();
        $alimM = new ComidasAlimentosModel();

        $receta = $recM->find($recetaId);
        if (!$receta) return null;

        $ings = $ingM->where('receta_id', $recetaId)->findAll();
        // Si no hay ingredientes, deja el alimento virtual en 0s o créalo vacío
        if (empty($ings)) {
            $existing = $alimM->where('receta_id', $recetaId)->first();
            $payloadVacio = [
                'user_id'     => $userId,
                'nombre'      => $receta['nombre'],
                'descripcion' => 'Receta (sin ingredientes)',
                'es_liquido'  => 0,
                'kcal'=>0,'proteina_g'=>0,'carbohidratos_g'=>0,'azucares_g'=>0,
                'fibra_g'=>0,'grasas_g'=>0,'grasas_saturadas_g'=>0,
                'sodio_mg'=>0,'omega3_mg'=>0,'omega6_mg'=>0,
                'es_receta'   => 1,
                'receta_id'   => $recetaId,
            ];
            if ($existing) {
                $alimM->update($existing['id'], $payloadVacio);
                return (int)$existing['id'];
            }
            $alimM->insert($payloadVacio);
            return (int)$alimM->getInsertID();
        }

        // Sumar macros totales: cada alimento está expresado por 100g
        $sum = [
            'kcal'=>0,'proteina_g'=>0,'carbohidratos_g'=>0,'azucares_g'=>0,
            'fibra_g'=>0,'grasas_g'=>0,'grasas_saturadas_g'=>0,
            'sodio_mg'=>0,'omega3_mg'=>0,'omega6_mg'=>0
        ];
        $totalG = 0.0;

        // Evita N+1
        $alimCache = [];
        foreach ($ings as $ing) {
            $g = (float)($ing['gramos'] ?? 0);
            if ($g <= 0) continue;
            $totalG += $g;

            if (!isset($alimCache[$ing['alimento_id']])) {
                $alimCache[$ing['alimento_id']] = $alimM->find($ing['alimento_id']);
            }
            $a = $alimCache[$ing['alimento_id']] ?? null;
            if (!$a) continue;

            $factor = $g / 100.0; // porque los alimentos ya están “por 100 g”
            foreach ($sum as $k => $_) {
                $sum[$k] += (float)($a[$k] ?? 0) * $factor;
            }
        }

        // Normalizar a 100 g usando el total sumado (no usamos raciones)
        if ($totalG <= 0) $totalG = 1; // evita división por cero
        $norm = array_map(fn($v)=> round($v * (100.0/$totalG), 2), $sum);

        // Upsert alimento virtual
        $payload = [
            'user_id'     => $userId,
            'nombre'      => $receta['nombre'],
            'marca'       => null,
            'descripcion' => 'Receta',
            'es_liquido'  => 0,
            'kcal'              => $norm['kcal'],
            'proteina_g'        => $norm['proteina_g'],
            'carbohidratos_g'   => $norm['carbohidratos_g'],
            'azucares_g'        => $norm['azucares_g'],
            'fibra_g'           => $norm['fibra_g'],
            'grasas_g'          => $norm['grasas_g'],
            'grasas_saturadas_g'=> $norm['grasas_saturadas_g'],
            'sodio_mg'          => $norm['sodio_mg'],
            'omega3_mg'         => $norm['omega3_mg'],
            'omega6_mg'         => $norm['omega6_mg'],
            'es_receta'         => 1,
            'receta_id'         => $recetaId,
        ];

        $existing = $alimM->where('receta_id', $recetaId)->first();
        if ($existing) {
            $alimM->update($existing['id'], $payload);
            return (int)$existing['id'];
        } else {
            $alimM->insert($payload);
            return (int)$alimM->getInsertID();
        }
    }
}
