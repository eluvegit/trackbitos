<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaResinaModel;

class ResinasController extends BaseController
{
    private PiezaResinaModel $resinas;

    public function __construct()
    {
        $this->resinas = new PiezaResinaModel();
    }

    public function index()
    {
        $usos = [];
        foreach (db_connect()->table('piezas_placas')->select('resina_id, COUNT(*) n, MAX(creado_en) ultima')
            ->where('resina_id IS NOT NULL')->groupBy('resina_id')->get()->getResultArray() as $u) {
            $usos[(int) $u['resina_id']] = $u;
        }

        $nueva = $this->request->getGet('nueva');
        if ($nueva !== null) {
            session()->set('piezas_resinas_nueva', $nueva === '1');
        }
        return view((session('piezas_resinas_nueva') !== false ? 'piezas/resinas_nueva' : 'piezas/resinas'), [
            'resinas' => $this->resinas->orderBy('archivada')->orderBy('nombre')->findAll(),
            'usos'    => $usos,
            'enMaquina' => $this->resinas->enCadaImpresora(),
        ]);
    }

    public function crear()
    {
        $nombre = trim((string) $this->request->getPost('nombre'));
        if ($nombre === '') {
            return $this->volver('Ponle un nombre a la resina.', 'error');
        }
        $this->resinas->idPorNombre($nombre);

        return $this->volver('Resina «' . $nombre . '» lista para elegir en las placas.');
    }

    public function actualizar(int $id)
    {
        $resina = $this->resinas->find($id);
        if (!$resina) {
            return $this->volver('Esa resina ya no existe.', 'error');
        }
        $nombre = mb_substr(trim((string) $this->request->getPost('nombre')), 0, 120);
        if ($nombre === '') {
            return $this->volver('El nombre no puede quedar vacío.', 'error');
        }
        $this->resinas->update($id, [
            'nombre' => $nombre,
            'notas'  => trim((string) $this->request->getPost('notas')) ?: null,
        ]);
        if ($nombre !== $resina['nombre']) {
            db_connect()->table('piezas_placas')->where('resina_id', $id)->update(['resina' => $nombre]);
        }

        return $this->volver('Resina guardada.');
    }

    public function archivar(int $id)
    {
        $resina = $this->resinas->find($id);
        if ($resina) {
            $this->resinas->update($id, ['archivada' => $resina['archivada'] ? 0 : 1]);
        }

        return $this->volver($resina && !$resina['archivada'] ? 'Archivada: ya no sale en el selector de las placas nuevas.' : 'Vuelve a salir en el selector.');
    }

    public function borrar(int $id)
    {
        if (db_connect()->table('piezas_placas')->where('resina_id', $id)->countAllResults() > 0) {
            return $this->volver('Hay placas con esa resina: archívala en vez de borrarla.', 'error');
        }
        $this->resinas->delete($id);

        return $this->volver('Resina borrada.');
    }

    private function volver(string $mensaje, string $tipo = 'success')
    {
        return redirect()->to(site_url('piezas/resinas'))->with($tipo, $mensaje);
    }
}
