<?php

namespace App\Services;

use App\Models\SiloContadorModel;
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

    public function __construct()
    {
        $this->contadorModel    = new SiloContadorModel();
        $this->vocabularioModel = new SiloVocabularioModel();
        $this->unidadModel      = new SiloUnidadModel();
    }

    public function siguienteIdNegocio(int $ancho = 6): string
    {
        return str_pad((string) $this->contadorModel->siguiente(), $ancho, '0', STR_PAD_LEFT);
    }

    /**
     * Da de alta una unidad física: calcula su número de orden dentro del
     * nivel y genera el `.silo_unit.json` que se copiaría en su raíz (plan
     * Silo §7.1) — aquí solo se genera y queda descargable, no se escribe
     * a ningún disco todavía.
     */
    public function crearUnidad(int $nivel, ?string $etiqueta = null, ?string $agrupador = null, ?int $capacidadBytes = null): array
    {
        $numero = $this->unidadModel->siguienteNumero($nivel);

        $id = $this->unidadModel->insert([
            'nivel'           => $nivel,
            'numero'          => $numero,
            'etiqueta'        => $etiqueta,
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
     * mismo formato compacto que ya lleva el nombre de carpeta (AAAAMMDD,
     * AAAAMM, AAAA o literal "sinfecha", plan Silo §3) — así una carpeta ya
     * creada a mano se puede pegar tal cual, sin reescribirla. La precisión
     * se deduce del propio formato, nunca se pide aparte (plan Silo §4
     * "Fecha"). Si no hay token de fecha reconocible al principio, se
     * asume sin_fecha y el texto completo pasa intacto a `resto` — nunca
     * bloquea el alta (regla de "bloqueo cero").
     *
     * Como la columna `fecha` es DATE (necesita día y mes), una fecha
     * parcial se guarda con el resto relleno a 01 — formatearFecha() solo
     * usa la parte que corresponde a la precisión real al construir el
     * nombre de carpeta, así que el relleno no se ve nunca.
     */
    public function extraerFecha(string $texto): array
    {
        $texto = ltrim($texto);

        if (!preg_match('/^(\d{8}|\d{6}|\d{4}|sinfecha)\b[\s,]*/i', $texto, $m)) {
            return ['fecha' => null, 'precision' => 'sin_fecha', 'resto' => $texto];
        }

        $token = strtolower($m[1]);
        $resto = substr($texto, strlen($m[0]));

        if ($token === 'sinfecha') {
            return ['fecha' => null, 'precision' => 'sin_fecha', 'resto' => $resto];
        }

        if (strlen($token) === 8 && checkdate((int) substr($token, 4, 2), (int) substr($token, 6, 2), (int) substr($token, 0, 4))) {
            return [
                'fecha'     => substr($token, 0, 4) . '-' . substr($token, 4, 2) . '-' . substr($token, 6, 2),
                'precision' => 'dia',
                'resto'     => $resto,
            ];
        }

        if (strlen($token) === 6 && (int) substr($token, 4, 2) >= 1 && (int) substr($token, 4, 2) <= 12) {
            return ['fecha' => substr($token, 0, 4) . '-' . substr($token, 4, 2) . '-01', 'precision' => 'mes', 'resto' => $resto];
        }

        if (strlen($token) === 4) {
            return ['fecha' => $token . '-01-01', 'precision' => 'anio', 'resto' => $resto];
        }

        // 8 dígitos pero fecha inválida (p.ej. 20260230): no se consume nada,
        // el texto completo (incluidos esos dígitos) pasa intacto a resto.
        return ['fecha' => null, 'precision' => 'sin_fecha', 'resto' => $texto];
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
        string $precision,
        ?string $categoriaNombre,
        array $elementos
    ): string {
        $fechaTexto = $this->formatearFecha($fecha, $precision);
        $categoria  = $categoriaNombre !== null && $categoriaNombre !== '' ? $categoriaNombre : 'sin_clasificar';

        $nombre = "{$idNegocio} {$fechaTexto} {$categoria}";
        if (!empty($elementos)) {
            $nombre .= ', ' . implode(', ', $elementos);
        }

        return $nombre;
    }

    private function formatearFecha(?string $fecha, string $precision): string
    {
        if ($precision === 'sin_fecha' || !$fecha) {
            return 'sinfecha';
        }

        $partes = explode('-', $fecha); // YYYY-MM-DD desde un <input type="date"> o similar
        $anio   = $partes[0] ?? '';
        $mes    = $partes[1] ?? '';
        $dia    = $partes[2] ?? '';

        return match ($precision) {
            'dia'  => $anio . $mes . $dia,
            'mes'  => $anio . $mes,
            'anio' => $anio,
            default => 'sinfecha',
        };
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
            return ['id_negocio' => $nombreCarpeta, 'fecha' => null, 'precision' => 'sin_fecha', 'categoria_texto' => null, 'elementos' => []];
        }

        $idNegocio     = $m[1];
        $fechaExtraida = $this->extraerFecha($m[2]);

        $trozos = array_values(array_filter(array_map('trim', explode(',', $fechaExtraida['resto'])), fn ($t) => $t !== ''));
        $categoriaTexto = $trozos[0] ?? null;
        if ($categoriaTexto !== null && strtolower($categoriaTexto) === 'sin_clasificar') {
            $categoriaTexto = null;
        }

        return [
            'id_negocio'      => $idNegocio,
            'fecha'           => $fechaExtraida['fecha'],
            'precision'       => $fechaExtraida['precision'],
            'categoria_texto' => $categoriaTexto,
            'elementos'       => array_slice($trozos, 1),
        ];
    }
}
