<?php

use App\Services\PiezaImagenesPublicas;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Las copias públicas de las imágenes de Piezas: que se generen con la
 * medida y el formato que toca, que conserven la transparencia y que
 * limpiar no se lleve por delante lo que no es suyo.
 *
 * @internal
 */
final class PiezaImagenesPublicasTest extends CIUnitTestCase
{
    private string $directorio;
    private string $original;
    private string $hash;
    private PiezaImagenesPublicas $publicas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directorio = WRITEPATH . 'tests-piezas-img-' . bin2hex(random_bytes(4));
        $this->publicas   = new PiezaImagenesPublicas($this->directorio);

        // Un PNG con fondo transparente, como sale un render de Blender.
        $imagen = imagecreatetruecolor(1200, 800);
        imagealphablending($imagen, false);
        imagesavealpha($imagen, true);
        imagefill($imagen, 0, 0, imagecolorallocatealpha($imagen, 0, 0, 0, 127));
        imagealphablending($imagen, true);
        imagefilledellipse($imagen, 600, 400, 500, 300, imagecolorallocate($imagen, 200, 90, 40));

        $this->original = $this->directorio . '-original.png';
        imagepng($imagen, $this->original);
        imagedestroy($imagen);

        $this->hash = hash_file('sha256', $this->original);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directorio . DIRECTORY_SEPARATOR . '*') ?: [] as $fichero) {
            @unlink($fichero);
        }
        @rmdir($this->directorio);
        @unlink($this->original);

        parent::tearDown();
    }

    public function testSinPublicarNoHayUrl(): void
    {
        // De esto depende que la galería siga funcionando en un servidor
        // donde todavía no se ha pasado piezas:publicar-imagenes: quien
        // llama ve null y cae al controlador de siempre.
        $this->assertNull($this->publicas->url($this->hash, PiezaImagenesPublicas::MINIATURA));
        $this->assertNull($this->publicas->url(null, PiezaImagenesPublicas::MINIATURA));
        $this->assertNull($this->publicas->url('', PiezaImagenesPublicas::MINIATURA));
    }

    public function testPublicarGeneraMiniaturaYVista(): void
    {
        $hechos = $this->publicas->publicar($this->original, $this->hash);

        $this->assertSame(['t', 'v'], $hechos);

        $miniatura = $this->publicas->absoluta($this->hash, PiezaImagenesPublicas::MINIATURA);
        $vista     = $this->publicas->absoluta($this->hash, PiezaImagenesPublicas::VISTA);

        $this->assertFileExists($miniatura);
        $this->assertFileExists($vista);

        // El lado mayor manda, y la proporción se respeta: 1200x800 -> 400x267.
        [$ancho, $alto, $tipo] = getimagesize($miniatura);
        $this->assertSame(400, $ancho);
        $this->assertSame(267, $alto);
        $this->assertSame(IMAGETYPE_WEBP, $tipo);

        // Nunca se amplía: el original tiene 1200 de lado, no llega a 1600.
        $this->assertSame(1200, getimagesize($vista)[0]);
    }

    public function testLaTransparenciaSobrevive(): void
    {
        $this->publicas->publicar($this->original, $this->hash);

        $imagen = imagecreatefromwebp($this->publicas->absoluta($this->hash, PiezaImagenesPublicas::MINIATURA));
        $alfa   = (imagecolorat($imagen, 0, 0) >> 24) & 0x7F;
        imagedestroy($imagen);

        $this->assertSame(127, $alfa, 'La esquina debería seguir siendo transparente, no blanca.');
    }

    public function testPublicarDosVecesNoRehaceNada(): void
    {
        $this->publicas->publicar($this->original, $this->hash);

        $this->assertSame([], $this->publicas->publicar($this->original, $this->hash));
        $this->assertSame(['t', 'v'], $this->publicas->publicar($this->original, $this->hash, true));
    }

    public function testRetirarBorraLasDosMedidas(): void
    {
        $this->publicas->publicar($this->original, $this->hash);
        $this->publicas->retirar($this->hash);

        $this->assertFalse($this->publicas->existe($this->hash, PiezaImagenesPublicas::MINIATURA));
        $this->assertFalse($this->publicas->existe($this->hash, PiezaImagenesPublicas::VISTA));
    }

    public function testRetirarHuerfanasRespetaLoVivoYLoAjeno(): void
    {
        $this->publicas->publicar($this->original, $this->hash);

        $huerfana = $this->publicas->absoluta(str_repeat('a', 64), PiezaImagenesPublicas::MINIATURA);
        copy($this->publicas->absoluta($this->hash, PiezaImagenesPublicas::MINIATURA), $huerfana);

        $ajeno = $this->directorio . DIRECTORY_SEPARATOR . 'logo-de-otro.webp';
        copy($this->publicas->absoluta($this->hash, PiezaImagenesPublicas::MINIATURA), $ajeno);

        $this->assertSame(1, $this->publicas->retirarHuerfanas([$this->hash]));

        $this->assertFileDoesNotExist($huerfana);
        $this->assertFileExists($ajeno, 'Un fichero que no tiene forma de derivado nuestro no se toca.');
        $this->assertTrue($this->publicas->existe($this->hash, PiezaImagenesPublicas::MINIATURA));
    }
}
