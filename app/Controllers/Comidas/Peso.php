<?php

namespace App\Controllers\Comidas;

use App\Controllers\BaseController;
use App\Models\ComidasPesoModel;
use App\Models\GimnasioEntrenamientosModel;
use App\Services\TanitaImportService;
use CodeIgniter\I18n\Time;

class Peso extends BaseController
{
    // Constante de clase (más limpio aún)
    private int $diasRango = 90;  // cámbialo a 90 cuando quieras

    public function index()
    {
        helper(['form', 'url']);

        $model = new ComidasPesoModel();

        // Últimos 30 registros (tabla)
        $ultimos = $model->orderBy('fecha', 'DESC')->limit($this->diasRango)->find();

        // Rango del último mes para el gráfico
        $hoy     = Time::now('Europe/Madrid')->toDateString(); // YYYY-MM-DD
        $desdeTs = strtotime('-{$this->diasRango} days', strtotime($hoy));
        $desde   = date('Y-m-d', $desdeTs);

        $ultimoMes = $model->where('fecha >=', $desde)
            ->where('fecha <=', $hoy)
            ->orderBy('fecha', 'ASC')
            ->find();

        // ===== Entrenamientos =====
        $entrenaday = new GimnasioEntrenamientosModel();

        // ===== NUEVO: macros por día en el rango =====
        $macrosPorDia = $this->getMacrosPorDia($desde, $hoy);

        // (Opcional) kcal/kg y prot/kg si quieres ratios
        $pesoPorDia = [];
        foreach ($ultimoMes as $r) $pesoPorDia[$r['fecha']] = (float)$r['peso'];

        $macrosExtendidos = [];
        foreach ($macrosPorDia as $dia => $m) {
            $p = $pesoPorDia[$dia] ?? null;
            $macrosExtendidos[$dia] = $m + [
                'kcal_por_kg' => $p ? round($m['kcal'] / $p, 1) : null,
                'prot_por_kg' => $p ? round($m['proteina_g'] / $p, 2) : null,
            ];
        }

        // Fecha seleccionada para "entrenos del día" (query param ?fecha=YYYY-MM-DD) o hoy
        $fecha = $this->request->getGet('fecha') ?? $hoy;

        // Entrenamientos del día
        $entrenosDia = $entrenaday
            ->select('id, fecha, tipo_sesion, notas_generales, lesiones, sin_molestias')
            // Si tu columna es DATETIME, usa DATE(fecha)
            // ->where('DATE(fecha)', $fecha)
            ->where('fecha', $fecha)  // si 'fecha' es DATE
            ->orderBy('id', 'ASC')
            ->findAll();

        $tiposEntreno = array_values(array_filter(array_unique(array_map(
            static fn($e) => trim((string)($e['tipo_sesion'] ?? '')),
            $entrenosDia
        ))));

        $huboEntreno = !empty($entrenosDia);

        // Fechas (distintas) con entrenamiento en el rango del gráfico
        // Si 'fecha' es DATETIME, cambia a: ->select('DATE(fecha) AS dia')
        $entrenosPorDia = $entrenaday
            ->select('fecha AS dia, COUNT(*) AS n')
            ->where('fecha >=', $desde)
            ->where('fecha <=', $hoy)
            ->groupBy('dia')
            ->orderBy('dia', 'ASC')
            ->find();

        // Array simple de días con entreno: ['2025-08-28', '2025-08-30', ...]
        $diasConEntreno = array_map(static fn($r) => $r['dia'], $entrenosPorDia);

        // Mapa rápido para marcar en el gráfico/calendario: ['2025-08-28' => 2, ...]
        $mapEntrenos = [];
        foreach ($entrenosPorDia as $r) {
            $mapEntrenos[$r['dia']] = (int) $r['n'];
        }

        // ===== Preparar arrays para el gráfico de peso =====
        $labels = [];
        $values = [];
        $grasaValues = [];
        $aguaValues  = [];
        $flagsEntreno = []; // true/false para cada punto del gráfico
        foreach ($ultimoMes as $row) {
            $dia = $row['fecha']; // YYYY-MM-DD
            $labels[] = date('d/m', strtotime($dia));
            $values[] = (float) $row['peso'];
            $grasaValues[] = $row['grasa_corporal_pct'] !== null ? (float) $row['grasa_corporal_pct'] : null;
            $aguaValues[]  = $row['agua_corporal_pct']  !== null ? (float) $row['agua_corporal_pct']  : null;
            $flagsEntreno[] = isset($mapEntrenos[$dia]); // marca si hubo entreno ese día
        }

        // === Tipos de entrenamiento por día (para la tabla) ===
        // Si tu columna 'fecha' es DATETIME, usa DATE(fecha) AS dia y whereBetween por DATE(fecha)
        $entrenosTodos = $entrenaday
            ->select('fecha, tipo_sesion') // si es DATETIME: ->select('DATE(fecha) AS fecha, tipo_sesion')
            ->where('fecha >=', $desde)
            ->where('fecha <=', $hoy)
            ->orderBy('fecha', 'ASC')
            ->findAll();

        $entrenosTiposPorDia = []; // ['YYYY-MM-DD' => ['Fuerza', 'Cardio', ...]]
        foreach ($entrenosTodos as $e) {
            $dia  = $e['fecha']; // si usas DATE(fecha) AS fecha, sigue siendo 'YYYY-MM-DD'
            $tipo = trim((string)($e['tipo_sesion'] ?? ''));
            if ($tipo === '') continue;
            $entrenosTiposPorDia[$dia][] = $tipo;
        }

        // Quita duplicados por día y ordena bonito
        foreach ($entrenosTiposPorDia as $dia => $lista) {
            $lista = array_values(array_filter(array_unique($lista)));
            sort($lista, SORT_NATURAL | SORT_FLAG_CASE);
            $entrenosTiposPorDia[$dia] = $lista;
        }


        return view('comidas/peso/index', [
            'hoy'           => $hoy,
            'desde'         => $desde,
            'ultimos'       => $ultimos,
            'labels'        => $labels,
            'values'        => $values,
            'grasaValues'   => $grasaValues,
            'aguaValues'    => $aguaValues,
            'flagsEntreno'  => $flagsEntreno,   // para pintar el gráfico (p.ej. color/marker distinto)
            'diasConEntreno' => $diasConEntreno, // para un calendario/listado
            'mapEntrenos'   => $mapEntrenos,    // si quieres mostrar conteos por día
            'entrenosDia'   => $entrenosDia,
            'entrenosTiposPorDia' => $entrenosTiposPorDia,
            'tiposEntreno'  => $tiposEntreno,
            'huboEntreno'   => $huboEntreno,
            'macrosPorDia' => $macrosExtendidos,
            'exportTexto60d' => $this->buildExportTexto($ultimos, $macrosExtendidos, $entrenosTiposPorDia, 60),

            // Por si venimos de un duplicado:
            'dup_fecha'     => session()->getFlashdata('dup_fecha'),
            'dup_peso'      => session()->getFlashdata('dup_peso'),
            'dup_id'        => session()->getFlashdata('dup_id'),
        ]);
    }

    /**
     * Texto plano (tabla con separador " | ") con el histórico de peso y composición
     * corporal de los últimos $dias días, pensado para copiar y pegar en un chat de IA.
     */
    private function buildExportTexto(array $ultimos, array $macrosPorDia, array $entrenosTiposPorDia, int $dias): string
    {
        $corte = date('Y-m-d', strtotime("-{$dias} days"));

        // $ultimos viene ordenado DESC; nos quedamos con los últimos $dias días y
        // lo invertimos para que quede en orden cronológico (más fácil de leer/analizar).
        $filas = array_values(array_filter($ultimos, static fn($r) => $r['fecha'] >= $corte));
        $filas = array_reverse($filas);

        $lineas   = [];
        $lineas[] = "Registro de peso y composición corporal — últimos {$dias} días";
        $lineas[] = 'Fecha | Peso(kg) | Kcal | Proteina(g) | Carbohidratos(g) | Grasas(g) | Entrenamiento | IMC | %GrasaCorporal | %AguaCorporal';

        foreach ($filas as $r) {
            $dia   = $r['fecha'];
            $m     = $macrosPorDia[$dia] ?? null;
            $tipos = $entrenosTiposPorDia[$dia] ?? [];

            $lineas[] = implode(' | ', [
                $dia,
                number_format((float) $r['peso'], 2, '.', ''),
                $m ? (int) $m['kcal'] : '',
                $m ? number_format($m['proteina_g'], 1, '.', '') : '',
                $m ? number_format($m['carbohidratos_g'], 1, '.', '') : '',
                $m ? number_format($m['grasas_g'], 1, '.', '') : '',
                $tipos ? implode('/', $tipos) : '',
                $r['imc'] !== null ? number_format((float) $r['imc'], 2, '.', '') : '',
                $r['grasa_corporal_pct'] !== null ? number_format((float) $r['grasa_corporal_pct'], 1, '.', '') : '',
                $r['agua_corporal_pct'] !== null ? number_format((float) $r['agua_corporal_pct'], 1, '.', '') : '',
            ]);
        }

        return implode("\n", $lineas);
    }


    public function store()
    {
        $model = new ComidasPesoModel();

        // Sanitizar/validar rápido
        $fecha = trim((string)$this->request->getPost('fecha'));
        $peso  = str_replace(',', '.', trim((string)$this->request->getPost('peso')));

        if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return redirect()->back()->withInput()->with('error', 'Fecha inválida.');
        }
        if ($peso === '' || !is_numeric($peso)) {
            return redirect()->back()->withInput()->with('error', 'El peso debe ser numérico.');
        }

        // Comprobar si ya existe esa fecha
        $existente = $model->where('fecha', $fecha)->first();
        if ($existente) {
            // Informar y devolver al formulario sin romper nada
            session()->setFlashdata(
                'warning',
                'Ya existe un registro para esa fecha.'
                    . ' Valor guardado: ' . rtrim(rtrim((string)$existente['peso'], '0'), '.')
                    . ' kg. Puedes borrar el registro existente o elegir otra fecha.'
            );
            // Enviar además datos del existente por si quieres mostrarlos en la vista
            session()->setFlashdata('dup_fecha', $existente['fecha']);
            session()->setFlashdata('dup_peso',  $existente['peso']);
            session()->setFlashdata('dup_id',    $existente['id']);

            return redirect()->back()->withInput();
        }

        // Guardar normalmente
        $data = [
            'fecha' => $fecha,
            'peso'  => $peso,
        ];

        if (!$model->save($data)) {
            // Capturar posible error 1062 si hay índice único en fecha (por si se coló la carrera)
            $err = implode(' ', $model->errors());
            return redirect()->back()->withInput()->with('error', $err ?: 'No se pudo guardar el peso.');
        }

        return redirect()->to(site_url('comidas/peso'))->with('success', 'Peso registrado correctamente.');
    }

    public function delete($id)
    {
        $model = new ComidasPesoModel();

        if (!$model->delete((int)$id)) {
            return redirect()->back()->with('error', 'No se pudo eliminar el registro.');
        }

        return redirect()->to(site_url('comidas/peso'))->with('success', 'Registro eliminado.');
    }

    public function importarForm()
    {
        return view('comidas/peso/importar', [
            'title' => 'Importar CSV de báscula',
        ]);
    }

    public function importar()
    {
        $file = $this->request->getFile('csv');

        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Sube un archivo CSV válido.');
        }
        if (strtolower($file->getClientExtension()) !== 'csv') {
            return redirect()->back()->with('error', 'El archivo debe ser un .csv');
        }

        try {
            $resumen = (new TanitaImportService())->importFromCsv($file->getTempName());
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'No se pudo importar: ' . $e->getMessage());
        }

        $msg = "Importación completa: {$resumen['dias']} días procesados, "
             . "{$resumen['insertadas']} nuevos, {$resumen['actualizadas']} actualizados.";
        if (!empty($resumen['errores'])) {
            $msg .= ' Con ' . count($resumen['errores']) . ' error(es): ' . implode(' | ', $resumen['errores']);
            return redirect()->to(site_url('comidas/peso'))->with('warning', $msg);
        }

        return redirect()->to(site_url('comidas/peso'))->with('success', $msg);
    }

    // Opcional: JSON del último mes (por si quieres cargar el gráfico vía fetch)
    public function ultimoMesJson()
    {
        $model = new ComidasPesoModel();

        $hoy     = Time::now('Europe/Madrid')->toDateString();
        $desdeTs = strtotime('-{$this->diasRango} days', strtotime($hoy));
        $desde   = date('Y-m-d', $desdeTs);

        $rows = $model->where('fecha >=', $desde)
            ->where('fecha <=', $hoy)
            ->orderBy('fecha', 'ASC')
            ->find();

        return $this->response->setJSON($rows);
    }

    /**
     * Macros por día en rango [desde, hasta], solo con ALIMENTOS
     * (mismo criterio que usas en Diario: SUM(cantidad_gramos * valor_100 / 100)).
     * Devuelve: ['YYYY-MM-DD' => ['kcal'=>int,'proteina_g'=>float,'carbohidratos_g'=>float,'grasas_g'=>float]]
     */
    private function getMacrosPorDia(string $desde, string $hasta): array
    {
        $db = \Config\Database::connect();

        $sql = "
        SELECT cd.fecha,
               COALESCE(SUM(ci.cantidad_gramos * ca.kcal          / 100), 0) AS kcal,
               COALESCE(SUM(ci.cantidad_gramos * ca.proteina_g    / 100), 0) AS proteina_g,
               COALESCE(SUM(ci.cantidad_gramos * ca.carbohidratos_g/100), 0) AS carbohidratos_g,
               COALESCE(SUM(ci.cantidad_gramos * ca.grasas_g      / 100), 0) AS grasas_g
        FROM comidas_ingestas ci
        JOIN comidas_dias cd
              ON cd.id = ci.dia_id
        LEFT JOIN comidas_alimentos ca
              ON ca.id = ci.item_id
             AND ci.item_tipo = 'alimento'
        WHERE cd.fecha BETWEEN ? AND ?
        GROUP BY cd.fecha
        ORDER BY cd.fecha ASC
    ";

        $rows = $db->query($sql, [$desde, $hasta])->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[$r['fecha']] = [
                'kcal'           => (int) round((float)$r['kcal']),
                'proteina_g'     => round((float)$r['proteina_g'], 1),
                'carbohidratos_g' => round((float)$r['carbohidratos_g'], 1),
                'grasas_g'       => round((float)$r['grasas_g'], 1),
            ];
        }
        return $map;
    }
}
