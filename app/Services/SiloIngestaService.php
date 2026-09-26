<?php

namespace App\Services;

use App\Models\SiloFicheroModel;
use App\Models\SiloPiezaAtributoModel;
use App\Models\SiloPiezaModel;
use App\Models\SiloUbicacionModel;

/**
 * Ingesta de una carpeta ya escaneada de una unidad Maestro: recibe el
 * nombre de carpeta TAL CUAL está en disco (ya lleva su ID de negocio,
 * porque ya se creó antes) y su listado de ficheros, y da de alta
 * pieza + ficheros + ubicación — sin que un humano teclee clasificación
 * alguna (los proxies de previsualización los genera aparte
 * Agente::escaneo() / silo-agente/agente.py, ver
 * SiloProxyModel::tieneProxiesReales()). Esto es lo que hará la API real al
 * escanear una unidad Maestro de verdad (plan Silo §7.1/§9); separado de
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
    private SiloUbicacionModel $ubicacionModel;

    private const EXTENSIONES_FOTO  = ['jpg', 'jpeg', 'jpe', 'png', 'bmp', 'tif', 'tiff', 'heic', 'raw', 'cr2', 'nef'];
    private const EXTENSIONES_VIDEO = ['mp4', 'mov', 'avi', 'mkv', 'mpg', 'mpeg', 'm4v', 'wmv', 'webm', '3gp', 'mts', 'm2ts', 'flv'];

    public function __construct()
    {
        $this->silo           = new SiloService();
        $this->propagacion    = new SiloPropagacionService();
        $this->piezaModel     = new SiloPiezaModel();
        $this->atributoModel  = new SiloPiezaAtributoModel();
        $this->ficheroModel   = new SiloFicheroModel();
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

        // Contrato de "campos fijos" (docs/silo-ingesta-propagacion.md):
        // posición 1 tras la categoría = tema, posición 2 = lugar, el resto
        // = personas — así se clasifica sola sin que nadie etiquete nada a
        // mano (plan Silo, petición 2026-09-05).
        $clasificacion = $this->silo->clasificarElementosPorPosicion($parseado['elementos']);
        $atributoIds   = [];
        if ($clasificacion['tema'] !== null) {
            $atributoIds[] = $this->silo->getOrCreateVocabulario('tema', $clasificacion['tema'])['id'];
        }
        if ($clasificacion['lugar'] !== null) {
            $atributoIds[] = $this->silo->getOrCreateVocabulario('lugar', $clasificacion['lugar'])['id'];
        }
        foreach ($clasificacion['personas'] as $persona) {
            $atributoIds[] = $this->silo->getOrCreateVocabulario('persona', $persona)['id'];
        }

        $existente = $this->piezaModel->where('id_negocio', $parseado['id_negocio'])->first();
        if ($existente) {
            $piezaId = (int) $existente['id'];

            // Reingesta de una pieza ya conocida (rescaneo normal del
            // Maestro): sin manifiesto/hash todavía (N1-N3, pendiente) no
            // hay forma barata de saber qué cambió, así que se sustituye la
            // lista de ficheros entera en vez de acumular duplicados en cada
            // pasada. Los proxies NO se tocan aquí (generarlos con ffmpeg es
            // caro): sobreviven a la reingesta con `fichero_id` a NULL
            // (FK -> SET NULL) hasta que el agente los regenere de verdad
            // (Agente::escaneo() decide cuándo hace falta, ver
            // SiloProxyModel::tieneProxiesReales()).
            $this->ficheroModel->where('pieza_id', $piezaId)->delete();

            // El nombre de carpeta es la fuente de verdad: si se renombró
            // para corregir la clasificación (o para adoptar el contrato de
            // campos fijos), la categoría también se recalcula aquí, igual
            // criterio que los ficheros.
            $this->piezaModel->update($piezaId, ['categoria_id' => $categoriaId]);
        } else {
            $piezaId = $this->piezaModel->insert([
                'id_negocio'     => $parseado['id_negocio'],
                'fecha'          => $parseado['fecha'],
                'categoria_id'   => $categoriaId,
                'nombre_carpeta' => $nombreCarpeta,
            ], true);
        }

        $this->atributoModel->reemplazarDeLaPieza($piezaId, $atributoIds);

        foreach ($ficheros as $f) {
            $this->ficheroModel->insert([
                'pieza_id'     => $piezaId,
                'nombre'       => $f['nombre'],
                'tipo'         => self::tipoDeExtension($f['nombre']),
                'tamano_bytes' => $f['tamano_bytes'] ?? null,
                'hash'         => $f['hash'] ?? null,
            ]);
        }

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

    /**
     * `foto` / `video` / `otro` según la extensión del fichero. Público y
     * estático para que `spark silo:reclasificar-ficheros` reetiquete lo ya
     * ingestado cuando se amplía la lista de extensiones (p. ej. .mpg).
     */
    public static function tipoDeExtension(string $nombre): string
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
}
