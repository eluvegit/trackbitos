<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Pieza de Silo: material fotográfico/vídeo resultante ya editado,
 * seleccionado o entregado. `nombre_carpeta` se calcula una vez en el alta
 * (SiloService::formatearNombreCarpeta()) y no se vuelve a tocar al
 * reclasificar — reclasificar es solo cambiar `categoria_id`/atributos.
 */
class SiloPiezaModel extends Model
{
    protected $table         = 'silo_piezas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = 'actualizado_en';

    protected $allowedFields = [
        'id_negocio', 'fecha', 'tipo', 'fuente',
        'categoria_id', 'subido', 'subido_en', 'fecha_generacion',
        'tamano_bytes', 'bloque_semantico', 'nombre_carpeta', 'notas',
    ];

    protected $validationRules = [
        'id_negocio'     => 'required|max_length[20]|is_unique[silo_piezas.id_negocio,id,{id}]',
        'nombre_carpeta' => 'required|max_length[500]',
    ];

    /**
     * Búsqueda de listado: texto libre sobre id_negocio/nombre_carpeta +
     * nombre de los ficheros de dentro de la carpeta (silo_ficheros) +
     * filtro opcional por categoría. El match por fichero va como subconsulta
     * de IDs (no JOIN) para no multiplicar filas ni necesitar GROUP BY. No usa
     * FULLTEXT (como enlaces_items) porque el volumen esperado en esta fase es
     * bajo; revisar si hace falta cuando el catálogo crezca.
     */
    public function buscar(array $filtros = []): array
    {
        $builder = $this->select('silo_piezas.*, cat.nombre AS categoria_nombre')
            ->join('silo_vocabulario cat', 'cat.id = silo_piezas.categoria_id', 'left')
            ->orderBy('silo_piezas.nombre_carpeta', 'ASC');

        if (!empty($filtros['q'])) {
            $q = $filtros['q'];
            $piezasConFichero = $this->db->table('silo_ficheros')
                ->select('pieza_id')
                ->like('nombre', $q);

            $builder->groupStart()
                ->like('silo_piezas.id_negocio', $q)
                ->orLike('silo_piezas.nombre_carpeta', $q)
                ->orWhereIn('silo_piezas.id', $piezasConFichero)
                ->groupEnd();
        }

        if (!empty($filtros['categoria_id'])) {
            $builder->where('silo_piezas.categoria_id', (int) $filtros['categoria_id']);
        }

        $piezas = $this->adjuntarAtributos($builder->findAll());

        if (!empty($filtros['q'])) {
            $piezas = $this->adjuntarFicherosCoincidentes($piezas, $filtros['q']);
        }

        return $piezas;
    }

    /**
     * Adjunta en `ficheros_coincidentes` los ficheros de cada pieza cuyo
     * nombre casa con el texto buscado, para que el listado pueda enseñar el
     * fichero concreto que hizo salir la carpeta y no solo la carpeta. Vacío
     * cuando la coincidencia fue por ID/nombre de carpeta.
     *
     * @param array<int, array> $piezas
     * @return array<int, array>
     */
    private function adjuntarFicherosCoincidentes(array $piezas, string $q): array
    {
        if ($piezas === []) {
            return $piezas;
        }

        $filas = (new SiloFicheroModel())
            ->select('pieza_id, nombre, tipo')
            ->whereIn('pieza_id', array_column($piezas, 'id'))
            ->like('nombre', $q)
            ->orderBy('nombre', 'ASC')
            ->findAll();

        $porPieza = [];
        foreach ($filas as $f) {
            $porPieza[(int) $f['pieza_id']][] = ['nombre' => $f['nombre'], 'tipo' => $f['tipo']];
        }

        foreach ($piezas as &$p) {
            $p['ficheros_coincidentes'] = $porPieza[(int) $p['id']] ?? [];
        }

        return $piezas;
    }

    /**
     * Piezas que viven en una unidad, estilo "contenido de esta carpeta".
     * `$orden`: 'nombre' (por defecto, alfabético = orden de alta dentro
     * del año gracias al correlativo del ID, como un explorador) o 'fecha'
     * (cronológico de verdad — útil sobre todo en una unidad de Nivel 2
     * "Año", donde el ID de alta no coincide con el orden real de las
     * fechas; petición 2026-09-05). Sin fecha va al final, no al principio.
     */
    public function deLaUnidad(int $unidadId, string $orden = 'nombre'): array
    {
        $query = $this->select('silo_piezas.*, cat.nombre AS categoria_nombre')
            ->join('silo_ubicaciones', 'silo_ubicaciones.pieza_id = silo_piezas.id')
            ->join('silo_vocabulario cat', 'cat.id = silo_piezas.categoria_id', 'left')
            ->where('silo_ubicaciones.unidad_id', $unidadId)
            ->groupBy('silo_piezas.id');

        if ($orden === 'fecha') {
            $query->orderBy('silo_piezas.fecha IS NULL', 'ASC', false)
                  ->orderBy('silo_piezas.fecha', 'ASC');
        } else {
            $query->orderBy('silo_piezas.nombre_carpeta', 'ASC');
        }

        return $this->adjuntarAtributos($query->findAll());
    }

    /**
     * Piezas a las que les falta algún dato de clasificación, agrupadas por
     * qué les falta — alimenta la vista "Datos que faltan"
     * (/silo/datos-faltan). Una pieza puede salir en varias listas; las que
     * están completas NO se devuelven (solo interesa lo que hay que
     * arreglar). Se mira la clasificación YA asignada (categoría +
     * atributos), que es justo lo que se corrige en "Reclasificar" — no el
     * nombre de carpeta, que está congelado.
     *
     * - `sin_tematica`: ningún atributo de tipo `tema`
     * - `sin_lugar`:    ningún atributo de tipo `lugar`
     * - `sin_personas`: ningún atributo de tipo `persona`
     * - `mal`:          sin categoría, sin fecha, o el nombre de carpeta no
     *                   sigue el patrón "<id> <fecha> ..." del contrato de
     *                   entrada (cada pieza trae `motivos` con el detalle)
     * - `sin_etiqueta_contenido`: tiene temática pero ninguna lleva la
     *                   etiqueta de contenido "(Fotos + Vídeos + Montajes)".
     *                   Cada pieza trae `contenido_real` (['fotos','videos']
     *                   detectados en `silo_ficheros`) y `combo` para filtrar.
     * - `contenido_descuadra`: la temática declara "(Fotos + Vídeos)" pero en
     *                   `silo_ficheros` no hay material de un tipo detectable
     *                   (foto/video) que se declara. Trae `contenido_declarado`,
     *                   `contenido_real`, `contenido_falta` y `combo` (lo que
     *                   falta). Montajes no se puede comprobar en ficheros y
     *                   no cuenta como que falta.
     *
     * @return array{sin_tematica: array<int, array>, sin_lugar: array<int, array>, sin_personas: array<int, array>, mal: array<int, array>, sin_etiqueta_contenido: array<int, array>, contenido_descuadra: array<int, array>}
     */
    public function datosQueFaltan(): array
    {
        helper('silo');

        $piezas = $this->adjuntarAtributos(
            $this->select('silo_piezas.*, cat.nombre AS categoria_nombre')
                ->join('silo_vocabulario cat', 'cat.id = silo_piezas.categoria_id', 'left')
                ->orderBy('silo_piezas.nombre_carpeta', 'ASC')
                ->findAll()
        );

        $contenidoReal = $this->contenidoRealPorPieza(array_column($piezas, 'id'));

        $grupos = [
            'sin_tematica' => [], 'sin_lugar' => [], 'sin_personas' => [], 'mal' => [],
            'sin_etiqueta_contenido' => [], 'contenido_descuadra' => [],
        ];

        foreach ($piezas as $pieza) {
            $tipos     = array_column($pieza['atributos'] ?? [], 'tipo');
            $tieneTema = in_array('tema', $tipos, true);

            if (!$tieneTema) {
                $grupos['sin_tematica'][] = $pieza;
            }
            if (!in_array('lugar', $tipos, true)) {
                $grupos['sin_lugar'][] = $pieza;
            }
            if (!in_array('persona', $tipos, true)) {
                $grupos['sin_personas'][] = $pieza;
            }

            $motivos = $this->motivosMalFormado($pieza);
            if ($motivos !== []) {
                $pieza['motivos'] = $motivos;
                $grupos['mal'][]  = $pieza;
            }

            // Etiqueta de contenido "(Fotos + Vídeos + Montajes)" escrita en
            // la temática. Solo tiene sentido si la pieza tiene temática (si
            // no, ya sale en "Sin temática").
            if ($tieneTema) {
                $real       = $contenidoReal[(int) $pieza['id']] ?? [];
                $declaradas = $this->clavesContenidoDeclaradas($pieza['atributos'] ?? []);

                if ($declaradas === []) {
                    $pieza['contenido_real'] = $real;
                    $pieza['combo']          = $this->comboClave($real);
                    $grupos['sin_etiqueta_contenido'][] = $pieza;
                } else {
                    $faltan = array_values(array_diff(
                        array_intersect($declaradas, ['fotos', 'videos']),
                        $real
                    ));
                    if ($faltan !== []) {
                        $pieza['contenido_declarado'] = $declaradas;
                        $pieza['contenido_real']      = $real;
                        $pieza['contenido_falta']     = $faltan;
                        $pieza['combo']               = $this->comboClave($faltan);
                        $grupos['contenido_descuadra'][] = $pieza;
                    }
                }
            }
        }

        return $grupos;
    }

    /**
     * Para un conjunto de piezas, qué tipos de material detectable tiene cada
     * una de verdad en `silo_ficheros`: subconjunto ordenado de
     * ['fotos', 'videos'] con al menos un fichero. `otro` se ignora y
     * `montajes` no es un tipo de fichero (no se puede detectar).
     *
     * @param array<int, int|string> $ids
     * @return array<int, string[]>
     */
    private function contenidoRealPorPieza(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        $filas = (new SiloFicheroModel())
            ->select('pieza_id, tipo')
            ->whereIn('pieza_id', $ids)
            ->whereIn('tipo', ['foto', 'video'])
            ->groupBy('pieza_id, tipo')
            ->findAll();

        $mapa = ['foto' => 'fotos', 'video' => 'videos'];
        $out  = [];
        foreach ($filas as $f) {
            $clave = $mapa[$f['tipo']] ?? null;
            if ($clave !== null) {
                $out[(int) $f['pieza_id']][$clave] = true;
            }
        }

        foreach ($out as $piezaId => $presentes) {
            $out[$piezaId] = array_values(array_filter(
                ['fotos', 'videos'],
                static fn ($c) => isset($presentes[$c]),
            ));
        }

        return $out;
    }

    /**
     * Unión de las claves de contenido ('fotos'/'videos'/'montajes')
     * declaradas en el paréntesis final de las temáticas de la pieza
     * (silo_contenido_detectar()). `[]` si ninguna temática lo lleva.
     *
     * @param array<int, array{tipo: string, nombre: string}> $atributos
     * @return string[]
     */
    private function clavesContenidoDeclaradas(array $atributos): array
    {
        $claves = [];
        foreach ($atributos as $a) {
            if (($a['tipo'] ?? '') !== 'tema') {
                continue;
            }
            $det = silo_contenido_detectar($a['nombre'] ?? '');
            if ($det !== null) {
                foreach ($det['claves'] as $c) {
                    $claves[$c] = true;
                }
            }
        }

        return array_values(array_filter(
            ['fotos', 'videos', 'montajes'],
            static fn ($c) => isset($claves[$c]),
        ));
    }

    /**
     * Clave estable para el subfiltro por combinación: 'fotos+videos',
     * 'fotos', '' (nada)... siempre en el orden fotos → videos → montajes.
     *
     * @param string[] $claves
     */
    private function comboClave(array $claves): string
    {
        return implode('+', array_values(array_filter(
            ['fotos', 'videos', 'montajes'],
            static fn ($c) => in_array($c, $claves, true),
        )));
    }

    /**
     * Fallos "de fondo" de una pieza (los que no son un simple atributo
     * suelto que falte): sin categoría, sin fecha o nombre de carpeta fuera
     * del formato del contrato de entrada.
     *
     * @return string[]
     */
    private function motivosMalFormado(array $pieza): array
    {
        $motivos = [];

        $categoria = strtolower(trim((string) ($pieza['categoria_nombre'] ?? '')));
        if (empty($pieza['categoria_id']) || $categoria === '' || $categoria === 'sin_clasificar') {
            $motivos[] = 'sin categoría';
        }
        if (empty($pieza['fecha'])) {
            $motivos[] = 'sin fecha';
        }
        // Mismo patrón que SiloService::clasificarEntradaRoot(): "<id> <fecha> ...".
        if (!preg_match('/^\S+\s+(\d{8}|\d{6}|\d{4}|sinfecha)\b/i', trim((string) $pieza['nombre_carpeta']))) {
            $motivos[] = 'nombre fuera de formato';
        }

        return $motivos;
    }

    /**
     * Adjunta a cada pieza su lista de atributos [{tipo, nombre}] (persona,
     * lugar, tema, evento) en una sola consulta, para pintar el nombre de
     * carpeta como badges en los listados.
     */
    private function adjuntarAtributos(array $piezas): array
    {
        if ($piezas === []) {
            return $piezas;
        }

        $ids   = array_column($piezas, 'id');
        $filas = (new SiloPiezaAtributoModel())
            ->select('silo_pieza_atributo.pieza_id, silo_vocabulario.tipo, silo_vocabulario.nombre')
            ->join('silo_vocabulario', 'silo_vocabulario.id = silo_pieza_atributo.vocabulario_id')
            ->whereIn('silo_pieza_atributo.pieza_id', $ids)
            ->orderBy('silo_vocabulario.tipo', 'ASC')
            ->orderBy('silo_vocabulario.nombre', 'ASC')
            ->findAll();

        $porPieza = [];
        foreach ($filas as $f) {
            $porPieza[(int) $f['pieza_id']][] = ['tipo' => $f['tipo'], 'nombre' => $f['nombre']];
        }

        foreach ($piezas as &$p) {
            $p['atributos'] = $porPieza[(int) $p['id']] ?? [];
        }

        return $piezas;
    }
}
