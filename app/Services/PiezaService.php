<?php

namespace App\Services;

use App\Models\PiezaCategoriaModel;
use App\Models\PiezaComposicionModel;
use App\Models\PiezaDescargaModel;
use App\Models\PiezaFamiliaModel;
use App\Models\PiezaMaquinaModel;
use App\Models\PiezaRamaModel;
use App\Models\PiezaReferenciaModel;
use App\Models\PiezaRenderModel;
use App\Models\PiezaSesionModel;
use App\Models\PiezaVarianteModel;
use App\Models\PiezaVersionModel;
use App\Models\PiezaVersionStlModel;
use CodeIgniter\Model;
use RuntimeException;
use Throwable;

/**
 * Los verbos del dominio de Piezas (spec sección 3). Cada método es la
 * transacción atómica completa de una acción de usuario; los invariantes
 * 1-4 ya viven en los modelos (ver Pieza*Model), aquí se añade la parte
 * de "verbo" propiamente dicha: qué pasos concretos ejecuta cada acción
 * y qué transición de estado exige antes de dejarla pasar.
 *
 * Sin interfaz todavía (fase 6): se prueba por consola/seeder.
 */
class PiezaService
{
    /**
     * Nombre de la variante que se crea sola con cada pieza. "base" y no
     * "estándar" ni "original": no promete nada sobre las que puedan venir
     * después, que es justo lo que hace falta cuando todavía no sabes si
     * habrá más de una.
     */
    public const VARIANTE_BASE = 'base';

    private PiezaFamiliaModel $familiaModel;
    private PiezaVarianteModel $varianteModel;
    private PiezaVersionModel $versionModel;
    private PiezaRamaModel $ramaModel;
    private PiezaSesionModel $sesionModel;
    private PiezaCategoriaModel $categoriaModel;
    private PiezaMaquinaModel $maquinaModel;
    private PiezaComposicionModel $composicionModel;
    private PiezaVersionStlModel $stlModel;

    public function __construct()
    {
        $this->stlModel         = new PiezaVersionStlModel();
        $this->familiaModel     = new PiezaFamiliaModel();
        $this->varianteModel    = new PiezaVarianteModel();
        $this->versionModel     = new PiezaVersionModel();
        $this->ramaModel        = new PiezaRamaModel();
        $this->sesionModel      = new PiezaSesionModel();
        $this->categoriaModel   = new PiezaCategoriaModel();
        $this->maquinaModel     = new PiezaMaquinaModel();
        $this->composicionModel = new PiezaComposicionModel();
    }

    // ---- Máquinas -------------------------------------------------------

    /**
     * Renombrar una máquina para que se lea (spec 4.5): el nombre de alta
     * sale del hostname, que puede ser "MacBook-de-Jesus" o "DESKTOP-4F2K1".
     * Es lo único editable de una máquina, y a propósito: el UUID es su
     * identidad y lo pone el cliente, no el navegador.
     *
     * El nombre tiene que ser único porque su único trabajo es distinguirlas
     * en los avisos: dos máquinas llamadas "MacBook" convierten "sesión
     * abierta en MacBook" en una frase que no dice dónde ir a mirar.
     */
    public function renombrarMaquina(int $maquinaId, string $nombre): array
    {
        if (!$this->maquinaModel->find($maquinaId)) {
            throw new RuntimeException("Máquina {$maquinaId} no encontrada.");
        }

        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new RuntimeException('La máquina necesita un nombre.');
        }

        foreach ($this->maquinaModel->findAll() as $existente) {
            if ((int) $existente['id'] !== $maquinaId
                && mb_strtolower($existente['nombre']) === mb_strtolower($nombre)) {
                throw new RuntimeException("Ya hay otra máquina llamada \"{$existente['nombre']}\".");
            }
        }

        $this->maquinaModel->update($maquinaId, ['nombre' => $nombre]);

        return $this->maquinaModel->find($maquinaId);
    }

    // ---- Categorías (spec 11.1) -----------------------------------------

    /**
     * Nace la última de la lista: quien crea una categoría a mitad de
     * organizar no está diciendo nada sobre su prioridad, y colarla arriba
     * movería de sitio las que el usuario ya había colocado.
     */
    public function crearCategoria(string $nombre): array
    {
        $nombre = $this->nombreDeCategoriaOFallar($nombre);

        $id = $this->insertarOFallar($this->categoriaModel, [
            'nombre' => $nombre,
            'orden'  => 1 + (int) ($this->categoriaModel->selectMax('orden')->first()['orden'] ?? 0),
        ]);

        return $this->categoriaModel->find($id);
    }

    public function renombrarCategoria(int $categoriaId, string $nombre): array
    {
        if (!$this->categoriaModel->find($categoriaId)) {
            throw new RuntimeException("Categoría {$categoriaId} no encontrada.");
        }

        $nombre = $this->nombreDeCategoriaOFallar($nombre, $categoriaId);
        $this->categoriaModel->update($categoriaId, ['nombre' => $nombre]);

        return $this->categoriaModel->find($categoriaId);
    }

    /**
     * Borrar una categoría no toca las piezas que había dentro: se quedan
     * sin clasificar, visibles al final del índice. Se devuelve cuántas
     * son para poder decirlo — que aparezcan de golpe abajo sin avisar es
     * justo lo que hace pensar que se han perdido.
     *
     * @return array{categoria: array, descolocadas: int}
     */
    public function borrarCategoria(int $categoriaId): array
    {
        $categoria = $this->categoriaModel->find($categoriaId);
        if (!$categoria) {
            throw new RuntimeException("Categoría {$categoriaId} no encontrada.");
        }

        $descolocadas = $this->familiaModel->where('categoria_id', $categoriaId)->countAllResults();

        // El ON DELETE SET NULL del esquema haría esto solo, pero hacerlo
        // aquí deja el recuento y el efecto en el mismo sitio, y no depende
        // de que la FK exista (la base de producción se migró después).
        $this->transaccion('borrar la categoría', function () use ($categoriaId) {
            $this->familiaModel->where('categoria_id', $categoriaId)->set('categoria_id', null)->update();
            $this->categoriaModel->delete($categoriaId);
        });

        return ['categoria' => $categoria, 'descolocadas' => $descolocadas];
    }

    /**
     * Sube o baja una categoría intercambiando su `orden` con la vecina.
     * Con media docena de categorías esto basta y sobra; un campo de orden
     * editable a mano obligaría a pensar en números que a nadie le importan.
     */
    public function moverCategoria(int $categoriaId, int $direccion): array
    {
        $categoria = $this->categoriaModel->find($categoriaId);
        if (!$categoria) {
            throw new RuntimeException("Categoría {$categoriaId} no encontrada.");
        }

        $lista    = $this->categoriaModel->ordenadas();
        $posicion = array_search((int) $categoria['id'], array_column($lista, 'id'), false);
        $destino  = $posicion + ($direccion < 0 ? -1 : 1);

        if ($destino < 0 || $destino >= count($lista)) {
            return $categoria; // Ya está en el borde: no es un error, no hay nada que hacer.
        }

        // Se reescribe la lista entera y no solo el par que se intercambia:
        // las categorías creadas antes de reordenar nada comparten `orden`
        // (todas a 0 no, pero sí pueden empatar tras varios borrados) y un
        // intercambio de valores iguales no movería nada.
        $vecina = $lista[$destino];
        $lista[$destino]  = $categoria;
        $lista[$posicion] = $vecina;

        $this->transaccion('reordenar las categorías', function () use ($lista) {
            foreach ($lista as $posicion => $fila) {
                $this->categoriaModel->update($fila['id'], ['orden' => $posicion + 1]);
            }
        });

        return $this->categoriaModel->find($categoriaId);
    }

    /**
     * Mete una pieza en una categoría, o la saca de todas ($categoriaId
     * null). Es una reorganización, no un cambio de identidad: nada del
     * historial de versiones depende de dónde esté colocada la pieza.
     */
    public function clasificarFamilia(int $familiaId, ?int $categoriaId): array
    {
        $familia = $this->familiaModel->find($familiaId);
        if (!$familia) {
            throw new RuntimeException("Pieza {$familiaId} no encontrada.");
        }
        if ($categoriaId !== null && !$this->categoriaModel->find($categoriaId)) {
            throw new RuntimeException("Categoría {$categoriaId} no encontrada.");
        }

        $this->familiaModel->update($familiaId, ['categoria_id' => $categoriaId]);

        return $this->familiaModel->find($familiaId);
    }

    private function nombreDeCategoriaOFallar(string $nombre, ?int $excluirId = null): string
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new RuntimeException('La categoría necesita un nombre.');
        }

        // Comparación insensible a mayúsculas y en PHP, no en SQL: son media
        // docena de filas, y así el criterio no depende de la collation de
        // la base. "Accesorios" y "accesorios" son la misma carpeta para
        // quien organiza.
        foreach ($this->categoriaModel->findAll() as $existente) {
            if ((int) $existente['id'] !== $excluirId
                && mb_strtolower($existente['nombre']) === mb_strtolower($nombre)) {
                throw new RuntimeException("Ya existe una categoría llamada \"{$existente['nombre']}\".");
            }
        }

        return $nombre;
    }

    /**
     * Crea la pieza (en el esquema, "familia") y de una vez su variante
     * inicial, que se llama "base".
     *
     * Las variantes son la excepción, no la norma: la mayoría de piezas son
     * una sola cosa ("un pincel y ya"), y obligar a inventarse un nombre de
     * variante para llegar a modelar era peaje puro. Quien sí necesite
     * varias líneas de diseño las añade después, o las deriva de una versión
     * concreta, que es de donde salen de verdad.
     *
     * La jerarquía no cambia — sigue haciendo falta para numerar versiones
     * por variante y para colgar las referencias de la pieza, comunes a
     * todas ellas (spec 1.1). Lo que se quita es el trabajo manual.
     *
     * @return array{familia: array, variante: array}
     */
    public function crearFamilia(string $nombre, ?string $notas = null, ?string $sku = null, ?int $categoriaId = null): array
    {
        $sku = $this->normalizarSkuOFallar($sku);
        if ($categoriaId !== null && !$this->categoriaModel->find($categoriaId)) {
            throw new RuntimeException("Categoría {$categoriaId} no encontrada.");
        }

        $familiaId = $this->transaccion('crear la pieza', function () use ($nombre, $notas, $categoriaId) {
            return $this->insertarOFallar($this->familiaModel, [
                'nombre'       => $nombre,
                'categoria_id' => $categoriaId,
                'notas'        => $notas,
            ]);
        });

        // Fuera de la transacción anterior a propósito: crearVariante abre su
        // propia transacción (rama incluida) y anidarlas en CodeIgniter no
        // daría un punto de retorno intermedio real.
        $variante = $this->crearVariante($familiaId, self::VARIANTE_BASE, null, $sku);

        return [
            'familia'  => $this->familiaModel->find($familiaId),
            'variante' => $variante,
        ];
    }

    /**
     * El nombre de la pieza entera (a diferencia de `renombrarVariante`, que
     * solo toca la línea de diseño dentro de ella). Sin comprobación de
     * unicidad, igual que `crearFamilia`: no la había al crear, así que
     * exigirla solo al renombrar sería inconsistente.
     */
    public function renombrarFamilia(int $familiaId, string $nombre): array
    {
        if (!$this->familiaModel->find($familiaId)) {
            throw new RuntimeException("Pieza {$familiaId} no encontrada.");
        }

        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new RuntimeException('La pieza necesita un nombre.');
        }

        $this->familiaModel->update($familiaId, ['nombre' => $nombre]);

        return $this->familiaModel->find($familiaId);
    }

    /**
     * "Borrar pieza" (invariante 6, extendida a la familia entera): no la
     * destruye, la marca con la fecha en que se apartó. Desaparece del
     * índice y de la galería, pero se puede restaurar mientras siga en la
     * papelera — y solo se purga de verdad, fila y ficheros, a los 30 días
     * (`purgarFamiliasBorradas`, vía `piezas:purgar`).
     *
     * Se niega si alguna de sus variantes tiene una sesión de trabajo
     * abierta: borrar debajo de ese bloqueo dejaría el asiento colgando de
     * una pieza que ya no aparece en ningún sitio.
     */
    public function borrarFamilia(int $familiaId): array
    {
        $familia = $this->familiaModel->find($familiaId);
        if (!$familia) {
            throw new RuntimeException("Pieza {$familiaId} no encontrada.");
        }
        if ($familia['borrado_en'] !== null) {
            throw new RuntimeException('Esa pieza ya está en la papelera.');
        }

        foreach ($this->varianteModel->where('familia_id', $familiaId)->findAll() as $variante) {
            if ($this->sesionModel->hayAbiertaParaVariante((int) $variante['id'])) {
                throw new RuntimeException(
                    "La variante \"{$variante['nombre']}\" tiene una sesión de trabajo sin cerrar. "
                    . 'Ciérrala antes de borrar la pieza.'
                );
            }
        }

        $this->familiaModel->update($familiaId, ['borrado_en' => date('Y-m-d H:i:s')]);

        return $this->familiaModel->find($familiaId);
    }

    /**
     * Saca una pieza de la papelera mientras todavía se pueda: deshace
     * `borrarFamilia` sin más rastro que el que ya hubiera antes.
     */
    public function restaurarFamilia(int $familiaId): array
    {
        $familia = $this->familiaModel->find($familiaId);
        if (!$familia) {
            throw new RuntimeException("Pieza {$familiaId} no encontrada.");
        }
        if ($familia['borrado_en'] === null) {
            throw new RuntimeException('Esa pieza no está en la papelera.');
        }

        $this->familiaModel->update($familiaId, ['borrado_en' => null]);

        return $this->familiaModel->find($familiaId);
    }

    /**
     * "Borrar variante" (invariante 6, ahora también suelta, no solo la
     * familia entera): una pieza con varias líneas de diseño puede tener
     * alguna abandonada — un tamaño que no se pidió nunca más, un
     * prototipo descartado — sin que el resto de la pieza tenga nada que
     * ver. Mismo criterio que `borrarFamilia`: se niega si hay una sesión
     * de trabajo abierta, no destruye nada, solo aparta con fecha.
     */
    public function borrarVariante(int $varianteId): array
    {
        $variante = $this->varianteModel->find($varianteId);
        if (!$variante) {
            throw new RuntimeException("Variante {$varianteId} no encontrada.");
        }
        if ($variante['borrado_en'] !== null) {
            throw new RuntimeException('Esa variante ya está en la papelera.');
        }
        if ($this->sesionModel->hayAbiertaParaVariante($varianteId)) {
            throw new RuntimeException(
                'Esta variante tiene una sesión de trabajo sin cerrar. Ciérrala antes de borrarla.'
            );
        }

        $this->varianteModel->update($varianteId, ['borrado_en' => date('Y-m-d H:i:s')]);

        return $this->varianteModel->find($varianteId);
    }

    /**
     * Saca una variante de la papelera mientras todavía se pueda: deshace
     * `borrarVariante` sin más rastro que el que ya hubiera antes.
     */
    public function restaurarVariante(int $varianteId): array
    {
        $variante = $this->varianteModel->find($varianteId);
        if (!$variante) {
            throw new RuntimeException("Variante {$varianteId} no encontrada.");
        }
        if ($variante['borrado_en'] === null) {
            throw new RuntimeException('Esa variante no está en la papelera.');
        }

        $this->varianteModel->update($varianteId, ['borrado_en' => null]);

        return $this->varianteModel->find($varianteId);
    }

    /**
     * Purga definitiva de variantes sueltas que llevan más de N días en la
     * papelera (invariante 6) — mismo criterio que `purgarFamiliasBorradas`,
     * pero para una sola variante, sin tocar el resto de la pieza. Las
     * referencias quedan fuera a propósito: son de la familia entera
     * (spec 1.1), no de esta línea de diseño en concreto.
     *
     * Si la familia entera también se purga en la misma pasada, esta
     * variante ya no aparecerá aquí (la cascada de FK se la habrá llevado
     * por delante) — no hay conflicto entre las dos purgas, solo un orden
     * que no importa.
     *
     * @return list<string> "Familia / variante" de lo purgado
     */
    public function purgarVariantesBorradas(int $dias = 30): array
    {
        $limite = date('Y-m-d H:i:s', time() - $dias * 86400);

        $variantes = $this->varianteModel
            ->where('borrado_en IS NOT NULL')
            ->where('borrado_en <', $limite)
            ->findAll();

        if ($variantes === []) {
            return [];
        }

        $almacen     = new PiezaAlmacen();
        $renderModel = new PiezaRenderModel();
        $nombres     = [];

        foreach ($variantes as $variante) {
            $varianteId = (int) $variante['id'];
            $familia    = $this->familiaModel->find((int) $variante['familia_id']);

            foreach ($this->versionModel->where('variante_id', $varianteId)->findAll() as $version) {
                if (!empty($version['ruta_blend'])) {
                    $almacen->aPapelera($version['ruta_blend']);
                }
                foreach ($this->stlModel->deVersion((int) $version['id']) as $stl) {
                    if (!empty($stl['ruta_stl'])) {
                        $almacen->aPapelera($stl['ruta_stl']);
                    }
                }
                foreach ($renderModel->where('version_id', $version['id'])->findAll() as $render) {
                    $almacen->aPapelera($render['ruta_imagen']);
                }
            }

            foreach ($this->ramaModel->where('variante_id', $varianteId)->findAll() as $rama) {
                foreach ($this->sesionModel->where('rama_id', $rama['id'])->where('purgada', 0)->findAll() as $sesion) {
                    if (!empty($sesion['ruta_blend'])) {
                        $almacen->aPapelera($sesion['ruta_blend']);
                    }
                }
            }

            $this->varianteModel->delete($varianteId);
            $nombres[] = ($familia['nombre'] ?? '?') . ' / ' . $variante['nombre'];
        }

        return $nombres;
    }

    /**
     * Purga definitiva de piezas que llevan más de N días en la papelera
     * (invariante 6): borra la fila de verdad — la cascada de FK se lleva
     * variantes, versiones, ramas, sesiones y descargas — y antes aparta a
     * la papelera de ficheros (`PiezaAlmacen::aPapelera`) todo lo que aún
     * viviera en su sitio original, para que sus .blend/.stl/imágenes sigan
     * el mismo plazo de gracia que cualquier otro fichero apartado en vez de
     * quedar huérfanos en disco.
     *
     * Pensado para `piezas:purgar`, junto a la purga de la papelera de
     * ficheros: es el único punto del módulo que borra piezas de verdad, y
     * solo toca lo que ya lleva un mes esperando.
     *
     * @return list<string> nombres de las piezas purgadas
     */
    public function purgarFamiliasBorradas(int $dias = 30): array
    {
        $limite = date('Y-m-d H:i:s', time() - $dias * 86400);

        $familias = $this->familiaModel
            ->where('borrado_en IS NOT NULL')
            ->where('borrado_en <', $limite)
            ->findAll();

        if ($familias === []) {
            return [];
        }

        $almacen         = new PiezaAlmacen();
        $referenciaModel = new PiezaReferenciaModel();
        $renderModel     = new PiezaRenderModel();
        $nombres         = [];

        foreach ($familias as $familia) {
            $familiaId = (int) $familia['id'];

            foreach ($this->varianteModel->where('familia_id', $familiaId)->findAll() as $variante) {
                $varianteId = (int) $variante['id'];

                foreach ($this->versionModel->where('variante_id', $varianteId)->findAll() as $version) {
                    if (!empty($version['ruta_blend'])) {
                        $almacen->aPapelera($version['ruta_blend']);
                    }
                    // Varios STL por versión desde la fase 21: los brazos por
                    // separado, una pieza alta cortada en trozos.
                    foreach ($this->stlModel->deVersion((int) $version['id']) as $stl) {
                        if (!empty($stl['ruta_stl'])) {
                            $almacen->aPapelera($stl['ruta_stl']);
                        }
                    }
                    foreach ($renderModel->where('version_id', $version['id'])->findAll() as $render) {
                        $almacen->aPapelera($render['ruta_imagen']);
                    }
                }

                foreach ($this->ramaModel->where('variante_id', $varianteId)->findAll() as $rama) {
                    // Las sesiones ya purgadas (invariante 5) apuntan a un
                    // fichero que ya vive en la papelera; solo quedan por
                    // apartar las que nunca llegaron a promocionar.
                    foreach ($this->sesionModel->where('rama_id', $rama['id'])->where('purgada', 0)->findAll() as $sesion) {
                        if (!empty($sesion['ruta_blend'])) {
                            $almacen->aPapelera($sesion['ruta_blend']);
                        }
                    }
                }
            }

            foreach ($referenciaModel->where('familia_id', $familiaId)->findAll() as $referencia) {
                $almacen->aPapelera($referencia['ruta_imagen']);
            }

            $this->familiaModel->delete($familiaId);
            $nombres[] = $familia['nombre'];
        }

        return $nombres;
    }

    /**
     * Crea la variante y le abre de una vez su rama inicial (desde_version_id
     * NULL): sin rama abierta no habría dónde abrir la primera sesión.
     */
    public function crearVariante(int $familiaId, string $nombre, ?string $notas = null, ?string $sku = null): array
    {
        if (!$this->familiaModel->find($familiaId)) {
            throw new RuntimeException("Familia {$familiaId} no encontrada.");
        }
        $sku = $this->normalizarSkuOFallar($sku);
        // Misma comprobación que al renombrar: si solo se exigiera allí, un
        // duplicado seguiría entrando por esta puerta.
        $nombre = $this->nombreDeVarianteOFallar($familiaId, $nombre);

        $varianteId = $this->transaccion('crear la variante', function () use ($familiaId, $nombre, $notas, $sku) {
            $varianteId = $this->insertarOFallar($this->varianteModel, [
                'familia_id' => $familiaId,
                'nombre'     => $nombre,
                'notas'      => $notas,
                'sku'        => $sku,
            ]);

            $this->ramaModel->abrir($varianteId);

            return $varianteId;
        });

        return $this->varianteModel->find($varianteId);
    }

    /**
     * Renombrar una variante. Hace falta desde el primer uso real: las
     * piezas con varias líneas de diseño nacen con una llamada `base`
     * (fase 12), y en cuanto aparece la segunda ese nombre deja de decir
     * nada — "base" y "grande" no se leen como una pareja.
     *
     * Es cosmético para el registro: lo que identifica a la variante es su
     * id, y ni las versiones ni los hashes ni los asientos de descarga
     * dependen del nombre. Lo único que cambia de verdad es cómo se la
     * llama desde el cliente (`trackbitos bajar "Pistola grande"`), que es
     * justo el motivo para poder arreglarlo.
     */
    public function renombrarVariante(int $varianteId, string $nombre): array
    {
        $variante = $this->varianteModel->find($varianteId);
        if (!$variante) {
            throw new RuntimeException("Variante {$varianteId} no encontrada.");
        }

        $nombre = $this->nombreDeVarianteOFallar((int) $variante['familia_id'], $nombre, $varianteId);
        $this->varianteModel->update($varianteId, ['nombre' => $nombre]);

        return $this->varianteModel->find($varianteId);
    }

    /**
     * El nombre de una variante solo tiene que ser único **dentro de su
     * pieza** ("base" se repite en cuanto hay dos piezas, y está bien).
     * Pero dentro de la misma pieza sí, porque el cliente resuelve las
     * variantes por nombre: dos "grande" en la misma pistola harían
     * ambiguo un `trackbitos bajar`, que es peor que un nombre feo.
     */
    private function nombreDeVarianteOFallar(int $familiaId, string $nombre, ?int $excluirId = null): string
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new RuntimeException('La variante necesita un nombre.');
        }

        $hermanas = $this->varianteModel->where('familia_id', $familiaId)->findAll();
        foreach ($hermanas as $hermana) {
            if ((int) $hermana['id'] !== $excluirId
                && mb_strtolower($hermana['nombre']) === mb_strtolower($nombre)) {
                throw new RuntimeException("Esta pieza ya tiene una variante llamada \"{$hermana['nombre']}\".");
            }
        }

        return $nombre;
    }

    /**
     * El SKU es la otra cosa editable de una variante. Es una referencia
     * manual — Trackbitos no sincroniza con la tienda, solo guarda el mismo
     * código.
     */
    public function actualizarSku(int $varianteId, ?string $sku): array
    {
        if (!$this->varianteModel->find($varianteId)) {
            throw new RuntimeException("Variante {$varianteId} no encontrada.");
        }

        $sku = $this->normalizarSkuOFallar($sku, $varianteId);
        $this->varianteModel->update($varianteId, ['sku' => $sku]);

        return $this->varianteModel->find($varianteId);
    }

    private function normalizarSkuOFallar(?string $sku, ?int $excluirVarianteId = null): ?string
    {
        $sku = trim((string) $sku);
        if ($sku === '') {
            return null;
        }

        $query = $this->varianteModel->where('sku', $sku);
        if ($excluirVarianteId !== null) {
            $query->where('id !=', $excluirVarianteId);
        }
        $existente = $query->first();
        if ($existente) {
            throw new RuntimeException("El SKU '{$sku}' ya lo tiene \"{$existente['nombre']}\".");
        }

        return $sku;
    }

    /**
     * Dónde vive el máster de máxima calidad de esta variante (p. ej. la
     * malla en bruto de una generación por IA, sin decimar ni limpiar de
     * texturas): fuera del tracker, normalmente en Drive — no hace falta
     * versionarlo ni bloquearlo entre máquinas, solo poder volver a él. Solo
     * se guarda el enlace; el fichero en sí nunca pasa por aquí.
     */
    public function actualizarEnlaceOriginal(int $varianteId, ?string $enlace): array
    {
        if (!$this->varianteModel->find($varianteId)) {
            throw new RuntimeException("Variante {$varianteId} no encontrada.");
        }

        $enlace = trim((string) $enlace);
        $this->varianteModel->update($varianteId, ['enlace_original' => $enlace === '' ? null : $enlace]);

        return $this->varianteModel->find($varianteId);
    }

    /**
     * "Derivar variante": nueva línea de diseño a partir de una versión ya
     * existente (de la misma familia o de otra). No copia ficheros ni
     * referencias — numeración de versiones propia desde v001.
     */
    public function derivarVariante(int $origenVersionId, string $nombre, ?string $notas = null): array
    {
        $origen = $this->versionModel->find($origenVersionId);
        if (!$origen) {
            throw new RuntimeException("Versión de origen {$origenVersionId} no encontrada.");
        }
        $varianteOrigen = $this->varianteModel->find($origen['variante_id']);
        $nombre         = $this->nombreDeVarianteOFallar((int) $varianteOrigen['familia_id'], $nombre);

        $varianteId = $this->transaccion('derivar la variante', function () use ($origenVersionId, $nombre, $notas, $varianteOrigen) {
            $varianteId = $this->insertarOFallar($this->varianteModel, [
                'familia_id'        => $varianteOrigen['familia_id'],
                'nombre'            => $nombre,
                'origen_version_id' => $origenVersionId,
                'notas'             => $notas,
            ]);

            $this->ramaModel->abrir($varianteId, $origenVersionId);

            return $varianteId;
        });

        return $this->varianteModel->find($varianteId);
    }

    /**
     * "Compuesta de": anota que la versión de OTRA pieza estaba presente en
     * la escena de esta variante (un torso modelado con el brazo ya hecho
     * al lado, un "Mini playmobil" que es varias piezas de cuerpo juntas).
     *
     * Aparte a propósito de `origen_version_id` (derivarVariante): eso es
     * de qué fichero concreto se partió, uno solo, y lo usa la
     * sincronización para la cadena de hashes (spec 4.4). Esto es una
     * lista puramente informativa — no afecta a ningún invariante, no se
     * recalcula ni se promociona sola.
     */
    public function declararComponente(int $varianteId, int $versionComponenteId, ?string $notas = null): array
    {
        $variante = $this->varianteModel->find($varianteId);
        if (!$variante) {
            throw new RuntimeException("Variante {$varianteId} no encontrada.");
        }

        $version = $this->versionModel->find($versionComponenteId);
        if (!$version) {
            throw new RuntimeException("Versión {$versionComponenteId} no encontrada.");
        }

        if ((int) $version['variante_id'] === $varianteId) {
            throw new RuntimeException('Una pieza no puede componerse de una versión de sí misma.');
        }

        if ($this->composicionModel->where('variante_id', $varianteId)->where('version_componente_id', $versionComponenteId)->first()) {
            throw new RuntimeException('Esa versión ya está anotada como parte de esta pieza.');
        }

        $id = $this->insertarOFallar($this->composicionModel, [
            'variante_id'           => $varianteId,
            'version_componente_id' => $versionComponenteId,
            'notas'                 => $notas,
        ]);

        return $this->composicionModel->find($id);
    }

    public function quitarComponente(int $composicionId): void
    {
        if (!$this->composicionModel->find($composicionId)) {
            throw new RuntimeException("Ese componente {$composicionId} no está anotado (o ya se quitó).");
        }

        $this->composicionModel->delete($composicionId);
    }

    /**
     * "Abrir sesión": reclama la máquina. Requiere que la variante tenga
     * una rama abierta (la crea crearVariante/promocionar/devolverATrabajo,
     * nunca este método). Falla si ya hay una sesión sin cerrar
     * (invariante 3, aplicado dentro de PiezaSesionModel::abrir).
     */
    public function abrirSesion(int $varianteId, int $maquinaId): array
    {
        $this->exigirNadaSinJuzgar($varianteId, 'abrir una sesión de trabajo');

        $rama = $this->ramaModel->abiertaDe($varianteId);
        if (!$rama) {
            throw new RuntimeException(
                "La variante {$varianteId} no tiene ninguna rama de trabajo abierta. Esto no debería pasar: "
                . 'toda variante nace con una, y promocionar/devolver a trabajo siempre dejan una abierta.'
            );
        }

        return $this->sesionModel->abrir($rama['id'], $maquinaId);
    }

    /**
     * "Subir sesión": guarda el .blend de una sesión ya abierta. El cálculo
     * del hash, el guardado físico del fichero y el cuadre del asiento de
     * descarga son responsabilidad de PiezaSyncService, que llama aquí —
     * este método solo persiste los datos ya verificados.
     */
    public function subirSesion(int $sesionId, string $rutaBlend, string $hashBlend, int $tamanoBytes, ?string $log = null, ?string $hashPadre = null): array
    {
        $sesion = $this->sesionModel->find($sesionId);
        if (!$sesion) {
            throw new RuntimeException("Sesión {$sesionId} no encontrada.");
        }

        $datos = [
            'ruta_blend'   => $rutaBlend,
            'hash_blend'   => $hashBlend,
            'tamano_bytes' => $tamanoBytes,
            'hash_padre'   => $hashPadre,
            'subida_en'    => date('Y-m-d H:i:s'),
        ];
        // Una segunda subida sin nota no debe borrar la nota de la primera.
        if ($log !== null) {
            $datos['log'] = $log;
        }

        $this->sesionModel->update($sesionId, $datos);

        return $this->sesionModel->find($sesionId);
    }

    /**
     * "Cerrar sesión": libera el bloqueo de máquina. No exige que se haya
     * subido nada — cerrar sin subir es legítimo (p.ej. sesión de consulta).
     */
    public function cerrarSesion(int $sesionId): array
    {
        if (!$this->sesionModel->find($sesionId)) {
            throw new RuntimeException("Sesión {$sesionId} no encontrada.");
        }

        return $this->sesionModel->cerrar($sesionId);
    }

    /**
     * "Promocionar": crea la version con el .blend de la última sesión
     * subida de la rama abierta, la cierra, y abre la rama siguiente
     * ("desde-vNNN"). Exige `cambio` no vacío (lo valida PiezaVersionModel).
     */
    public function promocionar(int $varianteId, string $cambio, ?string $medidas = null): array
    {
        $rama = $this->ramaModel->abiertaDe($varianteId);
        if (!$rama) {
            throw new RuntimeException("La variante {$varianteId} no tiene ninguna rama abierta que promocionar.");
        }

        // Promocionar con una sesión viva dejaría el bloqueo colgando de una
        // rama ya cerrada, y la rama nueva nacería inutilizable (invariante 3
        // se comprueba por variante, no por rama). Se niega y explica.
        if ($this->sesionModel->hayAbiertaParaVariante($varianteId)) {
            throw new RuntimeException(
                'Hay una sesión de trabajo sin cerrar en esta variante. Súbela y ciérrala antes de promocionar: '
                . 'lo que quede sin subir no entraría en la versión.'
            );
        }

        $ultimaSubida = $this->sesionModel->ultimaSubida((int) $rama['id']);
        if (!$ultimaSubida) {
            throw new RuntimeException(
                'No hay ninguna sesión subida en esta rama todavía. Sube el .blend antes de promocionar '
                . '— promocionar sin fichero dejaría una versión sin contenido real.'
            );
        }

        // La versión se lleva su propia copia del fichero, no la ruta de la
        // sesión: las sesiones se purgan al validar (invariante 5) y esa purga
        // se llevaría por delante justo el fichero que nunca debe perderse.
        $numero      = $this->versionModel->siguienteNumero($varianteId);
        $almacen     = new PiezaAlmacen();
        $rutaVersion = $almacen->rutaVersion($varianteId, $numero);

        if (!$almacen->existe($ultimaSubida['ruta_blend'])) {
            throw new RuntimeException(
                "El .blend de la sesión {$ultimaSubida['numero']} no está en el almacén "
                . "({$ultimaSubida['ruta_blend']}). No se promociona una versión sin fichero real detrás."
            );
        }
        $almacen->copiar($ultimaSubida['ruta_blend'], $rutaVersion);

        try {
            $versionId = $this->transaccion('promocionar', function () use ($varianteId, $numero, $cambio, $medidas, $rama, $ultimaSubida, $rutaVersion) {
                $versionId = $this->insertarOFallar($this->versionModel, [
                    'variante_id'     => $varianteId,
                    'numero'          => $numero,
                    'estado'          => 'borrador',
                    'promocionada_en' => date('Y-m-d H:i:s'),
                    'ruta_blend'      => $rutaVersion,
                    'hash_blend'      => $ultimaSubida['hash_blend'],
                    'cambio'          => $cambio,
                    'medidas'         => $medidas,
                ]);

                $this->ramaModel->cerrar($rama['id'], $versionId);
                $this->ramaModel->abrir($varianteId, $versionId);

                return $versionId;
            });
        } catch (Throwable $e) {
            $almacen->descartarEscritura($rutaVersion);

            throw $e;
        }

        return $this->versionModel->find($versionId);
    }

    /**
     * "Devolver a trabajo": abre una rama nueva partiendo de una versión ya
     * existente, sin tocarla (las versiones son inmutables). Típicamente
     * para retomar una versión superada/descartada, o iterar sobre la
     * validada actual.
     *
     * Siempre hay una rama abierta (promocionar cierra una y abre otra), así
     * que retomar una versión antigua implica necesariamente abandonar la
     * línea en curso. Eso es destructivo y ambiguo, así que por defecto se
     * niega y explica cuánto trabajo dejaría atrás; con $abandonarRama el
     * usuario ya sabe lo que hace. Las sesiones no se pierden: quedan
     * colgando de la rama cerrada, con su historial intacto.
     */
    public function devolverATrabajo(int $versionId, bool $abandonarRama = false): array
    {
        $version = $this->versionModel->find($versionId);
        if (!$version) {
            throw new RuntimeException("Versión {$versionId} no encontrada.");
        }

        $varianteId = (int) $version['variante_id'];

        $this->exigirNadaSinJuzgar($varianteId, 'devolver a trabajo');

        if ($this->sesionModel->hayAbiertaParaVariante($varianteId)) {
            throw new RuntimeException(
                'Hay una sesión de trabajo sin cerrar en esta variante. Ciérrala antes de cambiar de línea de trabajo.'
            );
        }

        $ramaAbierta = $this->ramaModel->abiertaDe($varianteId);

        if ($ramaAbierta && !$abandonarRama) {
            $subidas = count($this->sesionModel
                ->where('rama_id', $ramaAbierta['id'])
                ->where('subida_en IS NOT NULL')
                ->findAll());

            throw new RuntimeException(sprintf(
                'La rama "%s" sigue abierta con %d sesión(es) subida(s) sin promocionar. Volver a la v%03d '
                . 'la cerraría sin convertirla en versión: ese trabajo quedaría solo en el historial.',
                $this->ramaModel->nombre($ramaAbierta),
                $subidas,
                (int) $version['numero']
            ));
        }

        return $this->transaccion('devolver a trabajo', function () use ($ramaAbierta, $varianteId, $versionId) {
            if ($ramaAbierta) {
                // Sin versión que la cierre: la rama se abandona, no se promociona.
                $this->ramaModel->cerrar((int) $ramaAbierta['id']);
            }

            return $this->ramaModel->abrir($varianteId, $versionId);
        });
    }

    /** Los STL de una versión, en el orden en que se subieron. */
    public function stlsDe(int $versionId): array
    {
        return $this->stlModel->deVersion($versionId);
    }

    /** @param int[] $versionIds @return array<int, array> agrupados por versión */
    public function stlsDeVersiones(array $versionIds): array
    {
        return $this->stlModel->porVersiones($versionIds);
    }

    public function stl(int $stlId): ?array
    {
        return $this->stlModel->find($stlId);
    }

    /**
     * Reserva el sitio de un STL nuevo en una versión: crea la fila y
     * devuelve su id, para poder calcular la ruta del fichero (que lleva ese
     * id dentro) antes de mover nada al almacén. Mismo alta en dos pasos que
     * las referencias e imágenes.
     *
     * Se adjunta aparte de promocionar: el usuario exporta desde Blender
     * cuando le hace falta, normalmente justo antes de imprimir.
     */
    public function reservarStl(int $versionId, string $nombre): array
    {
        if (!$this->versionModel->find($versionId)) {
            throw new RuntimeException("Versión {$versionId} no encontrada.");
        }

        $nombre = $this->stlModel->exigirNombreLibre($versionId, $nombre);

        $id = $this->insertarOFallar($this->stlModel, [
            'version_id' => $versionId,
            'nombre'     => $nombre,
        ]);

        return $this->stlModel->find($id);
    }

    /**
     * Confirma el STL una vez su fichero ya está en el almacén. Inmutable
     * desde aquí (invariante 4): un STL se añade o se aparta, nunca se
     * sobreescribe — si el modelo cambió, eso es una versión nueva, no un
     * reemplazo silencioso del fichero con el que ya se imprimió.
     */
    public function adjuntarStl(int $stlId, string $rutaRelativa, string $hash, ?int $tamanoBytes = null): array
    {
        $stl = $this->stlModel->find($stlId);
        if (!$stl) {
            throw new RuntimeException("STL {$stlId} no encontrado.");
        }
        if (!empty($stl['ruta_stl'])) {
            throw new RuntimeException(
                "El STL \"{$stl['nombre']}\" ya tiene fichero. Es inmutable, como el .blend: "
                . 'quítalo y súbelo otra vez, o promociona una versión nueva.'
            );
        }

        $this->stlModel->update($stlId, [
            'ruta_stl'     => $rutaRelativa,
            'hash_stl'     => $hash,
            'tamano_bytes' => $tamanoBytes,
            'subido_en'    => date('Y-m-d H:i:s'),
        ]);

        return $this->stlModel->find($stlId);
    }

    /**
     * Quita un STL de una versión. Con varios por versión, subir el fichero
     * equivocado deja de ser un accidente raro, así que hace falta una vía
     * de vuelta — pero por papelera (invariante 6), no borrando: el fichero
     * sigue 30 días recuperable a mano.
     *
     * La fila sí se borra, a diferencia de las sesiones: una sesión purgada
     * conserva número, hashes y log porque documenta trabajo que existió;
     * un STL retirado no documenta nada que el historial necesite.
     */
    public function quitarStl(int $stlId): array
    {
        $stl = $this->stlModel->find($stlId);
        if (!$stl) {
            throw new RuntimeException("STL {$stlId} no encontrado.");
        }

        if (!empty($stl['ruta_stl'])) {
            (new PiezaAlmacen())->aPapelera($stl['ruta_stl']);
        }

        $this->stlModel->delete($stlId);

        return $stl;
    }

    /**
     * "Marcar impresa": borrador -> impresa, con los parámetros usados.
     */
    public function marcarImpresa(int $versionId, ?string $paramsImpresion = null): array
    {
        $version = $this->exigirEstado($versionId, ['borrador'], 'marcar como impresa');

        $this->versionModel->update($versionId, [
            'estado'           => 'impresa',
            'params_impresion' => $paramsImpresion ?? $version['params_impresion'],
        ]);

        return $this->versionModel->find($versionId);
    }

    /**
     * "Validar": impresa -> validada. Degrada la anterior validada de la
     * misma variante a superada (invariante 1, PiezaVersionModel::marcarValidada)
     * y habilita la purga de las sesiones que llevaron hasta ella.
     */
    public function validar(int $versionId, ?string $resultado = null): array
    {
        $this->exigirEstado($versionId, ['impresa'], 'validar');

        $version = $this->versionModel->marcarValidada($versionId, $resultado);

        // Invariante 5: las sesiones se purgan al VALIDAR, no al promocionar.
        // Si la impresión sale mal, los .blend intermedios aún hacen falta
        // para entender qué se probó; una vez la pieza física funciona, ya
        // no. Va fuera de la transacción de marcarValidada a propósito: mover
        // ficheros no es reversible con un rollback, y un fallo purgando no
        // debe deshacer una validación que es correcta.
        $this->purgarSesionesDe($versionId);

        return $version;
    }

    /**
     * Aparta los .blend de las sesiones de la rama que cerró esta versión.
     * Las filas NO se borran: se marcan `purgada` y conservan número, hashes,
     * máquina y log. Lo que ocupa sitio es el fichero; lo que da valor al
     * historial es el registro, y eso se queda.
     *
     * @return int cuántas sesiones se purgaron
     */
    public function purgarSesionesDe(int $versionId): int
    {
        $rama = $this->ramaModel->where('cerrada_por_version_id', $versionId)->first();
        if (!$rama) {
            return 0;
        }

        $almacen  = new PiezaAlmacen();
        $purgadas = 0;

        foreach ($this->sesionModel->where('rama_id', $rama['id'])->where('purgada', 0)->findAll() as $sesion) {
            $datos = ['purgada' => 1];

            if (!empty($sesion['ruta_blend'])) {
                $enPapelera = $almacen->aPapelera($sesion['ruta_blend']);
                if ($enPapelera !== null) {
                    $datos['ruta_blend'] = $enPapelera;
                }
            }

            $this->sesionModel->update($sesion['id'], $datos);
            $purgadas++;
        }

        return $purgadas;
    }

    /**
     * Aparta a mano el .blend de una sesión ya cerrada que no va a llegar a
     * promocionarse tal cual (p. ej. una subida de prueba que resultó
     * demasiado pesada y se va a reemplazar por otra reducida). Es lo mismo
     * que hace `purgarSesionesDe` al validar, pero disparado antes de ese
     * punto: la rama sigue abierta, así que ni el invariante 5 ni ningún
     * "descartar" de versión se aplican todavía — sin esto, un .blend de
     * prueba se queda ocupando sitio para siempre porque el módulo nunca
     * llegaría a purgar solo esa rama.
     *
     * La fila NO se borra, igual que el resto del módulo (invariante 6): se
     * marca `purgada` y conserva número, hashes, máquina y log. Solo se
     * mueve el fichero.
     */
    public function descartarFicheroSesion(int $sesionId, string $motivo = ''): array
    {
        $sesion = $this->sesionModel->find($sesionId);
        if (!$sesion) {
            throw new RuntimeException("Sesión {$sesionId} no encontrada.");
        }
        if (empty($sesion['cerrada_en'])) {
            throw new RuntimeException('Esta sesión sigue abierta: ciérrala antes de descartar su fichero.');
        }
        if (!empty($sesion['purgada'])) {
            throw new RuntimeException('El fichero de esta sesión ya se apartó a la papelera.');
        }

        $descargaAbierta = (new PiezaDescargaModel())->where('sesion_id', $sesionId)->where('cerrada', 0)->first();
        if ($descargaAbierta) {
            throw new RuntimeException(
                "Esta sesión tiene la descarga {$descargaAbierta['id']} sin cerrar. Ciérrala primero."
            );
        }

        $datos = ['purgada' => 1];
        if (!empty($sesion['ruta_blend'])) {
            $enPapelera = (new PiezaAlmacen())->aPapelera($sesion['ruta_blend']);
            if ($enPapelera !== null) {
                $datos['ruta_blend'] = $enPapelera;
            }
        }
        $datos['log'] = trim(($sesion['log'] ? $sesion['log'] . "\n" : '')
            . 'Fichero apartado a mano' . (trim($motivo) !== '' ? ': ' . trim($motivo) : ' (sin motivo indicado).'));

        $this->sesionModel->update($sesionId, $datos);

        return $this->sesionModel->find($sesionId);
    }

    /**
     * "Descartar": no sirve, pero no se borra — se conserva con el motivo.
     * Solo desde borrador/impresa: una versión ya validada/superada/
     * descartada tiene su propio historial, no se tapa con un descarte.
     */
    public function descartar(int $versionId, string $resultado): array
    {
        if (trim($resultado) === '') {
            throw new RuntimeException('Descartar exige un motivo en "resultado": no se borra nada sin dejar constancia de por qué.');
        }

        $this->exigirEstado($versionId, ['borrador', 'impresa'], 'descartar');

        $this->versionModel->update($versionId, ['estado' => 'descartada', 'resultado' => $resultado]);

        return $this->versionModel->find($versionId);
    }

    /**
     * Invariante 9: una impresión sin juzgar bloquea el trabajo nuevo.
     *
     * Si ya imprimiste una versión y no has dicho si sirve, seguir modelando
     * encima es trabajar a ciegas: no sabes si partes de algo bueno. Y ese
     * juicio, si no se hace en caliente — con la pieza recién salida en la
     * mano —, no se hace nunca: quedan versiones "impresa" para siempre y el
     * historial deja de decir cuál era la buena. Para seguir hay que
     * decidirlo: validar, o descartar y continuar desde ahí.
     *
     * Mira TODAS las versiones, no solo la última. Con la última bastaría
     * con promocionar otra encima para que el bloqueo desapareciera y la
     * impresa se quedara sin juzgar — una puerta trasera a esta misma regla.
     */
    private function exigirNadaSinJuzgar(int $varianteId, string $accion): void
    {
        $pendientes = $this->versionModel
            ->where('variante_id', $varianteId)
            ->where('estado', 'impresa')
            ->orderBy('numero', 'ASC')
            ->findAll();

        if ($pendientes === []) {
            return;
        }

        $numeros = array_map(static fn($v) => 'v' . sprintf('%03d', (int) $v['numero']), $pendientes);

        throw new RuntimeException(sprintf(
            'No se puede %s: %s. Di si la impresión sirve (validar) o si no (descartar, con el motivo) '
            . 'y sigue desde ahí.',
            $accion,
            count($numeros) === 1
                ? 'la ' . $numeros[0] . ' está impresa y sin juzgar'
                : 'las versiones ' . implode(', ', $numeros) . ' están impresas y sin juzgar'
        ));
    }

    /**
     * Comprueba que la versión está en uno de los estados de partida
     * permitidos para la acción; si no, se niega y explica en qué estado
     * está realmente, en vez de dejar pasar una transición inválida.
     */
    private function exigirEstado(int $versionId, array $permitidos, string $accion): array
    {
        $version = $this->versionModel->find($versionId);
        if (!$version) {
            throw new RuntimeException("Versión {$versionId} no encontrada.");
        }

        if (!in_array($version['estado'], $permitidos, true)) {
            throw new RuntimeException(
                "No se puede {$accion} la versión {$versionId}: está en estado '{$version['estado']}', "
                . 'y esta acción solo es válida desde ' . implode('/', $permitidos) . '.'
            );
        }

        return $version;
    }

    /**
     * insert() de CodeIgniter no lanza excepción si falla la validación:
     * devuelve false en silencio. Aquí se convierte en un fallo explícito
     * con el motivo, para no arrastrar un id falso (false/0) a los pasos
     * siguientes de un verbo — que es exactamente lo que rompía una
     * transacción a medias antes de este helper.
     */
    private function insertarOFallar(Model $model, array $datos): int
    {
        $id = $model->insert($datos, true);
        if (!$id) {
            $errores = $model->errors();
            throw new RuntimeException(
                'No se pudo guardar: ' . ($errores ? implode(' ', $errores) : 'motivo desconocido.')
            );
        }

        return (int) $id;
    }

    /**
     * Ejecuta $pasos dentro de una transacción; si algo lanza excepción a
     * mitad de camino, hace rollback explícito antes de relanzarla, para
     * que la conexión quede limpia y la siguiente operación no herede una
     * transacción a medias.
     */
    private function transaccion(string $accion, callable $pasos)
    {
        $db = db_connect();
        $db->transStart();

        try {
            $resultado = $pasos();
        } catch (Throwable $e) {
            $db->transRollback();
            throw $e;
        }

        $db->transComplete();
        if ($db->transStatus() === false) {
            throw new RuntimeException("No se pudo {$accion}: fallo de transacción.");
        }

        return $resultado;
    }
}
