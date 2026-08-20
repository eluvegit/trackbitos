<?php

namespace App\Services;

use RuntimeException;

/**
 * Las versiones para mirar de las imágenes de Piezas: miniatura y vista,
 * publicadas como ficheros estáticos bajo `public/`.
 *
 * Por qué existe esto (y por qué el original NO se sirve ya en las galerías):
 * una imagen dentro de `writable/` solo puede llegar al navegador pasando por
 * un controlador, y eso convierte cada `<img>` de una cuadrícula en un
 * arranque completo de CodeIgniter — framework, sesión, Myth\Auth y consulta
 * a la base — para acabar volcando un fichero. Medido en local: 4 ms sirve
 * Apache un estático, 230 ms el mismo fichero por controlador. Multiplicado
 * por las treinta miniaturas de una galería, esa diferencia es la que hacía
 * que las imágenes cargaran a medias y cambiaran en cada recarga.
 *
 * El original sigue donde estaba, bajo `writable/piezas` (spec sección 8):
 * es el máster, y de él salen estos derivados. Lo que se publica son copias
 * reducidas, y son desechables — se pueden borrar enteras y regenerarlas con
 * `php spark piezas:publicar-imagenes` sin perder nada.
 *
 * El nombre del fichero es el sha256 del original, que ya se guardaba en
 * `hash_imagen`. Eso da tres cosas de golpe: una URL que no se adivina (que
 * es toda la protección que tienen ya, y para renders de piezas propias
 * basta), un nombre que cambia solo si cambia la imagen, y con ello permiso
 * para cachear en el navegador para siempre sin miedo a servir una vieja.
 *
 * El directorio es `piezas-img` y no `piezas` a propósito: `public/piezas`
 * chocaría con la ruta `/piezas` del módulo — el `.htaccess` de CodeIgniter
 * manda al front controller solo lo que NO existe en disco, así que una
 * carpeta con ese nombre secuestraría la sección entera.
 */
class PiezaImagenesPublicas
{
    /** Dentro de `public/`. */
    public const DIRECTORIO = 'piezas-img';

    public const MINIATURA = 't';
    public const VISTA     = 'v';

    /**
     * La miniatura se pinta a 200 px en la galería y a 72 px en la ficha:
     * 400 la deja nítida también en pantallas de doble densidad, que es
     * donde se notaba que el navegador estaba reescalando un 1024.
     */
    private const LADOS = [
        self::MINIATURA => 400,
        self::VISTA     => 1600,
    ];

    private const CALIDAD = [
        self::MINIATURA => 82,
        self::VISTA     => 88,
    ];

    private string $base;

    public function __construct(?string $base = null)
    {
        $this->base = rtrim($base ?? (FCPATH . self::DIRECTORIO), '/\\');
    }

    public function nombre(string $hash, string $tamano): string
    {
        return $hash . '-' . $tamano . '.webp';
    }

    public function absoluta(string $hash, string $tamano): string
    {
        return $this->base . DIRECTORY_SEPARATOR . $this->nombre($hash, $tamano);
    }

    public function existe(?string $hash, string $tamano): bool
    {
        return $hash !== null && $hash !== '' && is_file($this->absoluta($hash, $tamano));
    }

    /**
     * La URL pública, o null si ese derivado todavía no está generado —
     * quien llame decide si cae al controlador de siempre. Devolver null en
     * vez de una URL rota es lo que permite desplegar el código antes de
     * haber publicado las imágenes que ya están subidas: la galería sigue
     * funcionando, más lenta, hasta que se pase el comando.
     */
    public function url(?string $hash, string $tamano): ?string
    {
        return $this->existe($hash, $tamano)
            ? base_url(self::DIRECTORIO . '/' . $this->nombre($hash, $tamano))
            : null;
    }

    /**
     * Genera miniatura y vista a partir del original. Idempotente: si ya
     * están y no se pide rehacerlas, no toca nada — así el comando de
     * publicar se puede lanzar tantas veces como haga falta.
     *
     * @return list<string> los tamaños que ha escrito de verdad
     */
    public function publicar(string $originalAbsoluto, string $hash, bool $rehacer = false): array
    {
        $pendientes = array_filter(
            array_keys(self::LADOS),
            fn(string $tamano) => $rehacer || !$this->existe($hash, $tamano)
        );

        if ($pendientes === []) {
            return [];
        }

        $original = $this->leer($originalAbsoluto);
        $this->asegurarDirectorio();

        $hechos = [];
        foreach ($pendientes as $tamano) {
            $this->escribirDerivado($original, $hash, $tamano);
            $hechos[] = $tamano;
        }

        imagedestroy($original);

        return $hechos;
    }

    /**
     * Quita las copias públicas de una imagen. Aquí sí se borra de verdad y
     * no hay papelera que valga (invariante 6): esto no es el fichero de
     * nadie, es una copia que se rehace en un segundo desde el original,
     * que ese sí está en la papelera si se acaba de apartar.
     */
    public function retirar(?string $hash): void
    {
        if ($hash === null || $hash === '') {
            return;
        }

        foreach (array_keys(self::LADOS) as $tamano) {
            $fichero = $this->absoluta($hash, $tamano);
            if (is_file($fichero)) {
                @unlink($fichero);
            }
        }
    }

    /**
     * Barre las copias cuyo original ya no lo respalda nadie.
     *
     * Hace falta porque no todo lo que borra imágenes pasa por
     * `borrarRender`/`borrarReferencia`: apartar una pieza entera deja sus
     * renders en pie durante el mes de gracia, y es la purga la que acaba
     * llevándoselos. Sin este barrido, esas miniaturas se quedarían en
     * `public/` para siempre — accesibles a quien tuviera la URL y ocupando
     * sitio sin que nada las nombre ya.
     *
     * @param list<string> $hashesVivos los que siguen teniendo registro
     *
     * @return int cuántos ficheros se han borrado
     */
    public function retirarHuerfanas(array $hashesVivos): int
    {
        $vivos    = array_flip($hashesVivos);
        $borrados = 0;

        foreach (glob($this->base . DIRECTORY_SEPARATOR . '*.webp') ?: [] as $fichero) {
            // Solo se toca lo que tiene forma de derivado nuestro
            // ("<sha256>-t.webp"). Cualquier otra cosa que alguien haya
            // dejado ahí no es asunto de esta función.
            if (!preg_match('/^([0-9a-f]{64})-[tv]\.webp$/', basename($fichero), $partes)) {
                continue;
            }

            if (!isset($vivos[$partes[1]]) && @unlink($fichero)) {
                $borrados++;
            }
        }

        return $borrados;
    }

    /**
     * @return \GdImage
     */
    private function leer(string $rutaAbsoluta)
    {
        if (!is_file($rutaAbsoluta)) {
            throw new RuntimeException("No existe el original {$rutaAbsoluta}.");
        }

        $contenido = file_get_contents($rutaAbsoluta);
        $imagen    = $contenido !== false ? @imagecreatefromstring($contenido) : false;
        if ($imagen === false) {
            throw new RuntimeException("No se pudo leer la imagen {$rutaAbsoluta}.");
        }

        return $imagen;
    }

    /**
     * @param \GdImage $original
     */
    private function escribirDerivado($original, string $hash, string $tamano): void
    {
        $anchoOriginal = imagesx($original);
        $altoOriginal  = imagesy($original);

        // Nunca se amplía: una imagen pequeña se queda como está antes que
        // estirarse y salir blanda.
        $escala = min(1, self::LADOS[$tamano] / max($anchoOriginal, $altoOriginal));
        $ancho  = max(1, (int) round($anchoOriginal * $escala));
        $alto   = max(1, (int) round($altoOriginal * $escala));

        $lienzo = imagecreatetruecolor($ancho, $alto);

        // La transparencia se conserva, que es la mitad de lo que se veía
        // mal: un render de Blender sale con el fondo en alfa y antes se
        // aplanaba a blanco para poder guardarlo en JPEG — un cuadrado
        // blanco en mitad de una tarjeta oscura. WebP sí lleva canal alfa.
        imagealphablending($lienzo, false);
        imagesavealpha($lienzo, true);
        imagefill($lienzo, 0, 0, imagecolorallocatealpha($lienzo, 0, 0, 0, 127));

        imagecopyresampled($lienzo, $original, 0, 0, 0, 0, $ancho, $alto, $anchoOriginal, $altoOriginal);

        if (!imagewebp($lienzo, $this->absoluta($hash, $tamano), self::CALIDAD[$tamano])) {
            imagedestroy($lienzo);

            throw new RuntimeException("No se pudo escribir la imagen pública {$hash}-{$tamano}.");
        }

        imagedestroy($lienzo);
    }

    private function asegurarDirectorio(): void
    {
        if (!is_dir($this->base) && !@mkdir($this->base, 0775, true) && !is_dir($this->base)) {
            throw new RuntimeException("No se pudo crear el directorio público {$this->base}.");
        }
    }

    /** Cuánto ocupan las copias públicas (pantalla de estadísticas). */
    public function tamanoTotal(): int
    {
        $total = 0;
        foreach (glob($this->base . DIRECTORY_SEPARATOR . '*.webp') ?: [] as $fichero) {
            $total += (int) filesize($fichero);
        }

        return $total;
    }
}
