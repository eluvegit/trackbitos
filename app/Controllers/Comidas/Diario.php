<?php

namespace App\Controllers\Comidas;

use App\Controllers\BaseController;
use App\Models\ComidasDiasModel;
use App\Models\ComidasIngestasModel;
use App\Models\ComidasAlimentosModel;
use App\Models\ComidasRecetasModel;
use App\Models\ComidasAlimentoUnidadesModel;
use App\Models\ComidasNutrientesModel;

class Diario extends BaseController
{
    // =================== Modelos ======================
    protected $diasM;
    protected $ingM;
    protected $alimentosM;

    // cache campos de comidas_alimentos
    private array $alimentoFields = [];

    public function __construct()
    {
        $this->diasM      = new ComidasDiasModel();
        $this->ingM       = new ComidasIngestasModel();
        $this->alimentosM = new ComidasAlimentosModel();
    }

    // =================== JSON ======================

    public function buscarAlimentos()
    {
        $q = (string) $this->request->getGet('q');
        $rows = $this->alimentosM
            ->like('nombre', $q)
            ->select('id,nombre')
            ->orderBy('nombre', 'asc')
            ->findAll(20);

        return $this->response->setJSON($rows);
    }

    public function ingestasAjax($fecha, $tipo)
    {
        $dia = $this->diasM->where('fecha', $fecha)->first();
        if (!$dia) return $this->response->setJSON([]);

        $rows = $this->ingM
            ->select('comidas_ingestas.id, comidas_alimentos.nombre, comidas_ingestas.cantidad_gramos,
                      comidas_alimentos.kcal, comidas_alimentos.proteina_g,
                      comidas_alimentos.carbohidratos_g, comidas_alimentos.grasas_g')
            ->join(
                'comidas_alimentos',
                'comidas_alimentos.id = comidas_ingestas.item_id AND comidas_ingestas.item_tipo = "alimento"',
                'left'
            )
            ->where([
                'dia_id' => $dia['id'],
                'tipo'   => $this->normalizeTipo($tipo)
            ])
            ->orderBy('hora', 'DESC')
            ->findAll();

        return $this->response->setJSON($rows);
    }

    public function addAjax()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Método no permitido']);
        }

        $debug = ['step' => [], 'input_raw' => [], 'normalized' => [], 'decision' => []];

        $fecha  = (string) $this->request->getPost('fecha');
        $tipo   = $this->normalizeTipo((string) $this->request->getPost('tipo'));
        $itemId = (int) $this->request->getPost('item_id');

        $debug['input_raw'] = [
            'fecha'           => $fecha,
            'tipo'            => $tipo,
            'item_id'         => $this->request->getPost('item_id'),
            'cantidad_gramos' => $this->request->getPost('cantidad_gramos'),
            'porcion_id'      => $this->request->getPost('porcion_id'),
            'porciones'       => $this->request->getPost('porciones'),
        ];

        // Buscar/crear día SOLO por fecha
        $dia = $this->diasM->where('fecha', $fecha)->first();
        if (!$dia) {
            $diaId = $this->diasM->insert(['fecha' => $fecha]);
            $dia   = $this->diasM->find($diaId);
            $debug['step'][] = 'dia_creado';
        } else {
            $debug['step'][] = 'dia_existente';
        }
        $debug['dia'] = ['id' => $dia['id'] ?? null, 'fecha' => $fecha];

        // Parse numéricos (admite coma)
        $toFloat = static function ($v): float {
            if ($v === null) return 0.0;
            if (is_string($v)) $v = str_replace(',', '.', $v);
            return (float) $v;
        };

        $porcionId      = (int) ($this->request->getPost('porcion_id') ?: 0); // '' -> 0
        $numPorciones   = $toFloat($this->request->getPost('porciones') ?: 0);
        $cantidadGramos = $toFloat($this->request->getPost('cantidad_gramos') ?: 0);

        $debug['normalized'] = [
            'porcion_id'      => $porcionId,
            'porciones'       => $numPorciones,
            'cantidad_gramos' => $cantidadGramos,
            'item_id'         => $itemId,
            'tipo'            => $tipo,
            'fecha'           => $fecha,
        ];

        $cantidadFinal = null;
        $dataExtra     = ['porcion_id' => null, 'porciones' => null];

        // Prioridad: porción válida
        if ($porcionId > 0) {
            $unidad = (new ComidasAlimentoUnidadesModel())->find($porcionId);
            $eq     = $toFloat($unidad['gramos_equivalentes'] ?? 0);
            $n      = $numPorciones > 0 ? $numPorciones : 1;

            $debug['decision']['modo'] = 'porciones';
            $debug['decision']['unidad'] = [
                'porcion_id'          => $porcionId,
                'descripcion'         => $unidad['descripcion'] ?? null,
                'gramos_equivalentes' => $eq,
                'n_porciones'         => $n,
            ];

            if ($eq > 0) $cantidadFinal = $eq * $n;
            $dataExtra['porcion_id'] = $porcionId;
            $dataExtra['porciones']  = $n;
        }
        // Si no hay porción, usar gramos escritos
        elseif ($cantidadGramos > 0) {
            $cantidadFinal = $cantidadGramos;
            $debug['decision']['modo'] = 'gramos';
        } else {
            $debug['decision']['modo'] = 'ninguno';
        }

        $debug['decision']['cantidad_final'] = $cantidadFinal;

        if (!$cantidadFinal || $itemId <= 0 || !$tipo || !$fecha) {
            $debug['error'] = 'Cantidad o datos no válidos';
            return $this->response->setJSON(['ok' => false, 'error' => 'Cantidad o datos no válidos', 'debug' => $debug]);
        }

        $insertData = [
            'dia_id'          => $dia['id'],
            'tipo'            => $tipo,
            'item_tipo'       => 'alimento',
            'item_id'         => $itemId,
            'cantidad_gramos' => $cantidadFinal,
            'porcion_id'      => $dataExtra['porcion_id'],
            'porciones'       => $dataExtra['porciones'],
            'hora'            => date('H:i:s'),
        ];

        $id = $this->ingM->insert($insertData);
        $debug['insert'] = ['id' => $id, 'data' => $insertData];

        return $this->response->setJSON(['ok' => true, 'id' => $id, 'debug' => $debug]);
    }

    public function deleteAjax($id)
    {
        $this->ingM->delete($id);
        return $this->response->setJSON(['ok' => true]);
    }

    // =================== Vistas ======================

    public function hoy()
    {
        return redirect()->to(site_url('comidas/diario/' . date('Y-m-d')));
    }

    public function ver($fecha = null)
    {
        $fecha = $fecha ?: date('Y-m-d');

        // validar formato
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$d || $d->format('Y-m-d') !== $fecha) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = $this->buildDia($fecha, null);
        return view('comidas/diario/resumen', $data);
    }

    public function verTipo($fecha, $tipo)
    {
        $data = $this->buildDia($fecha, $this->normalizeTipo($tipo));
        return view('comidas/diario/entrada', $data);
    }

    public function seleccionarTipo($fecha)
    {
        $data = $this->buildDia($fecha, null);

        // tipos fijos de comidas
        $data['tipos'] = ['almuerzo', 'cena', 'nocturna', 'desayuno', 'merienda'];

        // vista con tarjetas de selección
        return view('comidas/diario/seleccionar_tipo', $data);
    }

    // Vista completa de nutrientes del día
    public function nutrientes($fecha)
    {
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$d || $d->format('Y-m-d') !== $fecha) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $nutrs = $this->getActiveNutrients();              // lista desde comidas_nutrientes
        $fields = $this->getAlimentoFields();              // columnas reales de comidas_alimentos

        // SELECT dinámico SUM(ci.cantidad_gramos * ca.col / 100) AS col
        $selects = [];
        foreach ($nutrs as $n) {
            $c = $n['clave'];
            if (!in_array($c, $fields, true)) continue;    // salta si la columna no existe
            $selects[] = "SUM(ci.cantidad_gramos * ca.`$c` / 100) AS `$c`";
        }
        if (empty($selects)) $selects[] = "0 AS dummy";
        $selectSql = implode(",\n", $selects);

        $db  = \Config\Database::connect();
        $row = $db->table('comidas_ingestas ci')
            ->select($selectSql, false)
            ->join('comidas_dias cd', 'cd.id = ci.dia_id', 'inner')
            ->join('comidas_alimentos ca', "ca.id = ci.item_id AND ci.item_tipo = 'alimento'", 'left')
            ->where('cd.fecha', $fecha)
            ->get()->getRowArray() ?? [];

        helper('comidas');                 // usa comidas_limites() sin user_id
        $limites = comidas_limites();

        $items = [];
        foreach ($nutrs as $n) {
            $clave   = $n['clave'];
            $unidad  = $n['unidad'];
            $valor   = isset($row[$clave]) ? (float)$row[$clave] : 0.0;

            $min = isset($limites[$clave]['falta']['umbral'])  ? (float)$limites[$clave]['falta']['umbral']  : null;
            $max = isset($limites[$clave]['exceso']['umbral']) ? (float)$limites[$clave]['exceso']['umbral'] : null;
            if ($min !== null && $max !== null && $min > $max) { [$min,$max] = [$max,$min]; }

            $ref   = $max ?: $min ?: 0;
            $pct   = $ref > 0 ? max(0, min(100, $valor / $ref * 100)) : 0;
            $state = 'ok';
            if ($min !== null && $valor < $min) $state = 'low';
            if ($max !== null && $valor > $max) $state = 'high';
            $cls   = $state === 'ok' ? 'bg-success' : ($state === 'high' ? 'bg-danger' : 'bg-warning');

            $items[] = [
                'id'        => $n['id'],
                'nombre'    => $n['nombre'],
                'clave'     => $clave,
                'unidad'    => $unidad,
                'categoria' => $n['categoria'],   // macro|micro
                'valor'     => $valor,
                'min'       => $min,
                'max'       => $max,
                'pct'       => $pct,
                'cls'       => $cls,
            ];
        }

        return view('comidas/diario/nutrientes', [
            'title'    => 'Nutrientes · ' . $fecha,
            'fechaSel' => new \DateTime($fecha),
            'items'    => $items,
        ]);
    }

    // =================== Core ======================

    private function buildDia(string $fecha, ?string $tipo): array
    {
        // Nutrientes activos y columnas reales
        $nutrs  = $this->getActiveNutrients();
        $fields = $this->getAlimentoFields();
        $claves = array_values(array_filter(
            array_map(fn($n) => $n['clave'], $nutrs),
            fn($c) => in_array($c, $fields, true)   // sólo los que existen como columna
        ));

        // Crear día si no existe (solo por fecha)
        $dia = $this->diasM->where('fecha', $fecha)->first();
        if (!$dia) {
            $this->diasM->insert(['fecha' => $fecha]);
            $dia = $this->diasM->find($this->diasM->getInsertID());
        }

        // Buscador
        $q          = trim((string) $this->request->getGet('q'));
        $itemTipo   = $this->request->getGet('item_tipo') === 'receta' ? 'receta' : 'alimento';
        $alimentoId = (int) $this->request->getGet('alimento_id');

        $alimentosM = new ComidasAlimentosModel();
        $recetasM   = new ComidasRecetasModel();
        $porcionesM = new ComidasAlimentoUnidadesModel();
        $ingestasM  = new ComidasIngestasModel();

        $builder = $itemTipo === 'receta'
            ? $recetasM->select('id,nombre')
            : $alimentosM->select('id,nombre');
        if ($q !== '') $builder->like('nombre', $q);
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
        $tipos = ['almuerzo', 'merienda', 'cena', 'nocturna', 'desayuno'];

        // Totales día (todas las claves)
        $totalesDia = [];
        foreach ($claves as $c) $totalesDia[$c] = 0.0;

        $ingPorTipo     = [];
        $resumenPorTipo = [];
        $cacheAlimentos = [];
        $cachePorciones = [];

        foreach ($tipos as $t) {
            $ing = $ingestasM->where(['dia_id' => $dia['id'], 'tipo' => $t])
                             ->orderBy('hora', 'DESC')->findAll();

            // cache de porciones usadas
            $idsPorcionUsadas = array_values(array_unique(array_filter(array_map(fn($x) => $x['porcion_id'] ?? null, $ing))));
            if (!empty($idsPorcionUsadas)) {
                $porRows = $porcionesM->whereIn('id', $idsPorcionUsadas)->findAll();
                foreach ($porRows as $pr) $cachePorciones[$pr['id']] = $pr;
            }

            $res = [];
            foreach ($claves as $c) $res[$c] = 0.0;

            foreach ($ing as &$i) {
                // nombre
                if ($i['item_tipo'] === 'alimento') {
                    if (!isset($cacheAlimentos[$i['item_id']])) {
                        $cacheAlimentos[$i['item_id']] = $alimentosM->find($i['item_id']);
                    }
                    $a = $cacheAlimentos[$i['item_id']] ?? [];
                    $i['nombre'] = $a['nombre'] ?? 'Alimento #' . $i['item_id'];
                } else {
                    $r = $recetasM->find($i['item_id']);
                    $i['nombre'] = $r['nombre'] ?? 'Receta #' . $i['item_id'];
                    $a = []; // por si acaso
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
                    if ($gEq && !$descIncluyeGramos) $label .= ' (' . $gFmt($gEq) . ' g)';
                    if ($n != 1.0)                   $label .= ' (x' . $nFmt . ')';
                    if ($gEq && $n != 1.0)           $label .= ' ≈ ' . $gFmt($n * $gEq) . ' g';
                    $i['cantidad_label'] = $label;
                } else {
                    $i['cantidad_label'] = '-';
                }

                // macros para chip (si existen)
                $i['macros'] = ['kcal' => 0, 'proteina_g' => 0, 'carbohidratos_g' => 0, 'grasas_g' => 0];

                // suma dinámica
                $g = 0.0;
                if (!empty($i['cantidad_gramos'])) {
                    $g = (float) $i['cantidad_gramos'];
                } elseif (!empty($i['porcion_id']) && isset($cachePorciones[$i['porcion_id']])) {
                    $ge = (float) ($cachePorciones[$i['porcion_id']]['gramos_equivalentes'] ?? 0);
                    $n  = (float) ($i['porciones'] ?? 1);
                    $g  = max(0, $ge * $n);
                }

                if ($g > 0 && !empty($a)) {
                    $factor = $g / 100.0;
                    foreach ($claves as $c) {
                        // sólo si la columna existe
                        if (!array_key_exists($c, $a)) continue;
                        $valorBase = (float) ($a[$c] ?? 0);
                        $val       = $valorBase * $factor;
                        $res[$c]  += $val;

                        // chip macros
                        if (isset($i['macros'][$c])) {
                            $i['macros'][$c] = round($val, 1);
                        }
                    }
                }
            }

            // redondeo por unidad
            foreach ($nutrs as $n) {
                $c = $n['clave'];
                if (!array_key_exists($c, $res)) continue;
                $res[$c] = round($res[$c], $this->decimalsForUnit($n['unidad']));
            }

            $resumenPorTipo[$t] = $res;
            $ingPorTipo[$t]     = $ing;

            // acumula día
            foreach ($claves as $c) $totalesDia[$c] += ($res[$c] ?? 0);
        }

        // redondeo final día
        foreach ($nutrs as $n) {
            $c = $n['clave'];
            if (!array_key_exists($c, $totalesDia)) continue;
            $totalesDia[$c] = round($totalesDia[$c], $this->decimalsForUnit($n['unidad']));
        }

        // objetivos “placeholder” (si los usas en la vista antigua)
        $objetivos = [
            'kcal'            => 2000,
            'carbohidratos_g' => 250,
            'grasas_g'        => 70,
            'proteina_g'      => 150
        ];

        return [
            'title'        => 'Diario · ' . $fecha,
            'fechaSel'     => new \DateTime($fecha),
            'tipo'         => $tipo,
            'q'            => $q,
            'item_tipo'    => $itemTipo,
            'alimento_id'  => $alimentoId,
            'resultados'   => $resultados,
            'porciones'    => $porciones,
            'ingestas'     => $tipo ? ($ingPorTipo[$tipo] ?? []) : [],
            'resumen'      => $totalesDia,
            'resumenTipos' => $resumenPorTipo,
            'ingPorTipo'   => $ingPorTipo,
            'tiposLista'   => $tipos,
            'objetivos'    => $objetivos,
            'nutrientes'   => $nutrs, // útil si en la vista quieres iterar dinámico
        ];
    }

    // =================== Util ======================

    private function normalizeTipo(string $tipo): string
    {
        $t = strtolower(trim($tipo));
        if ($t === 'comida_nocturna') $t = 'nocturna';
        $valid = ['desayuno', 'almuerzo', 'merienda', 'cena', 'nocturna'];
        return in_array($t, $valid, true) ? $t : 'almuerzo';
    }

    private function getActiveNutrients(): array
    {
        // Todos los nutrientes activos, ordenados (macros primero)
        $nutM = new ComidasNutrientesModel();
        return $nutM->where('activo', 1)
            ->orderBy('es_macro', 'DESC')
            ->orderBy('orden', 'ASC')
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }

    private function getAlimentoFields(): array
    {
        if (empty($this->alimentoFields)) {
            $db = \Config\Database::connect();
            $this->alimentoFields = $db->getFieldNames('comidas_alimentos') ?: [];
        }
        return $this->alimentoFields;
    }

    private function decimalsForUnit(string $unidad): int
    {
        $u = strtolower(trim($unidad));
        if ($u === 'mg' || $u === 'µg' || $u === 'ug') return 0;
        if ($u === 'kcal') return 0;
        return 1; // g y otros casos
    }

    public function porciones($alimentoId)
    {
        $unidadesM = new \App\Models\ComidasAlimentoUnidadesModel();

        $rows = $unidadesM
            ->where('alimento_id', $alimentoId)
            ->select('id, descripcion, gramos_equivalentes, es_predeterminada')
            ->orderBy('descripcion', 'asc')
            ->findAll();

        return $this->response->setJSON($rows);
    }
}
