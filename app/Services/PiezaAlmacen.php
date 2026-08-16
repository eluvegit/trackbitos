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
    public function purgarPapelera(int $dias = 30): array
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
