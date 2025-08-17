<?php

namespace App\Controllers\Comidas;

use App\Controllers\BaseController;
use App\Models\ComidasDiasModel;
use App\Models\ComidasIngestasModel;
use App\Models\ComidasAlimentosModel;
use App\Models\ComidasRecetasModel;
use App\Models\ComidasAlimentoUnidadesModel;

class Diario extends BaseController
{
    private function currentUserId(): int
    {
        $uid = function_exists('user_id') ? (user_id() ?: 0) : 0;
        return $uid > 0 ? $uid : 1; // fallback mono-usuario
    }

    public function hoy()
    {
        // Redirige a la vista del día actual
        return redirect()->to(site_url('comidas/diario/' . date('Y-m-d')));
    }

    public function ver(string $fecha = null, string $tipo = null)
    {
        $userId = $this->currentUserId();

        // Si no viene fecha, usar hoy
        $fecha = $fecha ?: date('Y-m-d');

        $tipo = $tipo ?: 'almuerzo';


        // validar formato Y-m-d
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$d || $d->format('Y-m-d') !== $fecha) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->renderDia($userId, $fecha, $tipo);
    }

    private function renderDia(int $userId, string $fecha, string $tipo)
    {
        $objetivos = [
            'kcal'            => 2000,
            'carbohidratos_g' => 250,
            'grasas_g'        => 70,
            'proteina_g'      => 150
        ];

        $q        = trim((string) $this->request->getGet('q'));
        $itemTipo = $this->request->getGet('item_tipo') === 'receta' ? 'receta' : 'alimento';
        $alimentoId = (int) $this->request->getGet('alimento_id');

        $alimentosM = new ComidasAlimentosModel();
        $recetasM   = new ComidasRecetasModel();
        $porcionesM = new ComidasAlimentoUnidadesModel();
        $ingestasM  = new ComidasIngestasModel();
        $diasM      = new ComidasDiasModel();

        // Crear día si no existe
        $dia = $diasM->where(['user_id' => $userId, 'fecha' => $fecha])->first();
        if (!$dia) {
            $diasM->insert(['user_id' => $userId, 'fecha' => $fecha]);
            $dia = $diasM->find($diasM->getInsertID());
        }

        // Resultados buscador
        if ($itemTipo === 'receta') {
            $builder = $recetasM->where('user_id', $userId)->select('id,nombre');
        } else {
            $builder = $alimentosM->where('user_id', $userId)->select('id,nombre');
        }
        if ($q !== '') {
            $builder->like('nombre', $q);
        }
        $resultados = $builder->orderBy('nombre', 'ASC')->findAll(50);

        // Porciones
        $porciones = [];
        if ($itemTipo === 'alimento') {
            $targetId = $alimentoId ?: ($resultados[0]['id'] ?? 0);
            if ($targetId) {
                $porciones = $porcionesM->where('alimento_id', $targetId)->orderBy('id', 'ASC')->findAll();
            }
        }

        // Periodos fijos
        $tipos = ['almuerzo', 'merienda', 'cena', 'comida_nocturna', 'desayuno'];

        $ingPorTipo     = [];
        $resumenPorTipo = [];
        $totalesDia     = [
            'kcal'            => 0,
            'proteina_g'      => 0,
            'carbohidratos_g' => 0,
            'grasas_g'        => 0,
            'fibra_g'         => 0,
            'sodio_mg'        => 0,
            'azucares_g'      => 0
        ];

        $cacheAlimentos = [];
        $cachePorciones = [];

        foreach ($tipos as $t) {
            $ing = $ingestasM->where(['dia_id' => $dia['id'], 'tipo' => $t])->orderBy('hora', 'DESC')->findAll();

            // cache de porciones usadas
            $idsPorcionUsadas = array_values(array_unique(array_filter(array_map(fn($x) => $x['porcion_id'] ?? null, $ing))));
            if (!empty($idsPorcionUsadas)) {
                $porRows = $porcionesM->whereIn('id', $idsPorcionUsadas)->findAll();
                foreach ($porRows as $pr) {
                    $cachePorciones[$pr['id']] = $pr;
                }
            }

            $res = [
                'kcal'            => 0,
                'proteina_g'      => 0,
                'carbohidratos_g' => 0,
                'grasas_g'        => 0,
                'fibra_g'         => 0,
                'sodio_mg'        => 0,
                'azucares_g'      => 0
            ];

            foreach ($ing as &$i) {
                // nombre
                if ($i['item_tipo'] === 'alimento') {
                    if (!isset($cacheAlimentos[$i['item_id']])) {
                        $cacheAlimentos[$i['item_id']] = $alimentosM->find($i['item_id']);
                    }
                    $a = $cacheAlimentos[$i['item_id']];
                    $i['nombre'] = $a['nombre'] ?? 'Alimento #' . $i['item_id'];
                } else {
                    $r = $recetasM->find($i['item_id']);
                    $i['nombre'] = $r['nombre'] ?? 'Receta #' . $i['item_id'];
                }

                // cantidad_label
                if (!empty($i['cantidad_gramos'])) {
                    $i['cantidad_label'] = rtrim(rtrim(number_format((float)$i['cantidad_gramos'], 2, '.', ''), '0'), '.') . ' g';
                } elseif (!empty($i['porcion_id'])) {
                    $p    = $porcionesM->find($i['porcion_id']);
                    $desc = $p['descripcion'] ?? 'porción';
                    $gEq  = isset($p['gramos_equivalentes']) ? (float)$p['gramos_equivalentes'] : null;
                    $n    = isset($i['porciones']) ? (float)$i['porciones'] : 1.0;

                    $nFmt = rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
                    $gFmt = fn($g) => rtrim(rtrim(number_format((float)$g, 2, '.', ''), '0'), '.');

                    $descIncluyeGramos = (bool) preg_match('/\(\s*\d+(?:[.,]\d+)?\s*g\s*\)/i', $desc);

                    $label = $desc;
                    if ($gEq && !$descIncluyeGramos) {
                        $label .= ' (' . $gFmt($gEq) . ' g)';
                    }
                    if ($n > 1 || $n < 1) {
                        $label .= ' (x' . $nFmt . ')';
                    }
                    if ($gEq && ($n > 1 || $n < 1)) {
                        $label .= ' ≈ ' . $gFmt($n * $gEq) . ' g';
                    }
                    $i['cantidad_label'] = $label;
                } else {
                    $i['cantidad_label'] = '-';
                }

                // macros por alimento
                $i['macros'] = ['kcal' => 0, 'proteina_g' => 0, 'carbohidratos_g' => 0, 'grasas_g' => 0];
                if ($i['item_tipo'] === 'alimento' && !empty($cacheAlimentos[$i['item_id']])) {
                    $a = $cacheAlimentos[$i['item_id']];
                    $g = 0.0;
                    if (!empty($i['cantidad_gramos'])) {
                        $g = floatval($i['cantidad_gramos']);
                    } elseif (!empty($i['porcion_id']) && isset($cachePorciones[$i['porcion_id']])) {
                        $ge = floatval($cachePorciones[$i['porcion_id']]['gramos_equivalentes'] ?? 0);
                        $n  = floatval($i['porciones'] ?? 1);
                        $g  = max(0, $ge * $n);
                    }
                    if ($g > 0) {
                        $factor = $g / 100.0;
                        $i['macros']['kcal']            = round(floatval($a['kcal'] ?? 0) * $factor, 1);
                        $i['macros']['proteina_g']      = round(floatval($a['proteina_g'] ?? 0) * $factor, 1);
                        $i['macros']['carbohidratos_g'] = round(floatval($a['carbohidratos_g'] ?? 0) * $factor, 1);
                        $i['macros']['grasas_g']        = round(floatval($a['grasas_g'] ?? 0) * $factor, 1);

                        $res['kcal']            += $i['macros']['kcal'];
                        $res['proteina_g']      += $i['macros']['proteina_g'];
                        $res['carbohidratos_g'] += $i['macros']['carbohidratos_g'];
                        $res['grasas_g']        += $i['macros']['grasas_g'];
                        $res['fibra_g']         += round(floatval($a['fibra_g'] ?? 0) * $factor, 1);
                        $res['sodio_mg']        += round(floatval($a['sodio_mg'] ?? 0) * $factor, 1);
                        $res['azucares_g']      += round(floatval($a['azucares_g'] ?? 0) * $factor, 1);
                    }
                }
            }

            foreach ($res as $k => $v) {
                $res[$k] = round($v, 1);
            }
            $resumenPorTipo[$t] = $res;
            $ingPorTipo[$t]     = $ing;

            foreach ($totalesDia as $k => $_) {
                $totalesDia[$k] += $res[$k];
            }
        }
        foreach ($totalesDia as $k => $v) {
            $totalesDia[$k] = round($v, 1);
        }

        return view('comidas/diario/hoy', [
            'title'         => 'Diario · ' . $fecha,
            'fechaSel'      => new \DateTime($fecha),
            'tipo'          => $tipo,
            'q'             => $q,
            'item_tipo'     => $itemTipo,
            'alimento_id'   => $alimentoId,
            'resultados'    => $resultados,
            'porciones'     => $porciones,
            'ingestas'      => $ingPorTipo[$tipo] ?? [],
            'resumen'       => $totalesDia,
            'resumenTipos'  => $resumenPorTipo,
            'ingPorTipo'    => $ingPorTipo,
            'tiposLista'    => $tipos,
            'objetivos'     => $objetivos,
        ]);
    }

    public function add()
    {
        $userId = $this->currentUserId();
        $diasM  = new ComidasDiasModel();
        $ingM   = new ComidasIngestasModel();

        // fecha enviada o hoy por defecto
        $fecha = $this->request->getPost('fecha') ?? date('Y-m-d');

        $dia = $diasM->where(['user_id' => $userId, 'fecha' => $fecha])->first();
        if (!$dia) {
            $diasM->insert(['user_id' => $userId, 'fecha' => $fecha]);
            $dia = $diasM->find($diasM->getInsertID());
        }

        $tipo      = $this->request->getPost('tipo') ?? 'almuerzo';
        $itemTipo  = $this->request->getPost('item_tipo') === 'receta' ? 'receta' : 'alimento';
        $itemId    = (int) $this->request->getPost('item_id');
        $g         = trim((string) $this->request->getPost('cantidad_gramos'));
        $porcionId = trim((string) $this->request->getPost('porcion_id'));
        $porciones = trim((string) $this->request->getPost('porciones'));

        $errors = [];
        if ($itemId <= 0) {
            $errors[] = 'Debes seleccionar un elemento.';
        }
        $tieneGramos  = ($g !== '' && floatval($g) > 0);
        $tienePorcion = ($porcionId !== '' && floatval($porciones) > 0);
        if (!$tieneGramos && !$tienePorcion) {
            $errors[] = 'Indica cantidad en gramos o una porción válida.';
        }
        if ($tienePorcion && !ctype_digit($porcionId)) {
            $errors[] = 'Porción no válida.';
        }
        if (!empty($errors)) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $data = [
            'dia_id'          => $dia['id'],
            'tipo'            => $tipo,
            'item_tipo'       => $itemTipo,
            'item_id'         => $itemId,
            'cantidad_gramos' => $tieneGramos ? (int) $g : null,
            'porcion_id'      => $tienePorcion ? (int) $porcionId : null,
            'porciones'       => $tienePorcion ? floatval($porciones) : null,
            'hora'            => date('H:i:s'),
        ];

        $ingM->insert($data);

        // ✅ ahora sí, redirige RESTful a fecha + tipo
        return redirect()->to(site_url('comidas/diario/' . $fecha . '/' . $tipo));
    }


    public function porciones($alimentoId)
    {
        $porcionesM = new ComidasAlimentoUnidadesModel();
        $rows = $porcionesM->where('alimento_id', (int)$alimentoId)->orderBy('id', 'ASC')->findAll();
        return $this->response->setJSON($rows);
    }
}
