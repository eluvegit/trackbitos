<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaTiradaLineaModel;
use App\Models\PiezaTiradaModel;
use App\Models\PiezaTiradaPiezaModel;
use App\Models\PiezaTiradaPlacaModel;

/**
 * Tiradas: planificar una producción grande (p. ej. 250 muñecos) en placas
 * por días y máquinas. Aquí solo se guardan la definición (piezas con sus
 * condiciones, placas en orden de cola con sus líneas, máquinas) y lo que
 * cambia día a día (confirmación, días fuera, placas impresas, fallidas); el reparto por día y máquina lo simula la
 * vista con esos datos, para que se recoloque solo al tocar cualquier ajuste.
 */
class TiradasController extends BaseController
{
    private PiezaTiradaModel $tiradas;
    private PiezaTiradaPiezaModel $piezas;
    private PiezaTiradaPlacaModel $placas;
    private PiezaTiradaLineaModel $lineas;

    public function __construct()
    {
        $this->tiradas = new PiezaTiradaModel();
        $this->piezas  = new PiezaTiradaPiezaModel();
        $this->placas  = new PiezaTiradaPlacaModel();
        $this->lineas  = new PiezaTiradaLineaModel();
    }

    public function index()
    {
        $filas = [];
        foreach ($this->tiradas->orderBy('id', 'DESC')->findAll() as $t) {
            $placas  = $this->placas->deTirada((int) $t['id']);
            $filas[] = [
                'tirada'   => $t,
                'placas'   => count($placas),
                'impresas' => count(array_filter($placas, static fn (array $p) => $p['impresa_en'] !== null)),
                'dia'      => PiezaTiradaModel::diaDeHoy($t),
            ];
        }

        return view('piezas/tiradas/index', ['filas' => $filas]);
    }

    /** Alta con solo el nombre; "copiar de" trae piezas, placas, máquinas y notas de otra tirada. */
    public function crear()
    {
        $nombre = trim((string) $this->request->getPost('nombre'));
        if ($nombre === '') {
            return redirect()->to(site_url('piezas/tiradas'))->with('error', 'Ponle un nombre a la tirada.');
        }

        $origen = $this->tiradas->find((int) $this->request->getPost('copiar_de'));
        $datos  = ['nombre' => $nombre, 'fecha_inicio' => $this->fecha((string) $this->request->getPost('fecha_inicio')) ?? date('Y-m-d')];
        if ($origen) {
            foreach (['notas', 'plazo_dias', 'maquinas', 'placas_dia', 'placas_correo', 'cambio_resina'] as $campo) {
                $datos[$campo] = $origen[$campo];
            }
        }

        $db = db_connect();
        $db->transStart();
        $id = (int) $this->tiradas->insert($datos);
        if ($origen) {
            $piezaNueva = [];
            foreach ($this->piezas->deTirada((int) $origen['id']) as $pieza) {
                $vieja = (int) $pieza['id'];
                unset($pieza['id']);
                $pieza['tirada_id'] = $id;
                $piezaNueva[$vieja] = (int) $this->piezas->insert($pieza);
            }
            $lineasDe = $this->lineas->porPlaca((int) $origen['id']);
            foreach ($this->placas->deTirada((int) $origen['id']) as $placa) {
                $vieja = (int) $placa['id'];
                unset($placa['id']);
                $placa['tirada_id']  = $id;
                $placa['impresa_en'] = null;
                $placaId = (int) $this->placas->insert($placa);
                foreach ($lineasDe[$vieja] ?? [] as $l) {
                    $this->lineas->insert(['placa_id' => $placaId, 'pieza_id' => $piezaNueva[(int) $l['pieza_id']], 'cantidad' => $l['cantidad'], 'fallidas' => 0]);
                }
            }
        }
        $db->transComplete();

        return redirect()->to(site_url('piezas/tiradas/' . $id))
            ->with('success', $origen ? 'Tirada creada como copia de «' . $origen['nombre'] . '», sin nada impreso.' : 'Tirada creada. Define sus piezas y sus placas.');
    }

    public function ver(int $id)
    {
        $tirada = $this->tiradas->find($id);
        if (!$tirada) {
            return redirect()->to(site_url('piezas/tiradas'))->with('error', 'Esa tirada no existe.');
        }

        return view('piezas/tiradas/ver', [
            'tirada'   => $tirada,
            'maquinas' => PiezaTiradaModel::maquinas((string) $tirada['maquinas']),
            'piezas'   => $this->piezas->deTirada($id),
            'placas'   => $this->placas->deTirada($id),
            'lineas'   => $this->lineas->porPlaca($id),
            'hoy'      => PiezaTiradaModel::diaDeHoy($tirada),
            'otras'    => $this->tiradas->where('id !=', $id)->orderBy('id', 'DESC')->findAll(),
        ]);
    }

    /** Datos generales (modal "Ajustes"): nombre, fechas, máquinas, notas. */
    public function actualizar(int $id)
    {
        if (!$this->tiradas->find($id)) {
            return redirect()->to(site_url('piezas/tiradas'))->with('error', 'Esa tirada no existe.');
        }

        $nombre = trim((string) $this->request->getPost('nombre'));
        if ($nombre === '') {
            return $this->volver($id)->with('error', 'El nombre no puede quedar vacío.');
        }
        $maquinas = PiezaTiradaModel::maquinas((string) $this->request->getPost('maquinas'));
        if ($maquinas === []) {
            return $this->volver($id)->with('error', 'Pon al menos una máquina («A: blanco»).');
        }

        $placasDia = max(1, min(10, (int) $this->request->getPost('placas_dia')));
        $this->tiradas->update($id, [
            'nombre'        => $nombre,
            'notas'         => trim((string) $this->request->getPost('notas')),
            'fecha_inicio'  => $this->fecha((string) $this->request->getPost('fecha_inicio')),
            'plazo_dias'    => max(1, min(365, (int) $this->request->getPost('plazo_dias'))),
            'maquinas'      => implode("\n", array_map(static fn (array $m) => $m['nombre'] . ': ' . $m['resina'], $maquinas)),
            'placas_dia'    => $placasDia,
            'placas_correo' => max(0, min($placasDia, (int) $this->request->getPost('placas_correo'))),
        ]);

        return $this->volver($id)->with('success', 'Tirada guardada.');
    }

    /**
     * Lo que se toca a menudo desde el panel, por fetch(): día de
     * confirmación, si se permite cambiar de resina y los días fuera.
     */
    public function ajustes(int $id)
    {
        $tirada = $this->tiradas->find($id);
        if (!$tirada) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'mensaje' => 'Esa tirada no existe.']);
        }

        $datos = [];
        if ($this->request->getPost('dia_confirmacion') !== null) {
            $datos['dia_confirmacion'] = max(1, min(365, (int) $this->request->getPost('dia_confirmacion')));
        }
        if ($this->request->getPost('cambio_resina') !== null) {
            $datos['cambio_resina'] = $this->request->getPost('cambio_resina') ? 1 : 0;
        }
        if ($this->request->getPost('dias_fuera') !== null) {
            $dias = array_unique(array_filter(array_map('intval', explode(',', (string) $this->request->getPost('dias_fuera')))));
            sort($dias);
            $datos['dias_fuera'] = implode(',', $dias);
        }
        if ($datos !== []) {
            $this->tiradas->update($id, $datos);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    public function borrar(int $id)
    {
        $tirada = $this->tiradas->find($id);
        if ($tirada) {
            $this->tiradas->delete($id);
        }

        return redirect()->to(site_url('piezas/tiradas'))->with('success', $tirada ? 'Tirada «' . $tirada['nombre'] . '» borrada.' : 'Esa tirada ya no existía.');
    }

    /**
     * Tabla entera de piezas (modal "Piezas"): las filas con id se
     * actualizan, las nuevas se crean y las que faltan se borran — salvo
     * que alguna placa todavía las lleve, que se quedan con aviso.
     */
    public function guardarPiezas(int $id)
    {
        if (!$this->tiradas->find($id)) {
            return redirect()->to(site_url('piezas/tiradas'))->with('error', 'Esa tirada no existe.');
        }

        $existentes = array_column($this->piezas->deTirada($id), null, 'id');
        $vistos     = [];
        $orden      = 0;
        foreach ((array) $this->request->getPost('piezas') as $fila) {
            $nombre = trim((string) ($fila['nombre'] ?? ''));
            if ($nombre === '') {
                continue;
            }
            $datos = [
                'tirada_id'           => $id,
                'nombre'              => mb_substr($nombre, 0, 80),
                'resina'              => mb_strtolower(trim((string) ($fila['resina'] ?? ''))),
                'necesarias'          => max(0, (int) ($fila['necesarias'] ?? 0)),
                'margen'              => max(0, min(1000, (int) ($fila['margen'] ?? 0))),
                'por_placa'           => max(1, (int) ($fila['por_placa'] ?? 1)),
                'prioridad'           => max(0, min(9, (int) ($fila['prioridad'] ?? 0))),
                'espera_confirmacion' => empty($fila['espera_confirmacion']) ? 0 : 1,
                'color'               => preg_match('/^#[0-9a-f]{6}$/i', (string) ($fila['color'] ?? '')) ? $fila['color'] : '#9aa5b3',
                'orden'               => $orden++,
            ];
            $piezaId = (int) ($fila['id'] ?? 0);
            if ($piezaId && isset($existentes[$piezaId])) {
                $this->piezas->update($piezaId, $datos);
                $vistos[$piezaId] = true;
            } else {
                $this->piezas->insert($datos);
            }
        }

        $enPlacas = [];
        foreach (array_diff_key($existentes, $vistos) as $piezaId => $pieza) {
            if ($this->lineas->where('pieza_id', $piezaId)->countAllResults() > 0) {
                $enPlacas[] = $pieza['nombre'];
            } else {
                $this->piezas->delete($piezaId);
            }
        }

        $volver = $this->volver($id);

        return $enPlacas
            ? $volver->with('error', 'No se borra ' . implode(', ', $enPlacas) . ': alguna placa todavía la lleva. Quítala antes.')
            : $volver->with('success', 'Piezas guardadas.');
    }

    /**
     * Tabla entera de placas (modal "Placas"), en orden de cola, cada una
     * con sus líneas de pieza. Todas las líneas de una placa van en la misma
     * resina. Las placas y líneas que ya existían conservan si están
     * impresas y sus fallidas (como mucho, la cantidad nueva).
     */
    public function guardarPlacas(int $id)
    {
        if (!$this->tiradas->find($id)) {
            return redirect()->to(site_url('piezas/tiradas'))->with('error', 'Esa tirada no existe.');
        }

        $piezas = array_column($this->piezas->deTirada($id), null, 'id');
        $limpias = [];
        foreach ((array) $this->request->getPost('placas') as $fila) {
            $codigo = mb_substr(trim((string) ($fila['codigo'] ?? '')), 0, 20);
            $lineas = [];
            foreach ((array) ($fila['lineas'] ?? []) as $l) {
                $pieza    = $piezas[(int) ($l['pieza_id'] ?? 0)] ?? null;
                $cantidad = max(0, (int) ($l['cantidad'] ?? 0));
                if ($pieza && $cantidad > 0) {
                    $lineas[] = ['id' => (int) ($l['id'] ?? 0), 'pieza_id' => (int) $pieza['id'], 'cantidad' => $cantidad];
                }
            }
            if ($codigo === '' || $lineas === []) {
                continue;
            }
            $resinas = array_unique(array_map(static fn (array $l) => $piezas[$l['pieza_id']]['resina'], $lineas));
            if (count($resinas) > 1) {
                return $this->volver($id)->with('error', 'La placa ' . $codigo . ' mezcla resinas (' . implode(', ', $resinas) . '). No se ha guardado nada.');
            }
            $limpias[] = ['id' => (int) ($fila['id'] ?? 0), 'codigo' => $codigo, 'motivo' => trim((string) ($fila['motivo'] ?? '')), 'lineas' => $lineas];
        }

        $existentes = array_column($this->placas->deTirada($id), null, 'id');
        $lineasDe   = $this->lineas->porPlaca($id);
        $vistos     = [];
        $db         = db_connect();
        $db->transStart();
        foreach ($limpias as $orden => $placa) {
            $datos = ['tirada_id' => $id, 'codigo' => $placa['codigo'], 'motivo' => $placa['motivo'], 'orden' => $orden];
            $placaId = $placa['id'];
            if ($placaId && isset($existentes[$placaId])) {
                $this->placas->update($placaId, $datos);
                $vistos[$placaId] = true;
            } else {
                $placaId = (int) $this->placas->insert($datos);
            }
            $antes = array_column($lineasDe[$placaId] ?? [], null, 'id');
            foreach ($placa['lineas'] as $l) {
                if ($l['id'] && isset($antes[$l['id']])) {
                    $this->lineas->update($l['id'], [
                        'pieza_id' => $l['pieza_id'], 'cantidad' => $l['cantidad'],
                        'fallidas' => min((int) $antes[$l['id']]['fallidas'], $l['cantidad']),
                    ]);
                    unset($antes[$l['id']]);
                } else {
                    $this->lineas->insert(['placa_id' => $placaId, 'pieza_id' => $l['pieza_id'], 'cantidad' => $l['cantidad'], 'fallidas' => 0]);
                }
            }
            if ($antes !== []) {
                $this->lineas->delete(array_keys($antes));
            }
        }
        foreach (array_keys(array_diff_key($existentes, $vistos)) as $placaId) {
            $this->placas->delete($placaId);
        }
        $db->transComplete();

        return $this->volver($id)->with('success', 'Placas guardadas.');
    }

    /** Marcar / desmarcar una placa como impresa, por fetch(). Desmarcar borra sus fallidas. */
    public function alternarImpresa(int $placaId)
    {
        $placa = $this->placas->find($placaId);
        if (!$placa) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'mensaje' => 'Esa placa no existe.']);
        }

        $impresaEn = $placa['impresa_en'] === null ? date('Y-m-d H:i:s') : null;
        $this->placas->update($placaId, ['impresa_en' => $impresaEn]);
        if ($impresaEn === null) {
            $this->lineas->where('placa_id', $placaId)->set('fallidas', 0)->update();
        }

        return $this->response->setJSON(['ok' => true, 'impresa_en' => $impresaEn]);
    }

    /**
     * Cantidad real (y fallidas, si está impresa) de cada línea de una placa,
     * por fetch(): cantidad[linea_id], fallidas[linea_id], propagar[linea_id].
     * Con propagar, las líneas de esa pieza en placas pendientes que llevaban
     * la misma cantidad pasan a la nueva, y también su "por placa" si coincidía.
     */
    public function guardarLineas(int $placaId)
    {
        $placa = $this->placas->find($placaId);
        if (!$placa) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'mensaje' => 'Esa placa no existe.']);
        }

        $tiradaId   = (int) $placa['tirada_id'];
        $cantidades = (array) $this->request->getPost('cantidad');
        $fallidas   = (array) $this->request->getPost('fallidas');
        $propagar   = (array) $this->request->getPost('propagar');
        $impresa    = $placa['impresa_en'] !== null;
        $propagadas = 0;

        $db = db_connect();
        $db->transStart();
        foreach ($this->lineas->where('placa_id', $placaId)->findAll() as $l) {
            $antes = (int) $l['cantidad'];
            $cant  = isset($cantidades[$l['id']]) ? max(1, (int) $cantidades[$l['id']]) : $antes;
            $fall  = $impresa ? max(0, min($cant, (int) ($fallidas[$l['id']] ?? $l['fallidas']))) : 0;
            $this->lineas->update($l['id'], ['cantidad' => $cant, 'fallidas' => $fall]);

            if ($cant !== $antes && !empty($propagar[$l['id']])) {
                $ids = array_column($this->lineas->select('piezas_tiradas_lineas.id')
                    ->join('piezas_tiradas_placas p', 'p.id = piezas_tiradas_lineas.placa_id')
                    ->where('p.tirada_id', $tiradaId)->where('p.impresa_en', null)->where('p.id !=', $placaId)
                    ->where('piezas_tiradas_lineas.pieza_id', $l['pieza_id'])->where('piezas_tiradas_lineas.cantidad', $antes)
                    ->findAll(), 'id');
                if ($ids !== []) {
                    $this->lineas->whereIn('id', $ids)->set('cantidad', $cant)->update();
                    $propagadas += count($ids);
                }
                $this->piezas->where('id', $l['pieza_id'])->where('por_placa', $antes)->set('por_placa', $cant)->update();
            }
        }
        $db->transComplete();

        $lineas = [];
        foreach ($this->lineas->porPlaca($tiradaId) as $deUna) {
            foreach ($deUna as $l) {
                $lineas[$l['id']] = ['cant' => (int) $l['cantidad'], 'fall' => (int) $l['fallidas']];
            }
        }

        return $this->response->setJSON([
            'ok'         => true,
            'lineas'     => $lineas,
            'por_placa'  => array_map('intval', array_column($this->piezas->deTirada($tiradaId), 'por_placa', 'id')),
            'propagadas' => $propagadas,
        ]);
    }

    private function volver(int $id)
    {
        return redirect()->to(site_url('piezas/tiradas/' . $id));
    }

    private function fecha(string $valor): ?string
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', trim($valor));

        return $d ? $d->format('Y-m-d') : null;
    }
}
