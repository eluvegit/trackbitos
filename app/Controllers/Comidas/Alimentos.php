<?php

namespace App\Controllers\Comidas;

use App\Controllers\BaseController;
use App\Models\ComidasAlimentosModel;

class Alimentos extends BaseController
{
    /** Campos numéricos (se normalizan coma→punto y float) */
    private array $numericFields = [
        'densidad_g_ml',
        'kcal',
        'proteina_g',
        'carbohidratos_g',
        'azucares_g',
        'fibra_g',
        'grasas_g',
        'grasas_saturadas_g',
        'sodio_mg',
        'omega3_mg',
        'omega6_mg',
        'calcio_mg',
        'hierro_mg',
        'magnesio_mg',
        'fosforo_mg',
        'potasio_mg',
        'zinc_mg',
        'selenio_ug',
        'cobre_mg',
        'manganeso_mg',
        'yodo_ug',
        'vitamina_a_rae_ug',
        'vitamina_c_mg',
        'vitamina_d_ug',
        'vitamina_e_mg',
        'vitamina_k_ug',
    ];

    /** Defaults para la vista (evita undefined index) */
    private function defaults(): array
    {
        return [
            'id' => null,
            'nombre' => '',
            'marca' => '',
            'descripcion' => '',
            'es_receta' => 0,
            'receta_id' => null,
            'densidad_g_ml' => null,
            'es_liquido' => 0,
            'kcal' => 0,
            'proteina_g' => 0,
            'carbohidratos_g' => 0,
            'azucares_g' => 0,
            'fibra_g' => 0,
            'grasas_g' => 0,
            'grasas_saturadas_g' => 0,
            'sodio_mg' => 0,
            'omega3_mg' => 0,
            'omega6_mg' => 0,
            'calcio_mg' => 0,
            'hierro_mg' => 0,
            'magnesio_mg' => 0,
            'fosforo_mg' => 0,
            'potasio_mg' => 0,
            'zinc_mg' => 0,
            'selenio_ug' => 0,
            'cobre_mg' => 0,
            'manganeso_mg' => 0,
            'yodo_ug' => 0,
            'vitamina_a_rae_ug' => 0,
            'vitamina_c_mg' => 0,
            'vitamina_d_ug' => 0,
            'vitamina_e_mg' => 0,
            'vitamina_k_ug' => 0,
        ];
    }

    /** Fusiona pegado masivo (bulk) si viene en el POST */
    private function mergeBulk(array $data): array
    {
        if (!empty($data['bulk'])) {
            helper('comidas_parse'); // asegura el helper
            $parsed = comidas_parse_bulk((string)$data['bulk']);
            unset($data['bulk']);
            $data = array_merge($data, $parsed);
        }
        return $data;
    }

    /** Normaliza booleans, receta_id y decimales */
    private function normalizePayload(array $in): array
    {
        $data = $in;

        // Booleans
        $data['es_liquido'] = isset($in['es_liquido']) ? 1 : 0;
        $data['es_receta']  = isset($in['es_receta'])  ? 1 : 0;

        // receta_id: NULL si no es receta o si viene vacío/0 (evita UNIQUE (es_receta, receta_id) con 0)
        $recetaId = isset($in['receta_id']) ? (int)$in['receta_id'] : null;
        $data['receta_id'] = ($data['es_receta'] === 1 && $recetaId > 0) ? $recetaId : null;

        // Decimales (admite coma)
        foreach ($this->numericFields as $f) {
            if (!array_key_exists($f, $data)) continue;
            $v = $data[$f];
            if ($v === '' || $v === null) {
                $data[$f] = 0;
                continue;
            }
            if (is_string($v)) $v = str_replace(',', '.', $v);
            $data[$f] = (float)$v;
        }

        return $data;
    }

    // =================== Listado ===================

    public function index()
    {
        $q = trim((string) $this->request->getGet('q'));
        $m = new ComidasAlimentosModel();

        if ($q !== '') $m->like('nombre', $q);

        $rows  = $m->orderBy('nombre', 'ASC')->paginate(100);
        $pager = $m->pager;

        return view('comidas/alimentos/index', [
            'rows'  => $rows,
            'q'     => $q,
            'title' => 'Alimentos',
            'pager' => $pager,
        ]);
    }

    // =================== Crear ===================

    public function create()
    {
        helper('form'); // para form_open()

        return view('comidas/alimentos/form', [
            'title'  => 'Nuevo alimento',
            'row'    => $this->defaults(),
            'action' => site_url('comidas/alimentos/store'),
        ]);
    }

    public function store()
    {
        $m    = new ComidasAlimentosModel();
        $data = $this->mergeBulk($this->request->getPost());
        $data = $this->normalizePayload($data);
        $data = $this->filterByApplyFields($data);


        if (empty(trim($data['nombre'] ?? ''))) {
            return redirect()->back()->withInput()
                ->with('errors', ['El nombre es obligatorio.']);
        }

        if (!$m->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $m->errors());
        }

        return redirect()->to(site_url('comidas/alimentos'))->with('ok', 'Alimento creado');
    }

    // =================== Editar ===================

    public function edit($id)
    {
        helper('form'); // para form_open()

        $m   = new ComidasAlimentosModel();
        $row = $m->find($id);
        if (!$row) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Alimento no encontrado');
        }

        return view('comidas/alimentos/form', [
            'title'  => 'Editar alimento',
            'row'    => $row,
            'action' => site_url('comidas/alimentos/update/' . $id),
        ]);
    }

    public function update($id)
    {
        $m = new ComidasAlimentosModel();
        if (!$m->find($id)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Alimento no encontrado');
        }

        $data = $this->mergeBulk($this->request->getPost());
        $data = $this->normalizePayload($data);

        if (empty(trim($data['nombre'] ?? ''))) {
            return redirect()->back()->withInput()
                ->with('errors', ['El nombre es obligatorio.']);
        }

        if (!$m->update($id, $data)) {
            return redirect()->back()->withInput()->with('errors', $m->errors());
        }

        return redirect()->to(site_url('comidas/alimentos'))->with('ok', 'Alimento actualizado');
    }

    // =================== Eliminar ===================

    public function delete($id)
    {
        $m = new ComidasAlimentosModel();
        $m->delete($id);
        return redirect()->to(site_url('comidas/alimentos'))->with('ok', 'Alimento eliminado');
    }

    // Añade en la clase (propiedades privadas nuevas)
    private array $fieldLabels = [
        'kcal' => 'Calorías',
        'proteina_g' => 'Proteína (g)',
        'carbohidratos_g' => 'Carbohidratos (g)',
        'azucares_g' => 'Azúcares (g)',
        'fibra_g' => 'Fibra (g)',
        'grasas_g' => 'Grasas (g)',
        'grasas_saturadas_g' => 'Saturadas (g)',
        'sodio_mg' => 'Sodio (mg)',
        'omega3_mg' => 'Omega-3 (mg)',
        'omega6_mg' => 'Omega-6 (mg)',
        'calcio_mg' => 'Calcio (mg)',
        'hierro_mg' => 'Hierro (mg)',
        'magnesio_mg' => 'Magnesio (mg)',
        'fosforo_mg' => 'Fósforo (mg)',
        'potasio_mg' => 'Potasio (mg)',
        'zinc_mg' => 'Zinc (mg)',
        'selenio_ug' => 'Selenio (µg)',
        'cobre_mg' => 'Cobre (mg)',
        'manganeso_mg' => 'Manganeso (mg)',
        'yodo_ug' => 'Yodo (µg)',
        'vitamina_a_rae_ug' => 'Vit. A (RAE, µg)',
        'vitamina_c_mg' => 'Vit. C (mg)',
        'vitamina_d_ug' => 'Vit. D (µg)',
        'vitamina_e_mg' => 'Vit. E (mg)',
        'vitamina_k_ug' => 'Vit. K (µg)',
    ];

    // Solo campos que el pegado masivo puede tocar
    private function bulkEditableFields(): array
    {
        return array_keys($this->fieldLabels);
    }

    public function preview()
    {
        helper('comidas_parse');

        $m  = new ComidasAlimentosModel();
        $id = (int)($this->request->getPost('id') ?? 0);

        $current = $id ? ($m->find($id) ?? []) : [];
        $current = array_merge($this->defaults(), $current);

        $bulk   = (string)($this->request->getPost('bulk') ?? '');
        $parsed = comidas_parse_bulk($bulk);
        $parsed = $this->normalizePayload($parsed);

        $fields  = $this->bulkEditableFields();
        $changes = [];
        $parsedOut = [];

        foreach ($fields as $f) {
            if (!array_key_exists($f, $parsed)) continue;
            $old = isset($current[$f]) ? (float)$current[$f] : 0.0;
            $new = (float)$parsed[$f];
            $parsedOut[$f] = $new;
            if (abs($old - $new) > 1e-9) {
                $changes[] = [
                    'field' => $f,
                    'label' => $this->fieldLabels[$f] ?? $f,
                    'old'   => $old,
                    'new'   => $new,
                ];
            }
        }

        return $this->response->setJSON([
            'ok'      => true,
            'changes' => $changes,   // para pintar el diff
            'parsed'  => $parsedOut, // para rellenar valores al aplicar selección
        ]);
    }

    private function filterByApplyFields(array $data): array
    {
        // Campos marcados por el usuario en el preview
        $apply = (array)($this->request->getPost('apply_fields') ?? []);

        if (empty($apply)) {
            // compat: si no llega nada, se aplican todos como antes
            return $data;
        }

        // Permitir siempre estos “no nutricionales” del formulario
        $always = ['nombre', 'marca', 'descripcion', 'es_receta', 'receta_id', 'es_liquido', 'densidad_g_ml'];

        $out = [];
        foreach ($always as $k) {
            if (array_key_exists($k, $data)) $out[$k] = $data[$k];
        }

        // Solo los campos marcados
        foreach ($apply as $f) {
            if (array_key_exists($f, $data)) {
                $out[$f] = $data[$f];
            }
        }
        return $out;
    }
}
