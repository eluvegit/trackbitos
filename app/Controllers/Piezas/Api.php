<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaDescargaModel;
use App\Models\PiezaMaquinaModel;
use App\Models\PiezaRamaModel;
use App\Models\PiezaSesionModel;
use App\Models\PiezaVarianteModel;
use App\Models\PiezaVersionModel;

/**
 * Endpoints que consume piezas-cli/trackbitos.py. Todo responde JSON, sin
 * vistas. Autenticación: Bearer token único (filtro 'piezasApi'), no
 * Myth\Auth — ver App\Filters\PiezasApiAuth. Fase 4: solo lectura
 * (/variantes, /variante/{id}/estado) + alta de máquina; los verbos de
 * escritura (sesión, subida, promocionar...) llegan en la fase 5.
 */
class Api extends BaseController
{
    private PiezaVarianteModel $varianteModel;
    private PiezaVersionModel $versionModel;
    private PiezaRamaModel $ramaModel;
    private PiezaSesionModel $sesionModel;
    private PiezaDescargaModel $descargaModel;
    private PiezaMaquinaModel $maquinaModel;

    public function __construct()
    {
        $this->varianteModel = new PiezaVarianteModel();
        $this->versionModel  = new PiezaVersionModel();
        $this->ramaModel     = new PiezaRamaModel();
        $this->sesionModel   = new PiezaSesionModel();
        $this->descargaModel = new PiezaDescargaModel();
        $this->maquinaModel  = new PiezaMaquinaModel();
    }

    /**
     * Alta o "ping" de máquina. El uuid lo genera y guarda el cliente en su
     * primer arranque; hostname/so solo proponen un nombre por defecto.
     */
    public function registrarMaquina()
    {
        $uuid = trim((string) $this->request->getJsonVar('uuid'));
        if ($uuid === '') {
            return $this->response->setJSON(['error' => 'uuid es obligatorio'])->setStatusCode(422);
        }

        $hostname = $this->request->getJsonVar('hostname');
        $so       = $this->request->getJsonVar('so');

        $maquina = $this->maquinaModel->registrar(
            $uuid,
            $hostname !== null ? (string) $hostname : null,
            $so !== null ? (string) $so : null
        );

        return $this->response->setJSON($maquina);
    }

    /**
     * Lista de variantes con estado resumido (spec 7.1: de un vistazo, cuál
     * es la buena y dónde está el trabajo en curso).
     */
    public function variantes()
    {
        $variantes = $this->varianteModel->orderBy('nombre', 'ASC')->findAll();

        return $this->response->setJSON([
            'variantes' => array_map(fn($v) => $this->resumenVariante($v), $variantes),
        ]);
    }

    /**
     * Estado completo de una variante: rama abierta, hash de la nube
     * (última sesión subida en esa rama), bloqueo de máquina y descargas
     * pendientes. Es lo que consume "trackbitos estado" para las cuatro
     * filas de la tabla 4.3 — hash_nube es el campo clave.
     */
    public function varianteEstado(int $id)
    {
        $variante = $this->varianteModel->find($id);
        if (!$variante) {
            return $this->response->setJSON(['error' => 'Variante no encontrada.'])->setStatusCode(404);
        }

        $rama = $this->ramaModel->abiertaDe($id);
        $ultimaSubida = $rama ? $this->sesionModel->ultimaSubida((int) $rama['id']) : null;
        $sesionAbierta = $rama
            ? $this->sesionModel->where('rama_id', $rama['id'])->where('cerrada_en', null)->first()
            : null;

        return $this->response->setJSON([
            'variante_id' => (int) $variante['id'],
            'variante'    => $variante['nombre'],
            'rama'        => $rama ? [
                'id'         => (int) $rama['id'],
                'nombre'     => $this->ramaModel->nombre($rama),
                'abierta_en' => $rama['abierta_en'],
            ] : null,
            'hash_nube'            => $ultimaSubida['hash_blend'] ?? null,
            'ultima_sesion_subida' => $ultimaSubida ? [
                'id'         => (int) $ultimaSubida['id'],
                'numero'     => (int) $ultimaSubida['numero'],
                'subida_en'  => $ultimaSubida['subida_en'],
                'maquina_id' => (int) $ultimaSubida['maquina_id'],
            ] : null,
            'sesion_abierta' => $sesionAbierta ? [
                'id'             => (int) $sesionAbierta['id'],
                'numero'         => (int) $sesionAbierta['numero'],
                'maquina_id'     => (int) $sesionAbierta['maquina_id'],
                'maquina_nombre' => $this->nombreMaquina((int) $sesionAbierta['maquina_id']),
                'abierta_en'     => $sesionAbierta['abierta_en'],
            ] : null,
            'descargas_pendientes' => array_map(fn($d) => [
                'id'             => (int) $d['id'],
                'maquina_id'     => (int) $d['maquina_id'],
                'maquina_nombre' => $this->nombreMaquina((int) $d['maquina_id']),
                'motivo'         => $d['motivo'],
                'descargado_en'  => $d['descargado_en'],
            ], $this->descargaModel->abiertasParaVariante($id)),
        ]);
    }

    private function resumenVariante(array $variante): array
    {
        $validada = $this->versionModel
            ->where('variante_id', $variante['id'])
            ->where('estado', 'validada')
            ->first();

        $rama = $this->ramaModel->abiertaDe((int) $variante['id']);
        $sesionAbierta = $rama
            ? $this->sesionModel->where('rama_id', $rama['id'])->where('cerrada_en', null)->first()
            : null;

        return [
            'id'                    => (int) $variante['id'],
            'nombre'                => $variante['nombre'],
            'familia_id'            => (int) $variante['familia_id'],
            'version_validada'      => $validada ? [
                'id'     => (int) $validada['id'],
                'numero' => (int) $validada['numero'],
            ] : null,
            'rama_abierta'          => $rama !== null,
            'sesion_abierta'        => $sesionAbierta !== null,
            'descargas_pendientes'  => count($this->descargaModel->abiertasParaVariante((int) $variante['id'])),
        ];
    }

    private function nombreMaquina(int $maquinaId): ?string
    {
        $maquina = $this->maquinaModel->find($maquinaId);

        return $maquina['nombre'] ?? null;
    }
}
