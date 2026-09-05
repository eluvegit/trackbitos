<?php

namespace App\Services;

use App\Models\SiloContadorModel;
use App\Models\SiloUnidadBucketModel;
use App\Models\SiloUnidadModel;
use App\Models\SiloVocabularioModel;

/**
 * Lógica de dominio de Silo: generación del ID de negocio, alta de
 * unidades físicas, get-or-create de vocabulario, parseo del bloque
 * semántico/nombre de carpeta y formateo del nombre de carpeta (plan Silo
 * §2, §3, §4, §7). Módulo único (no cross-módulo), así que vive aquí y no
 * en un servicio compartido — mismo criterio que PiezaService. La
 * ingesta propiamente dicha (dar de alta piezas a partir de lo que
 * escanea/simula la API) vive en SiloIngestaService, que usa estos
 * helpers.
 */
class SiloService
{
    private SiloContadorModel $contadorModel;
    private SiloVocabularioModel $vocabularioModel;
    private SiloUnidadModel $unidadModel;
    private SiloUnidadBucketModel $unidadBucketModel;

    public function __construct()
    {
        $this->contadorModel     = new SiloContadorModel();
        $this->vocabularioModel  = new SiloVocabularioModel();
        $this->unidadModel       = new SiloUnidadModel();
        $this->unidadBucketModel = new SiloUnidadBucketModel();
    }

    /**
     * ID de negocio `AAnnnn`: dos dígitos del año en que se generó el
     * contenido + 4 correlativos que reinician cada año. `$fecha` es la
     * fecha del contenido (`AAAA-MM-DD` o `AAAAMMDD`); si falta, año actual.
     */
    public function siguienteIdNegocio(?string $fecha = null): string
    {
        $anio = $fecha !== null && preg_match('/^(\d{4})/', $fecha, $m)
            ? (int) $m[1]
            : (int) date('Y');

        $correlativo = $this->contadorModel->siguiente($anio);

        return substr((string) $anio, 2) . str_pad((string) $correlativo, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Da de alta una unidad física: calcula su número de orden dentro del
     * nivel y genera el `.silo_unit.json` que se copiaría en su raíz (plan
     * Silo §7.1) — aquí solo se genera y queda descargable, no se escribe
     * a ningún disco todavía.
     */
    public function crearUnidad(int $nivel, ?string $etiqueta = null, ?string $agrupador = null, ?int $capacidadBytes = null, ?string $rutaMontaje = null, ?string $tipoFisico = null): array
    {
        $numero = $this->unidadModel->siguienteNumero($nivel);

        $id = $this->unidadModel->insert([
            'nivel'           => $nivel,
            'numero'          => $numero,
            'etiqueta'        => $etiqueta,
            'tipo_fisico'     => $tipoFisico,
            'ruta_montaje'    => $rutaMontaje,
            'agrupador'       => $agrupador,
            'capacidad_bytes' => $capacidadBytes,
            'fichero_control' => '{}',
        ], true);

        $ficheroControl = json_encode([
            'unidad_id'             => $id,
            'nivel'                 => $nivel,
            'numero'                => $numero,
            'creado_en'             => date('c'),
            'ultima_sincronizacion' => null,
            'hash_indice'           => null,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $this->unidadModel->update($id, ['fichero_control' => $ficheroControl]);

        // `agrupador` sigue siendo el campo legado de un único bucket (alta
        // manual, "Año o categoría"); se refleja también en
        // silo_unidad_buckets porque esa es la fuente de verdad que usa
        // SiloUnidadModel::buscarPorAgrupador() — así una unidad
        // pre-creada a mano para un año/categoría concretos se sigue
        // encontrando cuando llegue la primera pieza real de ese cubo.
        if ($agrupador !== null && $agrupador !== '') {
            $this->unidadBucketModel->asignarBucket($id, $agrupador);
        }

        return $this->unidadModel->find($id);
    }

    /**
     * Get-or-create de una entrada de vocabulario, cerrado a duplicados de
     * grafía dentro del mismo tipo — port directo de
     * Enlaces::getOrCreateEtiquetaId() generalizado por `tipo`.
     */
    public function getOrCreateVocabulario(string $tipo, string $nombre): array
    {
        $nombre = trim($nombre);
        $slug   = $this->slugify($nombre);

        $existente = $this->vocabularioModel
            ->where('tipo', $tipo)
            ->groupStart()
                ->where('slug', $slug)
                ->orWhere('nombre', $nombre)
            ->groupEnd()
            ->first();

        if ($existente) {
            return $existente;
        }

        try {
            $id = $this->vocabularioModel->insert(['tipo' => $tipo, 'nombre' => $nombre, 'slug' => $slug], true);
        } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            if (strpos($e->getMessage(), '1062') === false) {
                throw $e;
            }
            // Condición de carrera: otra petición lo creó entre el SELECT y el INSERT.
            $existente = $this->vocabularioModel
                ->where('tipo', $tipo)
                ->groupStart()
                    ->where('slug', $slug)
                    ->orWhere('nombre', $nombre)
                ->groupEnd()
                ->first();
            if ($existente) {
                return $existente;
            }
            throw $e;
        }

        return $this->vocabularioModel->find($id);
    }

    /**
     * Comprime una lista de años consecutivos en notación de rango
     * ("2010-2018" en vez de "2010, 2011, ..., 2018") para que quepa en la
     * tarjeta de la unidad (petición 2026-09-05). Los buckets que no son un
     * año (p.ej. "sin_fecha") pasan tal cual, sin participar en ningún
     * rango — van siempre al final porque `SiloUnidadBucketModel::bucketsDe()`
     * ya los devuelve ordenados así (alfabético: los dígitos ordenan antes
     * que las letras).
     *
     * @param array<int, string> $anios
     */
    public function comprimirAnios(array $anios): string
    {
        $numericos = array_values(array_filter($anios, fn ($a) => ctype_digit((string) $a)));
        $otros     = array_values(array_diff($anios, $numericos));

        sort($numericos, SORT_NUMERIC);

        $grupos = [];
        $inicio = $fin = null;
        foreach ($numericos as $anio) {
            $anio = (int) $anio;
            if ($inicio === null) {
                $inicio = $fin = $anio;
            } elseif ($anio === $fin + 1) {
                $fin = $anio;
            } else {
                $grupos[] = $inicio === $fin ? (string) $inicio : "{$inicio}-{$fin}";
                $inicio   = $fin = $anio;
            }
        }
        if ($inicio !== null) {
            $grupos[] = $inicio === $fin ? (string) $inicio : "{$inicio}-{$fin}";
        }

        return implode(', ', array_merge($grupos, $otros));
    }

    /** Público: SiloPropagacionService lo reutiliza para el agrupador de nivel 3 (slug de categoría). */
    public function slugify(string $texto): string
    {
        $texto = trim(mb_strtolower($texto, 'UTF-8'));

        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);

        $texto = preg_replace('/[^a-z0-9]+/i', '-', $texto);

        return trim($texto, '-');
    }

    /**
     * Extrae la fecha del principio de una entrada de una sola línea, en el
     * mismo formato compacto que lleva el nombre de carpeta (AAAAMMDD,
     * AAAAMM, AAAA o literal "sinfecha", plan Silo §3) — así una carpeta ya
     * creada a mano se puede pegar tal cual. Si no hay token de fecha
     * reconocible al principio, se asume sin fecha y el texto completo pasa
     * intacto a `resto` (regla de "bloqueo cero"). Una fecha parcial se
     * completa con 01 en lo que falte: se espera fecha completa.
     */
    public function extraerFecha(string $texto): array
    {
        $texto = ltrim($texto);

        if (!preg_match('/^(\d{8}|\d{6}|\d{4}|sinfecha)\b[\s,]*/i', $texto, $m)) {
            return ['fecha' => null, 'resto' => $texto];
        }

        $token = strtolower($m[1]);
        $resto = substr($texto, strlen($m[0]));

        if ($token === 'sinfecha') {
            return ['fecha' => null, 'resto' => $resto];
        }

        if (strlen($token) === 8 && checkdate((int) substr($token, 4, 2), (int) substr($token, 6, 2), (int) substr($token, 0, 4))) {
            return [
                'fecha' => substr($token, 0, 4) . '-' . substr($token, 4, 2) . '-' . substr($token, 6, 2),
                'resto' => $resto,
            ];
        }

        if (strlen($token) === 6 && (int) substr($token, 4, 2) >= 1 && (int) substr($token, 4, 2) <= 12) {
            return ['fecha' => substr($token, 0, 4) . '-' . substr($token, 4, 2) . '-01', 'resto' => $resto];
        }

        if (strlen($token) === 4) {
            return ['fecha' => $token . '-01-01', 'resto' => $resto];
        }

        // 8 dígitos pero fecha inválida (p.ej. 20260230): no se consume nada,
        // el texto completo (incluidos esos dígitos) pasa intacto a resto.
        return ['fecha' => null, 'resto' => $texto];
    }

    /**
     * Clasifica una entrada del primer nivel del root de un Maestro tal
     * como la reporta el agente `.py`: ¿es una carpeta-pieza que hay que
     * ingestar, o se salta (y por qué)? La decisión es de la web, no del
     * agente (doc "Contrato de entrada" — nada silencioso, todo salto se
     * reporta con motivo). Reutiliza el mismo patrón que
     * parsearNombreCarpeta() para "no_es_pieza" sin duplicar la regex.
     *
     * @return array{estado: 'candidata'|'saltada', motivo: ?string}
     */
    public function clasificarEntradaRoot(string $nombre, bool $esCarpeta, array $listaNegra = []): array
    {
        if (!$esCarpeta) {
            return ['estado' => 'saltada', 'motivo' => 'no_es_carpeta'];
        }

        if (preg_match('/^[_.~]/', $nombre)) {
            return ['estado' => 'saltada', 'motivo' => 'prefijo'];
        }

        $listaNegraNormalizada = array_map(fn ($n) => mb_strtolower(trim($n), 'UTF-8'), $listaNegra);
        if (in_array(mb_strtolower(trim($nombre), 'UTF-8'), $listaNegraNormalizada, true)) {
            return ['estado' => 'saltada', 'motivo' => 'lista_negra'];
        }

        // <id> <fecha|sinfecha> ...: mismo patrón que documenta el plan de
        // ingesta, el ID de negocio es AAnnnn (6 dígitos).
        if (!preg_match('/^\d{5,6}\s+(\d{4}|\d{6}|\d{8}|sinfecha)\b/i', trim($nombre))) {
            return ['estado' => 'saltada', 'motivo' => 'no_es_pieza'];
        }

        return ['estado' => 'candidata', 'motivo' => null];
    }

    /**
     * Trocea el bloque semántico por coma y, para cada trozo, busca una
     * coincidencia case-insensitive existente en cualquier tipo de
     * vocabulario para sugerir autorrelleno (plan Silo §7). No crea nada
     * todavía — la confirmación del usuario decide el tipo definitivo.
     */
    public function parsearBloqueSemantico(string $texto): array
    {
        $trozos = array_filter(array_map('trim', explode(',', $texto)), fn ($t) => $t !== '');

        $resultado = [];
        foreach ($trozos as $trozo) {
            $slug = $this->slugify($trozo);
            $coincidencia = $this->vocabularioModel
                ->groupStart()
                    ->where('slug', $slug)
                    ->orWhere('nombre', $trozo)
                ->groupEnd()
                ->first();

            $resultado[] = [
                'texto'           => $trozo,
                'sugerencia_tipo' => $coincidencia['tipo'] ?? null,
                'vocabulario_id'  => $coincidencia['id'] ?? null,
            ];
        }

        return $resultado;
    }

    /**
     * Construye "ID FECHA categoria, elemento1, elemento2..." tal como
     * especifica el plan Silo §3. El resultado se congela en
     * silo_piezas.nombre_carpeta en el momento del alta.
     */
    public function formatearNombreCarpeta(
        string $idNegocio,
        ?string $fecha,
        ?string $categoriaNombre,
        array $elementos
    ): string {
        $fechaTexto = $this->formatearFecha($fecha);
        $categoria  = $categoriaNombre !== null && $categoriaNombre !== '' ? $categoriaNombre : 'sin_clasificar';

        $nombre = "{$idNegocio} {$fechaTexto} {$categoria}";
        if (!empty($elementos)) {
            $nombre .= ', ' . implode(', ', $elementos);
        }

        return $nombre;
    }

    private function formatearFecha(?string $fecha): string
    {
        if (!$fecha) {
            return 'sinfecha';
        }

        $partes = explode('-', $fecha); // YYYY-MM-DD

        return ($partes[0] ?? '') . ($partes[1] ?? '') . ($partes[2] ?? '');
    }

    /**
     * Inversa de formatearNombreCarpeta(): parsea un nombre de carpeta que
     * YA existe en disco (con su ID de negocio ya asignado, ej. escaneado
     * por la API) en sus componentes — para que SiloIngestaService pueda
     * dar de alta la pieza sin que un humano vuelva a teclear nada. El
     * primer trozo separado por espacio es el ID; el resto se procesa con
     * extraerFecha() igual que en el alta manual.
     */
    public function parsearNombreCarpeta(string $nombreCarpeta): array
    {
        $nombreCarpeta = trim($nombreCarpeta);

        if (!preg_match('/^(\S+)\s+(.*)$/', $nombreCarpeta, $m)) {
            return ['id_negocio' => $nombreCarpeta, 'fecha' => null, 'categoria_texto' => null, 'elementos' => []];
        }

        $idNegocio     = $m[1];
        $fechaExtraida = $this->extraerFecha($m[2]);

        // A diferencia de antes, NO se filtran los trozos vacíos: un hueco
        // intermedio (coma doble, "Recuerdos, , Sevilla") es un campo
        // saltado a propósito y hay que conservar su posición para que
        // clasificarElementosPorPosicion() no desplace lugar/personas.
        // Solo se recortan las comas colgantes del final (nada después de
        // la última coma), que no son un hueco intencionado.
        $trozos = array_map('trim', explode(',', $fechaExtraida['resto']));
        while (count($trozos) > 1 && end($trozos) === '') {
            array_pop($trozos);
        }
        if ($trozos === ['']) {
            $trozos = [];
        }

        $categoriaTexto = $trozos[0] ?? null;
        if ($categoriaTexto !== null && ($categoriaTexto === '' || strtolower($categoriaTexto) === 'sin_clasificar')) {
            $categoriaTexto = null;
        }

        return [
            'id_negocio'      => $idNegocio,
            'fecha'           => $fechaExtraida['fecha'],
            'categoria_texto' => $categoriaTexto,
            'elementos'       => array_slice($trozos, 1),
        ];
    }

    /**
     * Contrato de "campos fijos" del nombre de carpeta (plan Silo, contrato
     * de entrada): dado el resto de la lista de comas tras la categoría
     * (`elementos` de parsearNombreCarpeta(), posiciones ya conservadas
     * huecos incluidos), posición 1 = tema, posición 2 = lugar, de la 3 en
     * adelante = personas (todas las que haya). Así el escáner clasifica
     * solo, sin que nadie etiquete nada a mano — un campo que no aplica se
     * salta dejando el hueco vacío entre comas para no desplazar los
     * siguientes.
     *
     * @param array<int, string> $elementos
     * @return array{tema: ?string, lugar: ?string, personas: string[]}
     */
    public function clasificarElementosPorPosicion(array $elementos): array
    {
        $tema  = trim((string) ($elementos[0] ?? ''));
        $lugar = trim((string) ($elementos[1] ?? ''));

        $personas = array_values(array_filter(
            array_map('trim', array_slice($elementos, 2)),
            fn ($texto) => $texto !== ''
        ));

        return [
            'tema'     => $tema !== '' ? $tema : null,
            'lugar'    => $lugar !== '' ? $lugar : null,
            'personas' => $personas,
        ];
    }
}
