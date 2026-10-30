<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaEstucheModel;
use App\Models\PiezaFamiliaModel;
use App\Models\PiezaHuecoModel;
use App\Models\PiezaRenderModel;
use App\Models\PiezaSalidaDestinoModel;
use App\Models\PiezaSalidaLineaModel;
use App\Models\PiezaSalidaModel;
use App\Models\PiezaVarianteModel;
use App\Services\PiezaAlmacen;
use App\Services\PiezaImagenesPublicas;
use App\Services\PiezaInventario;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Albaranes de salida: una foto = un albarán. En borrador no tocan el
 * stock; confirmar crea un movimiento 'salida' por línea y reabrir los
 * borra. Ver App\Services\PiezaInventario.
 */
class SalidasController extends BaseController
{
    protected $helpers = ['url', 'form', 'piezas_imagenes'];

    private const MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private const ORIGENES = ['hueco' => 'Hueco', 'impresas' => 'Caja de impresas', 'pedido' => 'Caja de pedido'];

    private PiezaSalidaModel $salidas;
    private PiezaSalidaLineaModel $lineas;
    private PiezaSalidaDestinoModel $destinos;
    private PiezaInventario $inventario;
    private PiezaAlmacen $almacen;

    public function __construct()
    {
        $this->salidas    = new PiezaSalidaModel();
        $this->lineas     = new PiezaSalidaLineaModel();
        $this->destinos   = new PiezaSalidaDestinoModel();
        $this->inventario = new PiezaInventario();
        $this->almacen    = new PiezaAlmacen();
    }

    public function index()
    {
        $salidas = $this->salidas->orderBy('fecha', 'DESC')->orderBy('id', 'DESC')->findAll(200);

        $resumen = [];
        if ($salidas !== []) {
            $filas = $this->lineas->select('salida_id, COUNT(*) AS lineas, SUM(cantidad) AS uds')
                ->whereIn('salida_id', array_column($salidas, 'id'))->groupBy('salida_id')->findAll();
            $resumen = array_column($filas, null, 'salida_id');
        }

        $usos = array_column($this->salidas->select('destino_id, COUNT(*) AS n')->where('destino_id IS NOT NULL', null, false)
            ->groupBy('destino_id')->findAll(), 'n', 'destino_id');

        return view('piezas/salidas/index', [
            'salidas'  => $salidas,
            'resumen'  => $resumen,
            'destinos' => $this->destinos->lista(),
            'usos'     => $usos,
        ]);
    }

    public function crear()
    {
        $foto = $this->request->getFile('foto');
        $tieneFoto = $foto && $foto->getError() !== UPLOAD_ERR_NO_FILE;
        if ($tieneFoto && ($error = $this->errorFoto($foto))) {
            return redirect()->back()->with('error', $error);
        }

        $ahora = date('Y-m-d H:i:s');
        $id = (int) $this->salidas->insert([
            'fecha'          => $this->request->getPost('fecha') ?: date('Y-m-d'),
            'destino_id'     => (int) $this->request->getPost('destino_id') ?: null,
            'estado'         => 'borrador',
            'nota'           => trim((string) $this->request->getPost('nota')) ?: null,
            'creado_en'      => $ahora,
            'actualizado_en' => $ahora,
        ], true);

        if ($tieneFoto) {
            $this->guardarFoto($id, $foto);
        }

        return redirect()->to(site_url('piezas/salidas/' . $id))->with('success', 'Albarán creado.');
    }

    public function ver(int $id)
    {
        $salida = $this->salidas->find($id);
        if (!$salida) {
            return redirect()->to(site_url('piezas/salidas'))->with('error', 'Ese albarán no existe.');
        }

        $lineas = $this->lineas->where('salida_id', $id)->orderBy('orden')->orderBy('id')->findAll();
        $ids = array_values(array_filter(array_map(static fn ($l) => (int) $l['variante_id'], $lineas)));

        $nombres = $this->nombres($ids);
        $imagenes = $this->miniaturas($ids);
        $codigos = $this->codigosDeHuecos();
        $variantes = $ids === [] ? [] : array_column((new PiezaVarianteModel())->whereIn('id', $ids)->findAll(), null, 'id');

        foreach ($lineas as &$l) {
            $vid = (int) $l['variante_id'];
            $huecoId = (int) ($variantes[$vid]['hueco_predeterminado_id'] ?? 0);
            $l['nombre'] = $vid ? ($nombres[$vid] ?? '?') : ($l['descripcion'] ?? '—');
            $l['imagen'] = $imagenes[$vid] ?? null;
            $l['hueco']  = $huecoId > 0 ? ($codigos[$huecoId] ?? '?') : null;
        }
        unset($l);

        return view('piezas/salidas/ver', [
            'salida'   => $salida,
            'lineas'   => $lineas,
            'cajas'    => $salida['estado'] === 'borrador' ? $this->disponibleEnCajas($id) : [],
            'destinos' => $this->destinos->lista(),
            'origenes' => self::ORIGENES,
            'foto'     => !empty($salida['ruta_imagen']) ? [
                'vista'    => imagen_pieza($salida, 'salidas', PiezaImagenesPublicas::VISTA),
                'original' => site_url('piezas/salidas/' . $id . '/imagen'),
            ] : null,
        ]);
    }

    public function actualizar(int $id)
    {
        $salida = $this->salidas->find($id);
        if (!$salida) {
            return redirect()->to(site_url('piezas/salidas'));
        }
        $volver = site_url('piezas/salidas/' . $id);

        $foto = $this->request->getFile('foto');
        if ($foto && $foto->getError() !== UPLOAD_ERR_NO_FILE) {
            if ($error = $this->errorFoto($foto)) {
                return redirect()->to($volver)->with('error', $error);
            }
            $this->quitarFoto($salida);
            $this->guardarFoto($id, $foto);
        }

        $this->salidas->update($id, [
            'fecha'          => $this->request->getPost('fecha') ?: $salida['fecha'],
            'destino_id'     => (int) $this->request->getPost('destino_id') ?: null,
            'nota'           => trim((string) $this->request->getPost('nota')) ?: null,
            'actualizado_en' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to($volver)->with('success', 'Albarán guardado.');
    }

    /** Buscador de piezas para añadir líneas: dice dónde hay unidades de cada una. */
    public function buscar()
    {
        $q = trim((string) $this->request->getGet('q'));
        if (mb_strlen($q) < 2) {
            return $this->response->setJSON(['resultados' => []]);
        }

        $familias = new PiezaFamiliaModel();
        $idsFamilia = array_column($familias->where('borrado_en', null)->like('nombre', $q)->findAll(), 'id') ?: [0];
        $variantes = (new PiezaVarianteModel())->where('borrado_en', null)
            ->groupStart()->like('nombre', $q)->orLike('sku', $q)->orWhereIn('familia_id', $idsFamilia)->groupEnd()
            ->orderBy('nombre')->findAll(30);

        $ids = array_map(static fn ($v) => (int) $v['id'], $variantes);
        $nombres = $this->nombres($ids);
        $codigos = $this->codigosDeHuecos();

        $resultados = [];
        foreach ($variantes as $v) {
            $vid = (int) $v['id'];
            $huecoId = (int) ($v['hueco_predeterminado_id'] ?? 0);
            $resultados[] = [
                'id'     => $vid,
                'nombre' => $nombres[$vid] ?? $v['nombre'],
                'hueco'  => $huecoId > 0 ? ($codigos[$huecoId] ?? '?') : null,
                'hay'    => $this->inventario->dondeHay($vid),
            ];
        }
        usort($resultados, static fn ($a, $b) => (array_sum($b['hay']) > 0) <=> (array_sum($a['hay']) > 0));

        return $this->response->setJSON(['resultados' => array_slice($resultados, 0, 15)]);
    }

    /** Las piezas que viven en un hueco, por su código ("E1H2"). */
    public function porHueco()
    {
        $codigo = strtoupper(preg_replace('/\s+/', '', (string) $this->request->getGet('codigo')));
        $huecoId = array_search($codigo, array_map(static fn ($c) => strtoupper(preg_replace('/\s+/', '', $c)), $this->codigosDeHuecos()), true);
        if ($huecoId === false) {
            return $this->response->setJSON(['resultados' => [], 'aviso' => 'No hay ningún hueco ' . $codigo . '.']);
        }

        $variantes = $this->inventario->variantesDeHueco((int) $huecoId);
        $nombres = $this->nombres(array_map(static fn ($v) => (int) $v['id'], $variantes));
        $resultados = [];
        foreach ($variantes as $v) {
            $vid = (int) $v['id'];
            $resultados[] = ['id' => $vid, 'nombre' => $nombres[$vid] ?? $v['nombre'], 'hueco' => $codigo, 'hay' => $this->inventario->dondeHay($vid)];
        }

        return $this->response->setJSON(['resultados' => $resultados, 'aviso' => 'El hueco ' . $codigo . ' está vacío.']);
    }

    public function crearLinea(int $id)
    {
        $volver = site_url('piezas/salidas/' . $id);
        $salida = $this->salidas->find($id);
        if (!$salida || $salida['estado'] !== 'borrador') {
            return redirect()->to($volver)->with('error', 'Solo se añaden líneas a un albarán en borrador.');
        }

        $varianteId = (int) $this->request->getPost('variante_id');
        $cantidad   = (int) $this->request->getPost('cantidad');
        $origen     = (string) $this->request->getPost('origen');
        if ($varianteId < 1 || $cantidad < 1 || !isset(self::ORIGENES[$origen])) {
            return redirect()->to($volver)->with('error', 'Elige pieza, cantidad y de dónde sale.');
        }

        $existente = $this->lineas->where('salida_id', $id)->where('variante_id', $varianteId)->where('origen', $origen)->first();
        if ($existente) {
            $this->lineas->update($existente['id'], ['cantidad' => (int) $existente['cantidad'] + $cantidad]);
        } else {
            $orden = (int) ($this->lineas->selectMax('orden')->where('salida_id', $id)->first()['orden'] ?? 0) + 1;
            $this->lineas->insert(['salida_id' => $id, 'variante_id' => $varianteId, 'cantidad' => $cantidad, 'origen' => $origen, 'orden' => $orden]);
        }

        $hay = $this->inventario->dondeHay($varianteId)[$origen];
        $aviso = $cantidad > $hay ? ' Ojo: en ' . mb_strtolower(self::ORIGENES[$origen]) . ' solo constan ' . $hay . '.' : '';

        return redirect()->to($volver)->with('success', 'Línea añadida.' . $aviso);
    }

    /** Añade al albarán todo lo que queda disponible en una caja. */
    public function crearLineasCaja(int $id)
    {
        $volver = site_url('piezas/salidas/' . $id);
        $salida = $this->salidas->find($id);
        $caja   = (string) $this->request->getPost('caja');
        if (!$salida || $salida['estado'] !== 'borrador' || !in_array($caja, ['impresas', 'pedido'], true)) {
            return redirect()->to($volver)->with('error', 'Solo se añaden líneas a un albarán en borrador.');
        }

        $n = 0;
        foreach ($this->disponibleEnCajas($id)[$caja] ?? [] as $p) {
            $existente = $this->lineas->where('salida_id', $id)->where('variante_id', $p['id'])->where('origen', $caja)->first();
            if ($existente) {
                $this->lineas->update($existente['id'], ['cantidad' => (int) $existente['cantidad'] + $p['cantidad']]);
            } else {
                $orden = (int) ($this->lineas->selectMax('orden')->where('salida_id', $id)->first()['orden'] ?? 0) + 1;
                $this->lineas->insert(['salida_id' => $id, 'variante_id' => $p['id'], 'cantidad' => $p['cantidad'], 'origen' => $caja, 'orden' => $orden]);
            }
            $n += $p['cantidad'];
        }

        return redirect()->to($volver)->with('success', $n . ' ud. de la caja de ' . $caja . ' añadidas.');
    }

    public function borrarLinea(int $lineaId)
    {
        $linea  = $this->lineas->find($lineaId);
        $salida = $linea ? $this->salidas->find($linea['salida_id']) : null;
        if (!$salida) {
            return redirect()->to(site_url('piezas/salidas'));
        }
        $volver = site_url('piezas/salidas/' . $salida['id']);
        if ($salida['estado'] !== 'borrador') {
            return redirect()->to($volver)->with('error', 'Reabre el albarán para cambiar sus líneas.');
        }

        $this->lineas->delete($lineaId);

        return redirect()->to($volver)->with('success', 'Línea quitada.');
    }

    public function confirmar(int $id)
    {
        $error = $this->inventario->confirmarSalida($id);

        return redirect()->to(site_url('piezas/salidas/' . $id))
            ->with($error ? 'error' : 'success', $error ?? 'Albarán confirmado: las piezas salen del inventario.');
    }

    public function reabrir(int $id)
    {
        $salida = $this->salidas->find($id);
        if (!$salida || $salida['estado'] !== 'confirmada') {
            return redirect()->to(site_url('piezas/salidas/' . $id))->with('error', 'Ese albarán no está confirmado.');
        }

        $this->inventario->reabrirSalida($id);

        return redirect()->to(site_url('piezas/salidas/' . $id))->with('success', 'Albarán reabierto: las piezas vuelven a su sitio.');
    }

    public function borrar(int $id)
    {
        $salida = $this->salidas->find($id);
        if (!$salida || $salida['estado'] !== 'borrador') {
            return redirect()->to(site_url('piezas/salidas/' . $id))->with('error', 'Solo se borra un albarán en borrador; reábrelo antes.');
        }

        $this->quitarFoto($salida);
        $this->salidas->delete($id);

        return redirect()->to(site_url('piezas/salidas'))->with('success', 'Albarán borrado.');
    }

    public function crearDestino()
    {
        $nombre = trim((string) $this->request->getPost('nombre'));
        if ($nombre === '') {
            return redirect()->back()->with('error', 'Escribe el nombre del destino.');
        }
        $orden = (int) ($this->destinos->selectMax('orden')->first()['orden'] ?? 0) + 1;
        $this->destinos->insert(['nombre' => mb_substr($nombre, 0, 60), 'orden' => $orden]);

        return redirect()->back()->with('success', 'Destino «' . $nombre . '» añadido.');
    }

    public function renombrarDestino(int $id)
    {
        $nombre = trim((string) $this->request->getPost('nombre'));
        if ($nombre === '' || !$this->destinos->find($id)) {
            return redirect()->to(site_url('piezas/salidas'))->with('error', 'Escribe el nombre del destino.');
        }
        $this->destinos->update($id, ['nombre' => mb_substr($nombre, 0, 60)]);

        return redirect()->to(site_url('piezas/salidas'))->with('success', 'Destino renombrado a «' . $nombre . '».');
    }

    public function borrarDestino(int $id)
    {
        $destino = $this->destinos->find($id);
        if (!$destino) {
            return redirect()->to(site_url('piezas/salidas'));
        }
        $this->destinos->delete($id);

        return redirect()->to(site_url('piezas/salidas'))->with('success', 'Destino «' . $destino['nombre'] . '» borrado.');
    }

    public function imagen(int $id)
    {
        $salida = $this->salidas->find($id);
        if (!$salida || !$this->almacen->existe($salida['ruta_imagen'] ?? null)) {
            throw PageNotFoundException::forPageNotFound();
        }

        session()->close();
        header_remove('Expires');
        header_remove('Pragma');
        $ruta = $this->almacen->absoluta($salida['ruta_imagen']);
        $mime = array_search(pathinfo($ruta, PATHINFO_EXTENSION), self::MIMES, true) ?: 'application/octet-stream';

        return $this->response->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'private, max-age=31536000, immutable')
            ->setBody(file_get_contents($ruta));
    }

    private function errorFoto($foto): ?string
    {
        if (!$foto->isValid() || $foto->hasMoved()) {
            return 'No ha llegado una foto válida.';
        }
        if ($foto->getSize() > 20 * 1024 * 1024) {
            return 'La foto pesa más de 20 MB.';
        }

        return isset(self::MIMES[$foto->getMimeType()]) ? null : 'Formato no admitido. Solo JPEG, PNG o WEBP.';
    }

    private function guardarFoto(int $id, $foto): void
    {
        $ruta = $this->almacen->rutaSalidaImagen($id, self::MIMES[$foto->getMimeType()]);
        $this->almacen->guardar($foto->getTempName(), $ruta);
        $hash = $this->almacen->hash($ruta);
        $this->salidas->update($id, [
            'ruta_imagen'  => $ruta,
            'hash_imagen'  => $hash,
            'tamano_bytes' => filesize($this->almacen->absoluta($ruta)),
        ]);
        try {
            (new PiezaImagenesPublicas())->publicar($this->almacen->absoluta($ruta), $hash);
        } catch (\Throwable $e) {
            log_message('warning', 'Albarán ' . $id . ': no se pudo publicar la copia de la foto: ' . $e->getMessage());
        }
    }

    private function quitarFoto(array $salida): void
    {
        if (empty($salida['ruta_imagen'])) {
            return;
        }
        $this->almacen->aPapelera($salida['ruta_imagen']);
        (new PiezaImagenesPublicas())->retirar($salida['hash_imagen'] ?? null);
    }

    /**
     * Lo que hay en cada caja menos lo que ya lleva este albarán.
     *
     * @return array<string, list<array{id:int, nombre:string, cantidad:int, imagen:?string}>>
     */
    private function disponibleEnCajas(int $salidaId): array
    {
        $enCajas = $this->inventario->stockEnCajas();
        foreach ($this->lineas->where('salida_id', $salidaId)->whereIn('origen', ['impresas', 'pedido'])->findAll() as $l) {
            if (isset($enCajas[$l['origen']][(int) $l['variante_id']])) {
                $enCajas[$l['origen']][(int) $l['variante_id']] -= (int) $l['cantidad'];
            }
        }

        $ids = [];
        foreach ($enCajas as $porVariante) {
            $ids += $porVariante;
        }
        $nombres  = $this->nombres(array_keys($ids));
        $imagenes = $this->miniaturas(array_keys($ids));

        $cajas = [];
        foreach (['impresas', 'pedido'] as $caja) {
            $cajas[$caja] = [];
            foreach ($enCajas[$caja] ?? [] as $vid => $n) {
                if ($n > 0 && isset($nombres[$vid])) {
                    $cajas[$caja][] = ['id' => $vid, 'nombre' => $nombres[$vid], 'cantidad' => $n, 'imagen' => $imagenes[$vid] ?? null];
                }
            }
            usort($cajas[$caja], static fn ($a, $b) => $a['nombre'] <=> $b['nombre']);
        }

        return $cajas;
    }

    /** @return array<int,string> */
    private function nombres(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $variantes = (new PiezaVarianteModel())->whereIn('id', $ids)->findAll();
        $familias = array_column((new PiezaFamiliaModel())->whereIn('id', array_unique(array_column($variantes, 'familia_id')) ?: [0])->findAll(), 'nombre', 'id');
        $nombres = [];
        foreach ($variantes as $v) {
            $nombres[(int) $v['id']] = trim(($familias[$v['familia_id']] ?? '') . ' ' . $v['nombre']);
        }

        return $nombres;
    }

    /** @return array<int,string> */
    private function miniaturas(array $ids): array
    {
        $imagenes = [];
        if ($ids === []) {
            return $imagenes;
        }
        foreach ((new PiezaRenderModel())->whereIn('variante_id', $ids)->orderBy('subida_en', 'DESC')->findAll() as $r) {
            $vid = (int) $r['variante_id'];
            if (!isset($imagenes[$vid]) && (!empty($r['ruta_imagen']) || !empty($r['hash_imagen']))) {
                $imagenes[$vid] = imagen_pieza($r, 'render', 't');
            }
        }

        return $imagenes;
    }

    /** @return array<int,string> */
    private function codigosDeHuecos(): array
    {
        $huecos = new PiezaHuecoModel();
        $codigos = [];
        foreach ((new PiezaEstucheModel())->ordenados() as $e) {
            foreach ($huecos->deEstuche((int) $e['id']) as $h) {
                $codigos[(int) $h['id']] = PiezaHuecoModel::codigoCompleto($e, $h);
            }
        }

        return $codigos;
    }
}
