<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaPlacaModel;
use App\Models\PiezaPlacaPruebaModel;

/**
 * Cuaderno: todas las anotaciones de las bitácoras de placa en una sola
 * página para leerlas de corrido — preguntas y respuestas de las pruebas,
 * conclusiones, notas, notas por pieza y fotos —, de la placa más reciente a
 * la más antigua. No guarda nada propio: es una vista encima de lo que ya
 * tiene cada bitácora. La única escritura es responder una pregunta
 * pendiente sin entrar en su placa.
 */
class CuadernoController extends BaseController
{
    public function index()
    {
        $db = db_connect();

        $placas = (new PiezaPlacaModel())->findAll();
        if ($placas === []) {
            return view('piezas/cuaderno', ['entradas' => [], 'pendientes' => [], 'familias' => []]);
        }
        $ids = array_column($placas, 'id');

        $pruebas = [];
        foreach ((new PiezaPlacaPruebaModel())->whereIn('placa_id', $ids)->orderBy('orden')->orderBy('id')->findAll() as $p) {
            $pruebas[(int) $p['placa_id']][] = $p;
        }

        $fotos = [];
        foreach ($db->table('piezas_placa_imagenes')->select('id, placa_id, notas')->whereIn('placa_id', $ids)
            ->orderBy('orden')->orderBy('id')->get()->getResultArray() as $f) {
            $fotos[(int) $f['placa_id']][] = ['url' => site_url('piezas/placa-imagen/' . $f['id'] . '/imagen'), 'nota' => (string) $f['notas']];
        }

        // Qué llevaba cada placa, con el nombre legible de cada pieza, de
        // una vez (la bitácora lo hace pieza a pieza; aquí serían cientos).
        $piezas = [];
        $familias = [];
        $filas = $db->table('piezas_placas_versiones pv')
            ->select('pv.id, pv.placa_id, pv.cantidad, pv.fallidas, pv.notas, v.numero, va.nombre AS variante, f.id AS familia_id, f.nombre AS familia')
            ->join('piezas_versiones v', 'v.id = pv.version_id')
            ->join('piezas_variantes va', 'va.id = v.variante_id')
            ->join('piezas_familias f', 'f.id = va.familia_id')
            ->whereIn('pv.placa_id', $ids)
            ->orderBy('pv.id')
            ->get()->getResultArray();
        $fotosPieza = [];
        $idsFila = array_column($filas, 'id');
        if ($idsFila !== []) {
            foreach ($db->table('piezas_placa_version_imagenes')->select('id, placa_version_id, notas, resultado')
                ->whereIn('placa_version_id', $idsFila)->orderBy('orden')->orderBy('id')->get()->getResultArray() as $f) {
                $fotosPieza[(int) $f['placa_version_id']][] = [
                    'url'  => site_url('piezas/placa-version-imagen/' . $f['id'] . '/imagen'),
                    'nota' => trim($f['notas'] . ($f['resultado'] ? ' (' . $f['resultado'] . ')' : '')),
                ];
            }
        }
        foreach ($filas as $fila) {
            $piezas[(int) $fila['placa_id']][] = [
                'nombre'   => $fila['familia'] . ($fila['variante'] !== $fila['familia'] ? ' · ' . $fila['variante'] : '') . ' v' . sprintf('%03d', (int) $fila['numero']),
                'familia'  => (int) $fila['familia_id'],
                'cantidad' => (int) $fila['cantidad'],
                'fallidas' => (int) $fila['fallidas'],
                'notas'    => trim((string) $fila['notas']),
                'fotos'    => $fotosPieza[(int) $fila['id']] ?? [],
            ];
            $familias[(int) $fila['familia_id']] = $fila['familia'];
        }
        natcasesort($familias);

        $entradas = [];
        $pendientes = [];
        foreach ($placas as $placa) {
            $id = (int) $placa['id'];
            $susPruebas = $pruebas[$id] ?? [];
            $susPiezas = $piezas[$id] ?? [];
            $conNotas = array_values(array_filter($susPiezas, static fn (array $p) => $p['notas'] !== '' || $p['fallidas'] > 0 || $p['fotos'] !== []));
            $notas = trim((string) $placa['notas']);
            $conclusiones = trim((string) $placa['conclusiones']);

            // Una placa sin nada escrito no tiene nada que contar aquí.
            if ($susPruebas === [] && $notas === '' && $conclusiones === '' && $conNotas === [] && empty($fotos[$id])) {
                continue;
            }

            $fecha = $placa['impresa_en'] ?: ($placa['descargada_en'] ?: $placa['creado_en']);
            $entradas[] = [
                'placa'        => $placa,
                'fecha'        => $fecha,
                'pruebas'      => $susPruebas,
                'notas'        => $notas,
                'conclusiones' => $conclusiones,
                'piezas'       => $conNotas,
                'familias'     => array_values(array_unique(array_column($susPiezas, 'familia'))),
                'resumen'      => implode(' · ', array_column($susPiezas, 'nombre')),
                'fotos'        => $fotos[$id] ?? [],
            ];
            foreach ($susPruebas as $p) {
                if (trim((string) $p['respuesta']) === '') {
                    $pendientes[] = ['prueba' => $p, 'placa' => $placa, 'fecha' => $fecha];
                }
            }
        }
        usort($entradas, static fn (array $a, array $b) => strcmp((string) $b['fecha'], (string) $a['fecha']));
        usort($pendientes, static fn (array $a, array $b) => strcmp((string) $b['fecha'], (string) $a['fecha']));

        return view('piezas/cuaderno', [
            'entradas'   => $entradas,
            'pendientes' => $pendientes,
            'familias'   => $familias,
        ]);
    }

    /** Responder (o corregir) una pregunta de prueba desde el cuaderno, por fetch(). */
    public function responder(int $pruebaId)
    {
        $model = new PiezaPlacaPruebaModel();
        if (!$model->find($pruebaId)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'mensaje' => 'Esa pregunta ya no existe.']);
        }

        $respuesta = trim((string) $this->request->getPost('respuesta'));
        $model->update($pruebaId, ['respuesta' => $respuesta === '' ? null : $respuesta]);

        return $this->response->setJSON(['ok' => true, 'respuesta' => $respuesta]);
    }
}
