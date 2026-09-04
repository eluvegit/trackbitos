<?php

namespace App\Services;

use App\Models\SiloFicheroModel;
use App\Models\SiloPiezaAtributoModel;
use App\Models\SiloPiezaModel;
use App\Models\SiloProxyModel;
use App\Models\SiloUbicacionModel;

/**
 * Ingesta de una carpeta ya escaneada de una unidad Maestro: recibe el
 * nombre de carpeta TAL CUAL está en disco (ya lleva su ID de negocio,
 * porque ya se creó antes) y su listado de ficheros, y da de alta
 * pieza + ficheros + proxies simulados + ubicación — sin que un humano
 * teclee clasificación alguna. Simula lo que hará la API real cuando
 * escanee una unidad Maestro de verdad (plan Silo §7.1/§9); separado de
 * SiloService (que expone los helpers de dominio que aquí se reutilizan)
 * igual que PiezaSyncService está separado de PiezaService en Piezas.
 * Al terminar, dispara su propia propagación a Copia 2/3
 * (SiloPropagacionService) — la ingesta no termina hasta que la pieza
 * también queda destinada donde le corresponda (plan Silo §2).
 */
class SiloIngestaService
{
    private SiloService $silo;
    private SiloPropagacionService $propagacion;
    private SiloPiezaModel $piezaModel;
    private SiloPiezaAtributoModel $atributoModel;
    private SiloFicheroModel $ficheroModel;
    private SiloProxyModel $proxyModel;
    private SiloUbicacionModel $ubicacionModel;

    private const EXTENSIONES_FOTO  = ['jpg', 'jpeg', 'png', 'heic', 'raw', 'cr2', 'nef'];
    private const EXTENSIONES_VIDEO = ['mp4', 'mov', 'avi', 'mkv'];
    private const MAX_PROXIES_POR_TIPO = 3;

    public function __construct()
    {
        $this->silo           = new SiloService();
        $this->propagacion    = new SiloPropagacionService();
        $this->piezaModel     = new SiloPiezaModel();
        $this->atributoModel  = new SiloPiezaAtributoModel();
        $this->ficheroModel   = new SiloFicheroModel();
        $this->proxyModel     = new SiloProxyModel();
        $this->ubicacionModel = new SiloUbicacionModel();
    }

    /**
     * @param array<int, array{nombre: string, tamano_bytes?: int, hash?: string}> $ficheros
     */
    public function ingestarCarpeta(int $unidadId, string $nombreCarpeta, array $ficheros): array
    {
        $parseado = $this->silo->parsearNombreCarpeta($nombreCarpeta);

        $categoriaId = null;
        if ($parseado['categoria_texto'] !== null) {
            $categoriaId = $this->silo->getOrCreateVocabulario('categoria', $parseado['categoria_texto'])['id'];
        }

        $existente = $this->piezaModel->where('id_negocio', $parseado['id_negocio'])->first();
        if ($existente) {
            $piezaId = (int) $existente['id'];

            // Reingesta de una pieza ya conocida (rescaneo normal del
            // Maestro): sin manifiesto/hash todavía (N1-N3, pendiente) no
            // hay forma barata de saber qué cambió, así que se sustituye la
            // lista de ficheros entera en vez de acumular duplicados en
            // cada pasada. Los proxies simulados quedan huérfanos
            // (fichero_id -> SET NULL) y se regeneran también.
            $this->ficheroModel->where('pieza_id', $piezaId)->delete();
            $this->proxyModel->where('pieza_id', $piezaId)->delete();
        } else {
            $piezaId = $this->piezaModel->insert([
                'id_negocio'     => $parseado['id_negocio'],
                'fecha'          => $parseado['fecha'],
                'categoria_id'   => $categoriaId,
                'nombre_carpeta' => $nombreCarpeta,
            ], true);

            if (!empty($parseado['elementos'])) {
                $atributoIds = array_map(
                    fn ($texto) => $this->silo->getOrCreateVocabulario('tema', $texto)['id'],
                    $parseado['elementos']
                );
                $this->atributoModel->reemplazarDeLaPieza($piezaId, $atributoIds);
            }
        }

        $ficherosInsertados = [];
        foreach ($ficheros as $f) {
            $tipo = $this->tipoDeExtension($f['nombre']);
            $ficherosInsertados[] = [
                'id'   => $this->ficheroModel->insert([
                    'pieza_id'     => $piezaId,
                    'nombre'       => $f['nombre'],
                    'tipo'         => $tipo,
                    'tamano_bytes' => $f['tamano_bytes'] ?? null,
                    'hash'         => $f['hash'] ?? null,
                ], true),
                'tipo' => $tipo,
            ];
        }

        $this->generarProxiesSimulados($piezaId, $ficherosInsertados);

        // Siempre (no solo "si hay ficheros"): en un reingesta ya se
        // borraron los anteriores arriba, así que una carpeta que se quedó
        // vacía también tiene que reflejarse a 0, no quedarse con el
        // tamaño de la pasada previa.
        $this->piezaModel->update($piezaId, ['tamano_bytes' => $this->ficheroModel->sumaTamano($piezaId)]);

        if (!$existente) {
            $this->ubicacionModel->insert([
                'pieza_id'      => $piezaId,
                'unidad_id'     => $unidadId,
                'copia'         => 1,
                'ruta_relativa' => $nombreCarpeta,
            ]);
        }

        $this->propagacion->propagarPieza($piezaId);

        return $this->piezaModel->find($piezaId);
    }

    private function tipoDeExtension(string $nombre): string
    {
        $ext = strtolower((string) pathinfo($nombre, PATHINFO_EXTENSION));

        if (in_array($ext, self::EXTENSIONES_FOTO, true)) {
            return 'foto';
        }
        if (in_array($ext, self::EXTENSIONES_VIDEO, true)) {
            return 'video';
        }

        return 'otro';
    }

    /**
     * Hasta 3 fotos + 3 vídeos por carpeta, elegidos entre los ficheros
     * reales de ese tipo que se acaban de ingestar — simulados con un
     * placeholder determinista (mismo fichero = misma imagen si se
     * reingesta), a falta de la miniatura real que generará la API.
     */
    private function generarProxiesSimulados(int $piezaId, array $ficheros): void
    {
        foreach (['foto', 'video'] as $tipo) {
            $candidatos = array_values(array_filter($ficheros, fn ($f) => $f['tipo'] === $tipo));
            shuffle($candidatos);
            $elegidos = array_slice($candidatos, 0, self::MAX_PROXIES_POR_TIPO);

            foreach ($elegidos as $i => $f) {
                $seed = $piezaId . '-' . $f['id'];

                $this->proxyModel->insert([
                    'pieza_id'   => $piezaId,
                    'fichero_id' => $f['id'],
                    'tipo'       => $tipo,
                    'url'        => "https://picsum.photos/seed/{$seed}/320/200",
                    'orden'      => $i,
                ]);
            }
        }
    }
}
