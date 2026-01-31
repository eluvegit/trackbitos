<?php

namespace App\Controllers\Comidas;

use App\Controllers\BaseController;
use App\Models\ComidasAlimentosModel;
use App\Models\ComidasAlimentosControlModel;
use App\Models\ComidasIngestasModel;

class AlimentosControl extends BaseController
{
    protected $alimentosModel;
    protected $controlModel;
    protected $comidasModel;

    public function __construct()
    {
        $this->alimentosModel = new ComidasAlimentosModel();
        $this->controlModel   = new ComidasAlimentosControlModel();
        $this->comidasModel   = new ComidasIngestasModel();
    }

    public function index()
    {
        $alimentos_todos = $this->alimentosModel
            ->orderBy('nombre', 'ASC')
            ->findAll();

        $controlados = $this->controlModel->findAll();
        $hoy = new \DateTime();

        $alimentos_controlados = [];
        $db = \Config\Database::connect();

        // --- ALIMENTOS CONTROLADOS ---
foreach ($controlados as $c) {
    $alimento_id = $c['alimento_id'];

    // 1️⃣ Última ingesta directa
    $ultima_directa = $db->table('comidas_ingestas ci')
        ->select('cd.fecha')
        ->join('comidas_dias cd', 'cd.id = ci.dia_id')
        ->where('ci.item_tipo', 'alimento')
        ->where('ci.item_id', $alimento_id)
        ->orderBy('cd.fecha', 'DESC')
        ->limit(1)
        ->get()
        ->getRowArray();

    $ultima_directa_fecha = $ultima_directa['fecha'] ?? null;

    // 2️⃣ Recetas que contienen este alimento
    $recetas = $db->table('comidas_receta_ingredientes cri')
        ->select('cri.receta_id')
        ->where('cri.alimento_id', $alimento_id)
        ->get()
        ->getResultArray();

    $ultima_receta_fecha = null;
    $veces_receta = 0;

    if (!empty($recetas)) {
        $receta_ids = array_column($recetas, 'receta_id');

        // Buscar los IDs de esas recetas como alimentos
        $alimentos_recetas = $db->table('comidas_alimentos')
            ->select('id')
            ->whereIn('receta_id', $receta_ids)
            ->where('es_receta', 1)
            ->get()
            ->getResultArray();

        $alimento_receta_ids = array_column($alimentos_recetas, 'id');

        if (!empty($alimento_receta_ids)) {
            // Última ingesta de cualquier receta que contenga el alimento
            $ultima_receta = $db->table('comidas_ingestas ci')
                ->select('cd.fecha')
                ->join('comidas_dias cd', 'cd.id = ci.dia_id')
                ->where('ci.item_tipo', 'alimento') // porque la receta se registra como alimento
                ->whereIn('ci.item_id', $alimento_receta_ids)
                ->orderBy('cd.fecha', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();

            $ultima_receta_fecha = $ultima_receta['fecha'] ?? null;

            // Veces que se ha consumido en el periodo
            $fecha_inicio = (clone $hoy)->modify('-' . $c['periodo_dias'] . ' days')->format('Y-m-d');
            $veces_receta = $db->table('comidas_ingestas ci')
                ->join('comidas_dias cd', 'cd.id = ci.dia_id')
                ->where('ci.item_tipo', 'alimento')
                ->whereIn('ci.item_id', $alimento_receta_ids)
                ->where('cd.fecha >=', $fecha_inicio)
                ->countAllResults();
        }
    }

    // 3️⃣ Comparar ingestas directas vs recetas
    $ultima_fecha = $ultima_directa_fecha;
    if ($ultima_receta_fecha && (!$ultima_directa_fecha || $ultima_receta_fecha > $ultima_directa_fecha)) {
        $ultima_fecha = $ultima_receta_fecha;
    }

    $dias_desde_ultima = $ultima_fecha ? $hoy->diff(new \DateTime($ultima_fecha))->days : null;

    // 4️⃣ Veces totales en periodo
    $fecha_inicio = (clone $hoy)->modify('-' . $c['periodo_dias'] . ' days')->format('Y-m-d');
    $veces_directas = $db->table('comidas_ingestas ci')
        ->join('comidas_dias cd', 'cd.id = ci.dia_id')
        ->where('ci.item_tipo', 'alimento')
        ->where('ci.item_id', $alimento_id)
        ->where('cd.fecha >=', $fecha_inicio)
        ->countAllResults();

    $veces_en_periodo = $veces_directas + $veces_receta;

    // 5️⃣ Estado
    $estado = ($veces_en_periodo >= $c['min_veces'] && $veces_en_periodo <= $c['max_veces']) ? 'verde' : 'rojo';

    $alimentos_controlados[$alimento_id] = [
        'id' => $c['id'],
        'dias_desde_ultima' => $dias_desde_ultima,
        'veces_en_periodo' => $veces_en_periodo,
        'periodo_dias' => $c['periodo_dias'],
        'min_veces' => $c['min_veces'],
        'max_veces' => $c['max_veces'],
        'estado' => $estado,
    ];
}


        // --- INGREDIENTES NO CONTROLADOS ---
        $ingredientes_no_controlados = [];

        foreach ($alimentos_todos as $alimento) {
            // Saltar los que ya están controlados
            if (isset($alimentos_controlados[$alimento['id']])) continue;

            $alimento_id = $alimento['id'];

            // 1️⃣ Última ingesta directa del alimento
            $ultima_directa = $db->table('comidas_ingestas ci')
                ->select('cd.fecha')
                ->join('comidas_dias cd', 'cd.id = ci.dia_id')
                ->where('ci.item_tipo', 'alimento')
                ->where('ci.item_id', $alimento_id)
                ->orderBy('cd.fecha', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();

            $ultima_directa_fecha = $ultima_directa['fecha'] ?? null;

            // 2️⃣ Recetas que contienen este ingrediente
            $recetas = $db->table('comidas_receta_ingredientes cri')
                ->select('cri.receta_id')
                ->where('cri.alimento_id', $alimento_id)
                ->get()
                ->getResultArray();

            $ultima_receta_fecha = null;

            if (!empty($recetas)) {
                // Obtener los IDs de las recetas
                $receta_ids = array_column($recetas, 'receta_id');

                // Buscar los IDs de las recetas como alimentos
                $alimentos_recetas = $db->table('comidas_alimentos')
                    ->select('id')
                    ->whereIn('receta_id', $receta_ids)
                    ->where('es_receta', 1)
                    ->get()
                    ->getResultArray();

                $alimento_receta_ids = array_column($alimentos_recetas, 'id');

                if (!empty($alimento_receta_ids)) {
                    // Última ingesta de cualquier receta que contenga el ingrediente
                    $ultima_receta = $db->table('comidas_ingestas ci')
                        ->select('cd.fecha')
                        ->join('comidas_dias cd', 'cd.id = ci.dia_id')
                        ->where('ci.item_tipo', 'alimento') // porque la receta se registra como alimento
                        ->whereIn('ci.item_id', $alimento_receta_ids)
                        ->orderBy('cd.fecha', 'DESC')
                        ->limit(1)
                        ->get()
                        ->getRowArray();

                    $ultima_receta_fecha = $ultima_receta['fecha'] ?? null;
                }
            }

            // 3️⃣ Comparar ingesta directa vs receta y quedarnos con la más reciente
            $ultima_fecha = $ultima_directa_fecha;

            if ($ultima_receta_fecha && (!$ultima_directa_fecha || $ultima_receta_fecha > $ultima_directa_fecha)) {
                $ultima_fecha = $ultima_receta_fecha;
            }

            // 4️⃣ Calcular días desde la última ingesta
            $dias_desde_ultima = $ultima_fecha ? $hoy->diff(new \DateTime($ultima_fecha))->days : null;

            // 5️⃣ Añadir al array de ingredientes no controlados
            $ingredientes_no_controlados[] = [
                'id' => $alimento_id,
                'nombre' => $alimento['nombre'],
                'dias_desde' => $dias_desde_ultima,
            ];
        }

        // 6️⃣ Ordenar alfabéticamente por nombre
        usort($ingredientes_no_controlados, fn($a, $b) => strcmp($a['nombre'], $b['nombre']));


        return view('comidas/alimentos_control/index', [
            'alimentos_todos' => $alimentos_todos,
            'alimentos_controlados' => $alimentos_controlados,
            'ingredientes_no_controlados' => $ingredientes_no_controlados,
        ]);
    }


    public function add()
    {
        // Solo aceptar POST
        if ($this->request->getMethod() !== 'POST') {
            return redirect()->to(site_url('comidas/alimentos-control'));
        }

        $post = $this->request->getPost();

        // Validar campos obligatorios correctamente
        if (!isset($post['alimento_id'], $post['periodo_dias'], $post['min_veces'], $post['max_veces'])) {
            return redirect()->to(site_url('comidas/alimentos-control'))
                ->with('error', 'Todos los campos son obligatorios.');
        }

        // Convertir a enteros
        $alimento_id  = (int)$post['alimento_id'];
        $periodo_dias = max(1, (int)$post['periodo_dias']);
        $min_veces    = max(0, (int)$post['min_veces']);
        $max_veces    = max(1, (int)$post['max_veces']);
        $unidad       = $post['unidad'] ?? 'veces';

        try {
            $this->controlModel->insert([
                'alimento_id'  => $alimento_id,
                'periodo_dias' => $periodo_dias,
                'min_veces'    => $min_veces,
                'max_veces'    => $max_veces,
                'unidad'       => $unidad,
            ]);
        } catch (\Exception $e) {
            // Si hay algún error de DB
            return redirect()->to(site_url('comidas/alimentos-control'))
                ->with('error', 'No se pudo guardar el control: ' . $e->getMessage());
        }

        return redirect()->to(site_url('comidas/alimentos-control'))
            ->with('success', 'Control añadido correctamente.');
    }








    public function edit($id)
    {
        $control = $this->controlModel->find($id);
        if (!$control) return redirect()->to(site_url('comidas/alimentos-control'));

        if ($this->request->getMethod() === 'post') {
            $post = $this->request->getPost();
            $this->controlModel->update($id, [
                'periodo_dias' => $post['periodo_dias'],
                'min_veces'    => $post['min_veces'],
                'max_veces'    => $post['max_veces'],
                'unidad'       => $post['unidad'] ?? 'veces'
            ]);
            return redirect()->to(site_url('comidas/alimentos-control'))->with('success', 'Control actualizado.');
        }

        $alimento = $this->alimentosModel->find($control['alimento_id']);
        return view('comidas/alimentos_control/edit', [
            'control'  => $control,
            'alimento' => $alimento
        ]);
    }
    public function delete($id)
    {
        $this->controlModel->delete($id);
        return redirect()->to(site_url('comidas/alimentos-control'))->with('success', 'Control eliminado.');
    }
}
