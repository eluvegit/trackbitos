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
     * `$ficheros` = null: el agente dice que la carpeta no cambió desde la
     * última sincronización (su manifiesto, N1/N2 — ver
     * docs/silo-ingesta-propagacion.md § "Detección de cambios") y no manda
     * la lista: se actualiza lo que sale del NOMBRE (fecha, categoría,
     * etiquetas, ruta) y los ficheros se quedan como estaban. Si la pieza
     * aún no existe no hay de dónde sacarlos: error, y el agente la volverá
     * a mandar completa en el siguiente escaneo.
     *
     * Devuelve la pieza con `_cambios` (nuevos/borrados/modificados, por
     * nombre de fichero) y `_multimedia_cambiada` (algún cambio en fotos o
     * vídeos: sus proxies ya no representan la carpeta).
     *
     * @param array<int, array{nombre: string, tamano_bytes?: int, mtime?: int, hash?: string}>|null $ficheros
     */
    public function ingestarCarpeta(int $unidadId, string $nombreCarpeta, ?array $ficheros): array
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
        if (!$existente && $ficheros === null) {
            throw new \RuntimeException('Carpeta nueva para la web pero el agente no mandó sus ficheros (la daba por sincronizada); se mandará completa en el próximo escaneo.');
        }

        if ($existente) {
            $piezaId = (int) $existente['id'];

            // El nombre de carpeta es la fuente de verdad: si se renombró
            // para corregir la clasificación (o para adoptar el contrato de
            // campos fijos), nombre, fecha y categoría se recalculan aquí,
            // igual criterio que los ficheros — y la ruta de su Copia 1 en
            // esta unidad, que es la carpeta que se acaba de escanear.
            $this->piezaModel->update($piezaId, [
                'nombre_carpeta' => $nombreCarpeta,
                'fecha'          => $parseado['fecha'],
                'categoria_id'   => $categoriaId,
            ]);
            $copia1 = $this->ubicacionModel
                ->where('pieza_id', $piezaId)->where('unidad_id', $unidadId)->where('copia', 1)
                ->first();
            if ($copia1 && $copia1['ruta_relativa'] !== $nombreCarpeta) {
                $this->ubicacionModel->update($copia1['id'], ['ruta_relativa' => $nombreCarpeta]);
            }
        } else {
            $piezaId = $this->piezaModel->insert([
                'id_negocio'     => $parseado['id_negocio'],
                'fecha'          => $parseado['fecha'],
                'categoria_id'   => $categoriaId,
                'nombre_carpeta' => $nombreCarpeta,
            ], true);
        }

        $this->atributoModel->reemplazarDeLaPieza($piezaId, $atributoIds);

        $cambios = ['nuevos' => [], 'borrados' => [], 'modificados' => []];
        if ($ficheros !== null) {
            $cambios = $this->sincronizarFicheros($piezaId, $ficheros);

            // Siempre que llegan ficheros (no solo si hubo cambios): una
            // carpeta que se quedó vacía también tiene que reflejarse a 0.
            $this->piezaModel->update($piezaId, ['tamano_bytes' => $this->ficheroModel->sumaTamano($piezaId)]);
        }

        if (!$existente) {
            $this->ubicacionModel->insert([
                'pieza_id'      => $piezaId,
                'unidad_id'     => $unidadId,
                'copia'         => 1,
                'ruta_relativa' => $nombreCarpeta,
            ]);
        }

        $this->propagacion->propagarPieza($piezaId);

        $pieza = $this->piezaModel->find($piezaId);
        $pieza['_nueva']   = !$existente;
        $pieza['_cambios'] = $cambios;
        $pieza['_multimedia_cambiada'] = false;
        foreach (array_merge(...array_values($cambios)) as $nombre) {
            if (self::tipoDeExtension($nombre) !== 'otro') {
                $pieza['_multimedia_cambiada'] = true;
                break;
            }
        }

        return $pieza;
    }

    /**
     * Deja `silo_ficheros` de la pieza igual que la lista del agente
     * tocando solo lo que cambió (así los ids — y el enlace de cada proxy a
     * su fichero — sobreviven a los reescaneos). Un fichero cuenta como
     * modificado si cambió de tamaño, o de hash cuando hay hash a los dos
     * lados; sin hashes, si cambió su mtime. Un mtime que la web aún no
     * tenía (filas de antes del manifiesto) no cuenta como cambio.
     *
     * @return array{nuevos: string[], borrados: string[], modificados: string[]}
     */
    private function sincronizarFicheros(int $piezaId, array $ficheros): array
    {
        $actuales = array_column($this->ficheroModel->where('pieza_id', $piezaId)->findAll(), null, 'nombre');
        $cambios  = ['nuevos' => [], 'borrados' => [], 'modificados' => []];
        $vistos   = [];

        foreach ($ficheros as $f) {
            $nombre = (string) $f['nombre'];
            $nuevo  = [
                'tamano_bytes' => isset($f['tamano_bytes']) ? (int) $f['tamano_bytes'] : null,
                'mtime'        => isset($f['mtime']) ? (int) $f['mtime'] : null,
                'hash'         => ($f['hash'] ?? '') !== '' ? (string) $f['hash'] : null,
            ];
            $vistos[$nombre] = true;

            $antes = $actuales[$nombre] ?? null;
            if ($antes === null) {
                $this->ficheroModel->insert($nuevo + [
                    'pieza_id' => $piezaId,
                    'nombre'   => $nombre,
                    'tipo'     => self::tipoDeExtension($nombre),
                ]);
                $cambios['nuevos'][] = $nombre;
                continue;
            }

            $tamanoAntes = $antes['tamano_bytes'] !== null ? (int) $antes['tamano_bytes'] : null;
            $mtimeAntes  = $antes['mtime'] !== null ? (int) $antes['mtime'] : null;

            $modificado = $tamanoAntes !== $nuevo['tamano_bytes'];
            if (!$modificado && $antes['hash'] !== null && $nuevo['hash'] !== null) {
                $modificado = $antes['hash'] !== $nuevo['hash'];
            } elseif (!$modificado && $mtimeAntes !== null && $nuevo['mtime'] !== null) {
                $modificado = $mtimeAntes !== $nuevo['mtime'];
            }
            if ($modificado) {
                $cambios['modificados'][] = $nombre;
            }

            // Un hash que el agente no mandó esta vez (no hizo falta
            // re-hashear) no borra el que ya había, salvo que el fichero
            // cambiara: entonces el viejo ya no vale.
            if ($nuevo['hash'] === null && !$modificado) {
                $nuevo['hash'] = $antes['hash'];
            }
            if ($nuevo['tamano_bytes'] !== $tamanoAntes || $nuevo['mtime'] !== $mtimeAntes || $nuevo['hash'] !== $antes['hash']) {
                $this->ficheroModel->update($antes['id'], $nuevo);
            }
        }

        foreach ($actuales as $nombre => $antes) {
            if (!isset($vistos[$nombre])) {
                $this->ficheroModel->delete($antes['id']);
                $cambios['borrados'][] = (string) $nombre;
            }
        }

        return $cambios;
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
