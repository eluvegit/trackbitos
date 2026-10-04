<?php

namespace App\Controllers;

use App\Models\GimnasioCatalogoModel;
use App\Models\GimnasioEjerciciosModel;
use App\Services\GimnasioCatalogoService;

/**
 * Catálogo externo de ejercicios (exercises-dataset). Es una biblioteca de
 * consulta: nada de aquí aparece en el registro de entrenamientos salvo que
 * se añada explícitamente a "mis ejercicios" o se vincule a uno propio.
 */
class GimnasioCatalogo extends BaseController
{
    protected GimnasioCatalogoModel $catalogo;
    protected GimnasioEjerciciosModel $ejercicios;

    public function __construct()
    {
        helper('gimnasio');
        $this->catalogo   = new GimnasioCatalogoModel();
        $this->ejercicios = new GimnasioEjerciciosModel();
    }

    public function index()
    {
        $filas = $this->catalogo
            ->select('id, nombre, parte, equipo, objetivo, imagen')
            ->orderBy('nombre')
            ->findAll();

        // catalogo_id => nombres de mis ejercicios vinculados
        $vinculos = [];
        foreach ($this->ejercicios->select('nombre, catalogo_id')->where('catalogo_id IS NOT NULL')->findAll() as $e) {
            $vinculos[$e['catalogo_id']][] = $e['nombre'];
        }

        return view('gimnasio/catalogo/index', [
            'filas'    => $filas,
            'vinculos' => $vinculos,
        ]);
    }

    public function ver($id)
    {
        $c = $this->catalogo->find($id);
        if (!$c) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Ejercicio de catálogo no encontrado');
        }

        return view('gimnasio/catalogo/ver', [
            'c'           => $c,
            'pasos'       => json_decode($c['pasos_es'] ?? '[]', true) ?: [],
            'vinculados'  => $this->ejercicios->where('catalogo_id', $id)->orderBy('nombre')->findAll(),
            'misEjercicios' => $this->ejercicios->select('id, nombre, grupo_muscular, catalogo_id')
                ->orderBy('grupo_muscular')->orderBy('nombre')->findAll(),
            'grupos'      => gim_grupos(),
            'grupoSugerido' => gim_catalogo_grupo_sugerido($c),
        ]);
    }

    /** Crea un ejercicio propio nuevo a partir de uno del catálogo. */
    public function adoptar($id)
    {
        $c = $this->catalogo->find($id);
        if (!$c) {
            return redirect()->to(site_url('gimnasio/catalogo'));
        }

        $nombre = trim((string) $this->request->getPost('nombre')) ?: $c['nombre'];
        $grupo  = (string) $this->request->getPost('grupo_muscular');
        if (!isset(gim_grupos()[$grupo])) {
            $grupo = gim_catalogo_grupo_sugerido($c);
        }

        $this->ejercicios->insert([
            'nombre'         => mb_substr($nombre, 0, 100),
            'grupo_muscular' => $grupo,
            'catalogo_id'    => (int) $id,
        ]);

        return redirect()->to(site_url('gimnasio/catalogo/' . $id))
            ->with('success', "Añadido a tus ejercicios como «{$nombre}».");
    }

    /** Vincula uno de mis ejercicios existentes a este del catálogo. */
    public function vincular($id)
    {
        $ejId   = (int) $this->request->getPost('ejercicio_id');
        $volver = $this->request->getPost('volver') ?: ('gimnasio/catalogo/' . $id);
        if ($ejId && $this->catalogo->find($id) && $this->ejercicios->find($ejId)) {
            $this->ejercicios->update($ejId, ['catalogo_id' => (int) $id]);
            return redirect()->to(site_url($volver))->with('success', 'Ejercicio vinculado.');
        }
        return redirect()->to(site_url($volver));
    }

    /** JSON para el selector "cambiar ejercicio del catálogo". */
    public function buscar()
    {
        $res = (new GimnasioCatalogoService())->buscar((string) $this->request->getGet('q'));
        return $this->response->setJSON(array_map(fn ($c) => [
            'id'     => (int) $c['id'],
            'nombre' => ucfirst($c['nombre']),
            'meta'   => gim_catalogo_etiqueta('objetivo', $c['objetivo']) . ' · ' . gim_catalogo_etiqueta('equipo', $c['equipo']),
            'imagen' => gim_catalogo_media($c['imagen']),
        ], $res));
    }

    /** Quita el vínculo de uno de mis ejercicios (no borra nada). */
    public function desvincular($ejercicioId)
    {
        $ej = $this->ejercicios->find($ejercicioId);
        if (!$ej) {
            return redirect()->to(site_url('gimnasio/catalogo'));
        }
        $this->ejercicios->update($ejercicioId, ['catalogo_id' => null]);

        $volver = $this->request->getPost('volver') ?: ('gimnasio/catalogo/' . $ej['catalogo_id']);
        return redirect()->to(site_url($volver))->with('success', 'Vínculo eliminado.');
    }

    /** Revisión en lote: sugerencias del catálogo para mis ejercicios sin vincular. */
    public function vincularLote()
    {
        return view('gimnasio/catalogo/vincular_lote', [
            'sugerencias' => (new GimnasioCatalogoService())->sugerencias(),
            'grupos'      => gim_grupos(),
        ]);
    }

    public function vincularLoteGuardar()
    {
        $n = 0;
        foreach ((array) $this->request->getPost('vincular') as $ejId => $catId) {
            $ejId = (int) $ejId;
            $catId = (int) $catId;
            if (!$ejId || !$catId || !$this->catalogo->find($catId)) {
                continue;
            }
            // Solo ejercicios aún sin vincular: el lote nunca pisa un vínculo hecho a mano
            $ej = $this->ejercicios->find($ejId);
            if ($ej && empty($ej['catalogo_id'])) {
                $this->ejercicios->update($ejId, ['catalogo_id' => $catId]);
                $n++;
            }
        }

        return redirect()->to(site_url('gimnasio/catalogo/vincular-lote'))
            ->with('success', "$n ejercicio" . ($n === 1 ? '' : 's') . ' vinculado' . ($n === 1 ? '' : 's') . '.');
    }

    /** Descarga e importa/actualiza el dataset desde GitHub (equivale a `spark gimnasio:catalogo`). */
    public function importar()
    {
        try {
            set_time_limit(300);
            $r = (new GimnasioCatalogoService())->importar();
            $msg = "Catálogo importado: {$r['nuevos']} nuevos, {$r['actualizados']} actualizados.";
            return redirect()->to(site_url('gimnasio/catalogo'))->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->to(site_url('gimnasio/catalogo'))->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }
}
