<?php

namespace App\Services;

use RuntimeException;

/**
 * Dónde viven los .blend en el servidor (spec sección 8): bajo writable/,
 * fuera del directorio público — solo se sirven por la API autenticada.
 *
 * Los nombres se derivan de los IDs, nunca de texto libre del usuario: el
 * nombre de una variante puede cambiar o traer acentos y barras, y la ruta
 * quedaría rota o sería un agujero de path traversal.
 *
 * Sesión y versión guardan ficheros SEPARADOS aunque su contenido sea el
 * mismo al promocionar: las sesiones se purgan al validar (invariante 5) y
 * si la versión apuntase al fichero de la sesión, esa purga se llevaría por
 * delante el fichero de la versión, que es justo el que nunca debe perderse.
 */
class PiezaAlmacen
{
    private string $base;

    public function __construct(?string $base = null)
    {
        $this->base = rtrim($base ?? (WRITEPATH . 'piezas'), '/\\');
    }

    public function rutaSesion(int $varianteId, int $ramaId, int $sesionId): string
    {
        return "variante-{$varianteId}/rama-{$ramaId}/sesion-{$sesionId}.blend";
    }

    public function rutaVersion(int $varianteId, int $numero): string
    {
        return sprintf('variante-%d/version-v%03d.blend', $varianteId, $numero);
    }

    /**
     * Histórico de subidas dentro de una sesión (una por cada `subir`, no
     * solo la última): rutaSesion() es el fichero "vivo" que sigue sirviendo
     * las descargas de esa sesión; este es aparte, para que pisarlo con la
     * siguiente subida no se lleve por delante la copia anterior.
     */
    public function rutaSubida(int $varianteId, int $ramaId, int $sesionId, int $numero): string
    {
        return sprintf('variante-%d/rama-%d/sesion-%d/subida-%03d.blend', $varianteId, $ramaId, $sesionId, $numero);
    }

    /**
     * Un STL para imprimir, junto al .blend de la misma versión. Se adjunta
     * aparte (PiezaService::adjuntarStl), no al promocionar: el usuario
     * decide cuándo exportarlo, normalmente justo antes de imprimir.
     *
     * Lleva el id del STL porque una versión puede tener varios (fase 21):
     * los brazos por separado, o una pieza alta cortada en trozos. Sin él,
     * el segundo pisaría el fichero del primero.
     */
    public function rutaStl(int $varianteId, int $numero, int $stlId): string
    {
        return sprintf('variante-%d/version-v%03d-stl-%d.stl', $varianteId, $numero, $stlId);
    }

    public function rutaReferencia(int $familiaId, int $referenciaId, string $extension): string
    {
        return sprintf('familia-%d/referencia-%d.%s', $familiaId, $referenciaId, $extension);
    }

    /** Captura de la plataforma del laminador para una placa (fase 43). */
    public function rutaPlacaImagen(int $placaId, int $imagenId, string $extension): string
    {
        return sprintf('placa-%d/imagen-%d.%s', $placaId, $imagenId, $extension);
    }

    /** Captura de cómo quedó una pieza concreta dentro de una placa. */
    public function rutaPlacaVersionImagen(int $placaVersionId, int $imagenId, string $extension): string
    {
        return sprintf('placa-version-%d/imagen-%d.%s', $placaVersionId, $imagenId, $extension);
    }

    public function rutaRender(int $varianteId, int $versionId, int $renderId, string $extension): string
    {
        return sprintf('variante-%d/version-%d/render-%d.%s', $varianteId, $versionId, $renderId, $extension);
    }

    /** Render sin versión concreta todavía (fase 31): sube directo a la variante. */
    public function rutaRenderSuelto(int $varianteId, int $renderId, string $extension): string
    {
        return sprintf('variante-%d/render-%d.%s', $varianteId, $renderId, $extension);
    }

    public function absoluta(string $rutaRelativa): string
    {
        // Las rutas las construye siempre esta clase a partir de IDs, pero
        // llegan de vuelta desde la base de datos: si alguna trae "..", se
        // corta aquí antes de tocar el disco.
        if (str_contains($rutaRelativa, '..')) {
            throw new RuntimeException("Ruta de fichero no válida: {$rutaRelativa}");
        }

        return $this->base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rutaRelativa);
    }

    public function existe(?string $rutaRelativa): bool
    {
        return $rutaRelativa !== null && $rutaRelativa !== '' && is_file($this->absoluta($rutaRelativa));
    }

    /**
     * Tamaño de un fichero del almacén, en bytes. Se calcula del disco, no
     * de una columna de la base de datos: ni las referencias/renders ni las
     * versiones guardan su tamaño en ninguna tabla, y duplicarlo ahí solo
     * para esto se desincronizaría en cuanto el fichero cambiase de sitio
     * (papelera, purga...). `null` si no existe — que no ocupe nada es un
     * dato válido, no un error.
     */
    public function tamano(?string $rutaRelativa): ?int
    {
        if (!$this->existe($rutaRelativa)) {
            return null;
        }

        $bytes = filesize($this->absoluta($rutaRelativa));

        return $bytes !== false ? $bytes : null;
    }

    /**
     * Cuánto ocupa TODO el almacén, papelera incluida (estadísticas, spec
     * "¿hace falta purgar?"). Recorre el disco de verdad en vez de sumar
     * columnas: ninguna tabla guarda el tamaño de referencias/renders, y una
     * suma parcial daría un total que no cuadra con lo que de verdad ocupa
     * `writable/piezas`.
     */
    public function tamanoTotal(): int
    {
        return $this->tamanoDirectorio($this->base);
    }

    /**
     * Solo la papelera: lo que se liberaría de verdad si se purgara ahora
     * (`piezas:purgar`), sin esperar a los 30 días.
     */
    public function tamanoPapelera(): int
    {
        return $this->tamanoDirectorio($this->base . DIRECTORY_SEPARATOR . 'papelera');
    }

    private function tamanoDirectorio(string $ruta): int
    {
        if (!is_dir($ruta)) {
            return 0;
        }

        $total = 0;
        $iterador = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($ruta, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterador as $fichero) {
            if ($fichero->isFile()) {
                $total += $fichero->getSize();
            }
        }

        return $total;
    }

    public function hash(string $rutaRelativa): string
    {
        $absoluta = $this->absoluta($rutaRelativa);
        if (!is_file($absoluta)) {
            throw new RuntimeException("No existe el fichero {$rutaRelativa} en el almacén.");
        }

        return hash_file('sha256', $absoluta);
    }

    /**
     * Mueve un fichero recién subido a su sitio definitivo. Devuelve la ruta
     * relativa, que es lo que se guarda en base de datos (el almacén puede
     * cambiar de sitio; los registros no deberían enterarse).
     */
    public function guardar(string $rutaOrigenAbsoluta, string $rutaRelativaDestino): string
    {
        $destino = $this->absoluta($rutaRelativaDestino);
        $this->asegurarDirectorio(dirname($destino));

        if (!@rename($rutaOrigenAbsoluta, $destino) && !@copy($rutaOrigenAbsoluta, $destino)) {
            throw new RuntimeException("No se pudo guardar el fichero en {$rutaRelativaDestino}.");
        }

        return $rutaRelativaDestino;
    }

    public function copiar(string $rutaRelativaOrigen, string $rutaRelativaDestino): string
    {
        $origen  = $this->absoluta($rutaRelativaOrigen);
        $destino = $this->absoluta($rutaRelativaDestino);
        $this->asegurarDirectorio(dirname($destino));

        if (!is_file($origen)) {
            throw new RuntimeException("No existe el fichero de origen {$rutaRelativaOrigen}.");
        }
        if (!@copy($origen, $destino)) {
            throw new RuntimeException("No se pudo copiar {$rutaRelativaOrigen} a {$rutaRelativaDestino}.");
        }

        return $rutaRelativaDestino;
    }

    /**
     * Borrado real, solo para deshacer un fichero que se acaba de escribir
     * cuando la transacción de base de datos falla después. No es la vía
     * para retirar nada del histórico: eso es papelera (invariante 6).
     */
    public function descartarEscritura(string $rutaRelativa): void
    {
        $absoluta = $this->absoluta($rutaRelativa);
        if (is_file($absoluta)) {
            @unlink($absoluta);
        }
    }

    /**
     * Invariante 6: nada se borra, se aparta. Mueve el fichero a la papelera
     * con marca de tiempo y devuelve su ruta nueva, que se guarda en la
     * sesión: durante los 30 días siguientes el fichero sigue ahí y se puede
     * recuperar a mano si la purga se adelantó a un arrepentimiento.
     *
     * Devuelve null si el fichero ya no estaba: purgar algo que no existe no
     * es un error, es el estado al que se quería llegar.
     */
    public function aPapelera(string $rutaRelativa): ?string
    {
        if (!$this->existe($rutaRelativa)) {
            return null;
        }

        $destino = 'papelera/' . date('Ymd-His') . '-' . str_replace('/', '-', $rutaRelativa);

        return $this->guardar($this->absoluta($rutaRelativa), $destino);
    }

    /**
     * Purga de la papelera a los N días (invariante 6). Esto sí borra de
     * verdad, y es el único sitio del módulo que lo hace: la papelera es
     * precisamente el plazo de gracia que hace que borrar aquí sea seguro.
     *
     * @return array<string> ficheros borrados, para poder decir qué se fue
     */
    public function purgarPapelera(int $dias = 90): array
    {
        $directorio = $this->absoluta('papelera');
        if (!is_dir($directorio)) {
            return [];
        }

        $limite   = time() - ($dias * 86400);
        $borrados = [];

        foreach (glob($directorio . DIRECTORY_SEPARATOR . '*') ?: [] as $fichero) {
            if (is_file($fichero) && filemtime($fichero) < $limite && @unlink($fichero)) {
                $borrados[] = basename($fichero);
            }
        }

        return $borrados;
    }

    private function asegurarDirectorio(string $directorio): void
    {
        if (!is_dir($directorio) && !@mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            throw new RuntimeException("No se pudo crear el directorio {$directorio}.");
        }
    }
}
