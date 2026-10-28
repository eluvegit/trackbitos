<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaTiradaModel;
use App\Models\PiezaTiradaPlacaModel;
use App\Models\PiezaTiradaTipoModel;

/**
 * Tiradas: planificar una producción grande (p. ej. 250 muñecos) en placas
 * por días y máquinas. Aquí solo se guardan la definición (tipos, placas en
 * orden de cola, máquinas y resinas) y lo que cambia día a día (confirmación,
 * días fuera, placas impresas); el reparto por día y máquina lo simula la
 * vista con esos datos, para que se recoloque solo al tocar cualquier ajuste.
 */
class TiradasController extends BaseController
{
    private PiezaTiradaModel $tiradas;
    private PiezaTiradaTipoModel $tipos;
    private PiezaTiradaPlacaModel $placas;

    public function __construct()
    {
        $this->tiradas = new PiezaTiradaModel();
        $this->tipos   = new PiezaTiradaTipoModel();
        $this->placas  = new PiezaTiradaPlacaModel();
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

    /** Alta con solo el nombre; "copiar de" trae tipos, placas, máquinas y notas de otra tirada. */
    public function crear()
    {
        $nombre = trim((string) $this->request->getPost('nombre'));
        if ($nombre === '') {
            return redirect()->to(site_url('piezas/tiradas'))->with('error', 'Ponle un nombre a la tirada.');
        }

        $origen = $this->tiradas->find((int) $this->request->getPost('copiar_de'));
        $datos  = ['nombre' => $nombre, 'fecha_inicio' => $this->fecha((string) $this->request->getPost('fecha_inicio')) ?? date('Y-m-d')];
        if ($origen) {
            foreach (['notas', 'plazo_dias', 'objetivo_h', 'objetivo_m', 'maquinas', 'placas_dia', 'placas_correo', 'cambio_resina'] as $campo) {
                $datos[$campo] = $origen[$campo];
            }
        }

        $db = db_connect();
        $db->transStart();
        $id = (int) $this->tiradas->insert($datos);
        if ($origen) {
            $tipoNuevo = [];
            foreach ($this->tipos->deTirada((int) $origen['id']) as $tipo) {
                $viejo = (int) $tipo['id'];
                unset($tipo['id']);
                $tipo['tirada_id'] = $id;
                $tipoNuevo[$viejo] = (int) $this->tipos->insert($tipo);
            }
            foreach ($this->placas->deTirada((int) $origen['id']) as $placa) {
                unset($placa['id']);
                $placa['tirada_id']  = $id;
                $placa['tipo_id']    = $tipoNuevo[(int) $placa['tipo_id']];
                $placa['impresa_en'] = null;
                $this->placas->insert($placa);
            }
        }
        $db->transComplete();

        return redirect()->to(site_url('piezas/tiradas/' . $id))
            ->with('success', $origen ? 'Tirada creada como copia de «' . $origen['nombre'] . '», sin nada impreso.' : 'Tirada creada. Define sus tipos de pieza y sus placas.');
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
            'tipos'    => $this->tipos->deTirada($id),
            'placas'   => $this->placas->deTirada($id),
            'hoy'      => PiezaTiradaModel::diaDeHoy($tirada),
            'otras'    => $this->tiradas->where('id !=', $id)->orderBy('id', 'DESC')->findAll(),
        ]);
    }

    /** Datos generales (modal "Ajustes"): nombre, fechas, objetivo, máquinas, notas. */
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
            'objetivo_h'    => max(0, (int) $this->request->getPost('objetivo_h')),
            'objetivo_m'    => max(0, (int) $this->request->getPost('objetivo_m')),
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
     * Tabla entera de tipos (modal "Tipos de pieza"): las filas con id se
     * actualizan, las nuevas se crean y las que faltan se borran — salvo
     * que todavía tengan placas, que se quedan con aviso.
     */
    public function guardarTipos(int $id)
    {
        if (!$this->tiradas->find($id)) {
            return redirect()->to(site_url('piezas/tiradas'))->with('error', 'Esa tirada no existe.');
        }

        $existentes = array_column($this->tipos->deTirada($id), null, 'id');
        $vistos     = [];
        $orden      = 0;
        foreach ((array) $this->request->getPost('tipos') as $fila) {
            $nombre = trim((string) ($fila['nombre'] ?? ''));
            if ($nombre === '') {
                continue;
            }
            $datos = [
                'tirada_id'  => $id,
                'nombre'     => mb_substr($nombre, 0, 80),
                'resina'     => mb_strtolower(trim((string) ($fila['resina'] ?? ''))),
                'por_placa'  => max(1, (int) ($fila['por_placa'] ?? 1)),
                'por_muneco' => max(1, (int) ($fila['por_muneco'] ?? 1)),
                'sexuado'    => empty($fila['sexuado']) ? 0 : 1,
                'bloqueado'  => empty($fila['bloqueado']) ? 0 : 1,
                'color'      => preg_match('/^#[0-9a-f]{6}$/i', (string) ($fila['color'] ?? '')) ? $fila['color'] : '#9aa5b3',
                'orden'      => $orden++,
            ];
            $tipoId = (int) ($fila['id'] ?? 0);
            if ($tipoId && isset($existentes[$tipoId])) {
                $this->tipos->update($tipoId, $datos);
                $vistos[$tipoId] = true;
            } else {
                $this->tipos->insert($datos);
            }
        }

        $conPlacas = [];
        foreach (array_diff_key($existentes, $vistos) as $tipoId => $tipo) {
            if ($this->placas->where('tipo_id', $tipoId)->countAllResults() > 0) {
                $conPlacas[] = $tipo['nombre'];
            } else {
                $this->tipos->delete($tipoId);
            }
        }

        $volver = $this->volver($id);

        return $conPlacas
            ? $volver->with('error', 'No se borra ' . implode(', ', $conPlacas) . ': todavía tiene placas. Quítalas antes.')
            : $volver->with('success', 'Tipos guardados.');
    }

    /**
     * Tabla entera de placas (modal "Placas"), en orden de cola: mismo
     * criterio que los tipos. Las que ya existían conservan si están
     * impresas.
     */
    public function guardarPlacas(int $id)
    {
        if (!$this->tiradas->find($id)) {
            return redirect()->to(site_url('piezas/tiradas'))->with('error', 'Esa tirada no existe.');
        }

        $tipos      = array_column($this->tipos->deTirada($id), null, 'id');
        $existentes = array_column($this->placas->deTirada($id), null, 'id');
        $vistos     = [];
        $orden      = 0;
        foreach ((array) $this->request->getPost('placas') as $fila) {
            $codigo = trim((string) ($fila['codigo'] ?? ''));
            $tipo   = $tipos[(int) ($fila['tipo_id'] ?? 0)] ?? null;
            if ($codigo === '' || !$tipo) {
                continue;
            }
            $h = $tipo['sexuado'] ? max(0, (int) ($fila['hombres'] ?? 0)) : 0;
            $m = $tipo['sexuado'] ? max(0, (int) ($fila['mujeres'] ?? 0)) : 0;
            $datos = [
                'tirada_id' => $id,
                'tipo_id'   => (int) $tipo['id'],
                'codigo'    => mb_substr($codigo, 0, 20),
                'hombres'   => $h,
                'mujeres'   => $m,
                'unidades'  => $tipo['sexuado'] ? $h + $m : max(0, (int) ($fila['unidades'] ?? 0)),
                'motivo'    => trim((string) ($fila['motivo'] ?? '')),
                'orden'     => $orden++,
            ];
            $placaId = (int) ($fila['id'] ?? 0);
            if ($placaId && isset($existentes[$placaId])) {
                $this->placas->update($placaId, $datos);
                $vistos[$placaId] = true;
            } else {
                $this->placas->insert($datos);
            }
        }
        foreach (array_keys(array_diff_key($existentes, $vistos)) as $placaId) {
            $this->placas->delete($placaId);
        }

        return $this->volver($id)->with('success', 'Placas guardadas.');
    }

    /** Marcar / desmarcar una placa como impresa, por fetch() desde el panel. */
    public function alternarImpresa(int $placaId)
    {
        $placa = $this->placas->find($placaId);
        if (!$placa) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'mensaje' => 'Esa placa no existe.']);
        }

        $impresaEn = $placa['impresa_en'] === null ? date('Y-m-d H:i:s') : null;
        $this->placas->update($placaId, ['impresa_en' => $impresaEn]);

        return $this->response->setJSON(['ok' => true, 'impresa_en' => $impresaEn]);
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
