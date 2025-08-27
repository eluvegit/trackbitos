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

        // receta_id: NULL si no es receta o si viene vacío/0
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

        // 👉 ir al formulario de edición del registro recién creado
        $id = (int) $m->getInsertID();
        if ($id > 0) {
            return redirect()
                ->to(site_url('comidas/alimentos/edit/' . $id))
                ->with('ok', 'Alimento creado. Puedes seguir editando.');
        }

        // Fallback improbable
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
        $data = $this->filterByApplyFields($data);

        if (empty(trim($data['nombre'] ?? ''))) {
            return redirect()->back()->withInput()
                ->with('errors', ['El nombre es obligatorio.']);
        }

        if (!$m->update($id, $data)) {
            return redirect()->back()->withInput()->with('errors', $m->errors());
        }

        return redirect()->to(site_url('comidas/alimentos/edit/' . $id))
                 ->with('ok', 'Alimento actualizado');

    }

    // =================== Eliminar ===================

    public function delete($id)
    {
        $id = (int) $id;

        $aliM = new \App\Models\ComidasAlimentosModel();
        $ingM = new \App\Models\ComidasIngestasModel();
        $porM = new \App\Models\ComidasAlimentoUnidadesModel();

        $row = $aliM->find($id);
        if (!$row) {
            return redirect()->to(site_url('comidas/alimentos'))
                ->with('errors', ['Alimento no encontrado.']);
        }

        // Si es un "alimento virtual" generado por receta, fuerza borrarlo desde Recetas
        if (!empty($row['es_receta'])) {
            return redirect()->to(site_url('comidas/alimentos'))
                ->with('errors', ['Este alimento proviene de una receta. Elimínalo desde Recetas.']);
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Borra ingestas que apunten a este alimento
            $ingM->where('item_tipo', 'alimento')->where('item_id', $id)->delete();

            // Borra porciones ligadas a este alimento
            $porM->where('alimento_id', $id)->delete();

            // Borra el alimento
            $aliM->delete($id);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Fallo al borrar alimento.');
            }
            $db->transCommit();

            return redirect()->to(site_url('comidas/alimentos'))
                ->with('ok', 'Alimento eliminado.');
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->to(site_url('comidas/alimentos'))
                ->with('errors', ['No se pudo eliminar el alimento. ' . $e->getMessage()]);
        }
    }


    // ====== Labels para diffs de preview ======
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

    // =================== Preview (simular cambios) ===================

    public function preview()
    {
        helper('comidas_parse');

        $id   = (int) $this->request->getPost('id');
        $bulk = (string) ($this->request->getPost('bulk') ?? '');
        $url  = trim((string) ($this->request->getPost('url') ?? ''));

        // Si viene URL y no hay bulk, intentamos descargar y convertir a "bulk"
        if ($bulk === '' && $url !== '') {
            $host = parse_url($url, PHP_URL_HOST) ?: '';
            if (!preg_match('/(^|\.)nutrionio\.com$/i', $host)) {
                return $this->response->setJSON(['ok' => false, 'error' => 'Solo se permite nutrionio.com']);
            }

            $html = $this->fetchUrl($url);
            if ($html === '') {
                return $this->response->setJSON([
                    'ok' => false,
                    'error' => 'No se pudo obtener el HTML. Puede que el sitio bloquee la petición. Usa el pegado manual.'
                ]);
            }

            $text = $this->htmlToText($html);
            $bulk = $this->extractNutrientLines($text);
            if ($bulk === '') {
                return $this->response->setJSON([
                    'ok' => false,
                    'error' => 'No se detectaron líneas de nutrientes en la página. Copia/pega el bloque manualmente.'
                ]);
            }
        }

        // Nada que parsear
        if ($bulk === '') {
            return $this->response->setJSON([
                'ok' => false,
                'error' => 'Debes indicar una URL de nutrionio.com o pegar el bloque de nutrientes.'
            ]);
        }

        $parsed  = comidas_parse_bulk($bulk);
        $current = $id ? (new \App\Models\ComidasAlimentosModel())->find($id) : [];

        $labels   = $this->fieldLabels;
        $editable = $this->bulkEditableFields();

        $changes = [];
        foreach ($editable as $field) {
            if (!array_key_exists($field, $parsed)) continue;
            $new = $parsed[$field];
            $old = $current[$field] ?? null;

            $isDiff = ($old === null) || ((float)$old !== (float)$new);
            if ($isDiff) {
                $changes[] = [
                    'field' => $field,
                    'label' => $labels[$field] ?? $field,
                    'old'   => $old ?? '—',
                    'new'   => $new,
                ];
            }
        }

        return $this->response->setJSON(['ok' => true, 'parsed' => $parsed, 'changes' => $changes]);
    }

    /** Descarga robusta del HTML (UA, idioma, gzip) */
    private function fetchUrl(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_ENCODING       => '', // acepta gzip/deflate/br si está disponible
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
                'Cache-Control: no-cache',
            ],
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
        ]);
        $html = curl_exec($ch);
        curl_close($ch);
        return is_string($html) ? $html : '';
    }

    /** Limpia HTML a texto legible */
    private function htmlToText(string $html): string
    {
        // quita scripts y estilos primero
        $html = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', ' ', $html);
        // convierte entidades y elimina tags
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // normaliza espacios
        $text = preg_replace('/\x{00A0}/u', ' ', $text); // &nbsp;
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = preg_replace('/\R+/u', "\n", $text);
        return trim($text);
    }

    /** Extrae solo líneas que parecen “nutriente + número + unidad” */
    private function extractNutrientLines(string $text): string
    {
        $out = [];
        foreach (preg_split('/\R/u', $text) as $line) {
            $line = trim($line);
            if ($line === '') continue;

            // acepta kcal, g, mg, mcg, µg (y luego quitamos “NN%”)
            if (preg_match('/\b(\d+(?:[.,]\d+)?)\s?(kcal|g|mg|mcg|µg)\b/i', $line)) {
                $line = preg_replace('/\s+\d{1,3}\s?%(\b|$)/', '', $line);
                $out[] = $line;
            }
        }
        return trim(implode("\n", $out));
    }

    /** Filtra payload según apply_fields[] del preview */
    private function filterByApplyFields(array $data): array
    {
        $apply = (array)($this->request->getPost('apply_fields') ?? []);

        if (empty($apply)) {
            // compat: si no llega nada, se aplica todo
            return $data;
        }

        // Campos no nutricionales que siempre permitimos
        $always = ['nombre', 'marca', 'descripcion', 'es_receta', 'receta_id', 'es_liquido', 'densidad_g_ml'];

        $out = [];
        foreach ($always as $k) {
            if (array_key_exists($k, $data)) $out[$k] = $data[$k];
        }

        foreach ($apply as $f) {
            if (array_key_exists($f, $data)) {
                $out[$f] = $data[$f];
            }
        }
        return $out;
    }
}
