<?php

namespace App\Libraries;

use App\Models\CarActionModel;
use App\Models\LentillasStockModel;
use App\Models\LentillasSustitucionesModel;
use App\Models\RecordatorioModel;

/**
 * Lógica común de los recordatorios, compartida por Recordatorios, Coche y
 * Lentillas (y por el panel y Telegram). Hay una sola tabla `recordatorios`:
 * Coche enseña los de categoría «vehiculo» y Lentillas los de «lentillas».
 *
 * Marcar uno como hecho hace lo mismo desde cualquier pantalla: en los de
 * Coche apunta una acción en el historial del coche; en los de Lentillas
 * apunta la sustitución y descuenta el stock.
 */
class RecordatorioServicio
{
    /** Categorías: valor => [etiqueta, icono (Bootstrap Icons o emoji)] */
    public const CATEGORIAS = [
        'vehiculo'      => ['Vehículo', 'car-front'],
        'lentillas'     => ['Lentillas', 'eye'],
        'salud'         => ['Salud', 'heart-pulse'],
        'mascota'       => ['Mascota', '🐶'],
        'documentacion' => ['Documentación', 'card-text'],
        'tecnologia'    => ['Tecnología', 'hdd-stack'],
        'hogar'         => ['Hogar', 'house'],
        'otro'          => ['Otro', 'calendar-event'],
    ];

    /** Categorías que pertenecen a un módulo: categoría => [ruta del módulo, nombre] */
    public const MODULOS = [
        'vehiculo'  => ['coche', 'Coche'],
        'lentillas' => ['lentillas', 'Lentillas'],
    ];

    /** Elementos de Lentillas que se pueden sustituir (los dos primeros descuentan stock de cada ojo). */
    public const ELEMENTOS = ['lentillas', 'lentilla izquierda', 'lentilla derecha', 'líquido', 'estuche', 'presión'];

    private RecordatorioModel $model;

    public function __construct()
    {
        helper('recordatorio');
        $this->model = new RecordatorioModel();
    }

    // ------------------------------------------------------------------
    // Lectura
    // ------------------------------------------------------------------

    /**
     * Recordatorios con su estado calculado, del más urgente al más lejano
     * (los que no tienen fecha, al final).
     *
     * @param string|null $categoria  solo los de esa categoría
     * @param bool        $soloAvisar solo los que avisan en panel/Telegram
     */
    public function lista(?string $categoria = null, bool $soloAvisar = false): array
    {
        $q = $this->model;
        if ($categoria !== null) $q = $q->where('categoria', $categoria);
        if ($soloAvisar) $q = $q->where('avisar', 1);

        $filas = array_map([$this, 'conEstado'], $q->findAll());
        usort($filas, static fn ($a, $b) => ($a['dias'] <=> $b['dias']) ?: strnatcasecmp($a['titulo'], $b['titulo']));

        return $filas;
    }

    /**
     * Añade a un recordatorio: fecha_mostrar, recalculada, dias, texto, nivel
     * (caducado | urgente | proximo | lejano | sinfecha), categoria_label,
     * periodo_texto y modulo.
     */
    public function conEstado(array $r): array
    {
        $meses = $r['periodo_meses'] ? (int) $r['periodo_meses'] : null;
        $dias  = $r['periodo_dias'] ? (int) $r['periodo_dias'] : null;

        $r['categoria_label'] = self::CATEGORIAS[$r['categoria']][0] ?? 'Otro';
        $r['periodo_texto']   = recordatorio_periodo_texto($meses, $dias);
        $r['modulo']          = self::MODULOS[$r['categoria']][0] ?? null;

        if (empty($r['fecha_evento'])) {
            return $r + [
                'fecha_mostrar' => null,
                'recalculada'   => false,
                'dias'          => PHP_INT_MAX,
                'texto'         => empty($r['ultima_vez']) ? 'Sin registros' : 'Sin plazo',
                'nivel'         => 'sinfecha',
            ];
        }

        // Los persistentes se quedan vencidos hasta que se hacen; el resto salta al siguiente ciclo.
        $fecha  = !empty($r['persistente']) ? $r['fecha_evento'] : recordatorio_fecha_efectiva($r['fecha_evento'], $meses, $dias);
        $estado = recordatorio_estado($fecha);

        return $r + [
            'fecha_mostrar' => $fecha,
            'recalculada'   => $fecha !== $r['fecha_evento'],
            'dias'          => $estado['dias'],
            'texto'         => $estado['texto'],
            'nivel'         => $estado['nivel'],
        ];
    }

    /** Los que hay que enseñar como aviso: caducados o a 30 días o menos, y con «avisar» activado. */
    public function urgentes(): array
    {
        return array_values(array_filter(
            $this->lista(null, true),
            static fn ($r) => in_array($r['nivel'], ['caducado', 'urgente'], true)
        ));
    }

    /** Recordatorio de Lentillas ligado a un elemento («lentillas», «presión»…), o null. */
    public function deElemento(string $elemento): ?array
    {
        $clave = self::norm($elemento);
        // Cambiar solo una lentilla también cuenta para el aviso de «lentillas».
        if (in_array($clave, ['LENTILLA IZQUIERDA', 'LENTILLA DERECHA'], true)) $clave = 'LENTILLAS';

        foreach ($this->model->where('categoria', 'lentillas')->orderBy('id', 'ASC')->findAll() as $r) {
            if (self::norm((string) $r['elemento']) === $clave) return $r;
        }
        return null;
    }

    // ------------------------------------------------------------------
    // Escritura
    // ------------------------------------------------------------------

    /** Fecha siguiente a partir de $base según el período del recordatorio; null si no tiene período. */
    public function siguiente(string $base, array $r): ?string
    {
        $mod = recordatorio_periodo_modify_string(
            $r['periodo_meses'] ? (int) $r['periodo_meses'] : null,
            $r['periodo_dias'] ? (int) $r['periodo_dias'] : null
        );
        return $mod === '' ? null : (new \DateTime($base))->modify($mod)->format('Y-m-d');
    }

    /**
     * Marca un recordatorio como hecho el día $fecha (hoy si no se indica):
     * apunta el historial del módulo si lo tiene y mueve la fecha siguiente.
     *
     * @param array $extra ['km' => int|null, 'notas' => string]
     * @return array|null el recordatorio actualizado con su estado, o null si no existe
     */
    public function marcarHecho(int $id, ?string $fecha = null, array $extra = []): ?array
    {
        $r = $this->model->find($id);
        if (!$r) return null;
        $fecha = $fecha ?: date('Y-m-d');
        $notas = trim((string) ($extra['notas'] ?? ''));

        if ($r['categoria'] === 'vehiculo') {
            (new CarActionModel())->insert([
                'title'           => $r['titulo'],
                'date'            => $fecha,
                'recordatorio_id' => $id,
                'kilometers'      => ($extra['km'] ?? '') !== '' && $extra['km'] !== null ? (int) $extra['km'] : null,
                'notes'           => $notas,
            ]);
            $this->recalcular($id);
        } elseif ($r['categoria'] === 'lentillas' && trim((string) $r['elemento']) !== '') {
            $this->registrarSustitucion($r['elemento'], $fecha, $notas, $id);
        } else {
            $datos = ['ultima_vez' => $fecha];
            if ($sig = $this->siguiente($fecha, $r)) $datos['fecha_evento'] = $sig;
            $this->model->skipValidation(true)->update($id, $datos);
        }

        return $this->conEstado($this->model->find($id));
    }

    /**
     * Vuelve a calcular «última vez» y la fecha siguiente de un recordatorio
     * de Coche o Lentillas a partir de su historial. Se llama al añadir,
     * editar o borrar una acción o una sustitución. Si no le queda historial,
     * solo se vacía «última vez» y la fecha no se toca.
     */
    public function recalcular(?int $id): void
    {
        $r = $id ? $this->model->find($id) : null;
        if (!$r) return;

        if ($r['categoria'] === 'vehiculo') {
            $fila = (new CarActionModel())->selectMax('date', 'f')->where('recordatorio_id', $id)->first();
        } elseif ($r['categoria'] === 'lentillas') {
            $fila = (new LentillasSustitucionesModel())->selectMax('fecha', 'f')->where('recordatorio_id', $id)->first();
        } else {
            return;
        }
        $ultima = !empty($fila['f']) ? substr((string) $fila['f'], 0, 10) : null;

        $datos = ['ultima_vez' => $ultima];
        if ($ultima && ($sig = $this->siguiente($ultima, $r))) $datos['fecha_evento'] = $sig;
        $this->model->skipValidation(true)->update($id, $datos);
    }

    /**
     * Apunta una sustitución de Lentillas, descuenta el stock y renueva el
     * recordatorio ligado a ese elemento (si lo hay).
     *
     * @return array cambios de stock: [['item', 'antes', 'despues'], ...]
     */
    public function registrarSustitucion(string $elemento, string $fecha, string $notas = '', ?int $recordatorioId = null): array
    {
        $elemento = mb_strtolower(trim($elemento));
        $recordatorioId ??= $this->deElemento($elemento)['id'] ?? null;

        (new LentillasSustitucionesModel())->insert([
            'elemento'        => $elemento,
            'fecha'           => $fecha,
            'notas'           => $notas,
            'recordatorio_id' => $recordatorioId,
        ]);

        // «lentillas» gasta una de cada ojo.
        $stock   = new LentillasStockModel();
        $cambios = [];
        foreach ($elemento === 'lentillas' ? ['lentilla izquierda', 'lentilla derecha'] : [$elemento] as $item) {
            $fila = $stock->where('item', $item)->first();
            if (!$fila) continue;
            $antes   = (int) $fila['cantidad'];
            $despues = max(0, $antes - 1);
            $stock->update($fila['id'], ['cantidad' => $despues, 'updated_at' => date('Y-m-d H:i:s')]);
            $cambios[] = ['item' => $item, 'antes' => $antes, 'despues' => $despues];
        }

        $this->recalcular($recordatorioId ? (int) $recordatorioId : null);

        return $cambios;
    }

    /**
     * Tras cambiar el elemento o la fecha de una sustitución (o borrarla):
     * la vuelve a enlazar con el recordatorio que toque y recalcula el
     * anterior y el nuevo.
     */
    public function reenlazarSustitucion(?array $antes, ?array $despues): void
    {
        $viejo = $antes['recordatorio_id'] ?? null;
        $nuevo = null;
        if ($despues) {
            $nuevo = $this->deElemento((string) $despues['elemento'])['id'] ?? null;
            (new LentillasSustitucionesModel())->update($despues['id'], ['recordatorio_id' => $nuevo]);
        }
        foreach (array_unique(array_filter([(int) $viejo, (int) $nuevo])) as $id) $this->recalcular($id);
    }

    /**
     * Guarda (crea o actualiza) un recordatorio desde el formulario común.
     * Si cambia el plazo y hay «última vez», la fecha siguiente se recalcula;
     * si cambia el nombre, el historial del coche le sigue.
     *
     * @return int id del recordatorio, o 0 si la validación falla (ver errores())
     */
    public function guardar(array $datos, ?int $id = null): int
    {
        $previo = $id ? $this->model->find($id) : null;

        if (!array_key_exists($datos['categoria'] ?? '', self::CATEGORIAS)) $datos['categoria'] = 'otro';
        $datos['icono'] = self::CATEGORIAS[$datos['categoria']][1];
        foreach (['periodo_meses', 'periodo_dias', 'intervalo_km'] as $c) {
            $datos[$c] = ($datos[$c] ?? '') !== '' && $datos[$c] !== null && (int) $datos[$c] > 0 ? (int) $datos[$c] : null;
        }
        $datos['fecha_evento'] = trim((string) ($datos['fecha_evento'] ?? '')) ?: null;
        $datos['persistente']  = empty($datos['persistente']) ? 0 : 1;
        $datos['avisar']       = empty($datos['avisar']) ? 0 : 1;
        $datos['elemento']     = $datos['categoria'] === 'lentillas' ? (mb_strtolower(trim((string) ($datos['elemento'] ?? ''))) ?: null) : null;
        if ($datos['categoria'] !== 'vehiculo') $datos['intervalo_km'] = null;

        // Plazo cambiado sin tocar la fecha: la fecha sigue al plazo nuevo desde la última vez.
        if ($previo && !empty($previo['ultima_vez'])
            && $datos['fecha_evento'] === ($previo['fecha_evento'] ?: null)
            && [$datos['periodo_meses'], $datos['periodo_dias']] !== [$previo['periodo_meses'] ? (int) $previo['periodo_meses'] : null, $previo['periodo_dias'] ? (int) $previo['periodo_dias'] : null]) {
            $datos['fecha_evento'] = $this->siguiente($previo['ultima_vez'], $datos) ?? $datos['fecha_evento'];
        }

        if ($previo) {
            if (!$this->model->update($id, $datos)) return 0;
            if ($previo['titulo'] !== $datos['titulo']) {
                (new CarActionModel())->where('recordatorio_id', $id)->set(['title' => $datos['titulo']])->update();
            }
            // Si ha cambiado de elemento (Lentillas), su historial es otro.
            if ($datos['categoria'] === 'lentillas' && self::norm((string) $previo['elemento']) !== self::norm((string) $datos['elemento'])) {
                $this->enlazarHistorialLentillas($id, (string) $datos['elemento']);
            }
            return $id;
        }

        if (!$this->model->insert($datos)) return 0;
        $id = (int) $this->model->getInsertID();
        if ($datos['categoria'] === 'lentillas' && $datos['elemento']) $this->enlazarHistorialLentillas($id, $datos['elemento']);

        return $id;
    }

    public function errores(): array
    {
        return $this->model->errors();
    }

    /** Borra un recordatorio; su historial (acciones, sustituciones) se conserva suelto. */
    public function borrar(int $id): void
    {
        (new CarActionModel())->where('recordatorio_id', $id)->set(['recordatorio_id' => null])->update();
        (new LentillasSustitucionesModel())->where('recordatorio_id', $id)->set(['recordatorio_id' => null])->update();
        $this->model->delete($id);
    }

    /** Enlaza con un recordatorio de Lentillas las sustituciones de su elemento que estén sueltas. */
    private function enlazarHistorialLentillas(int $id, string $elemento): void
    {
        $sust = new LentillasSustitucionesModel();
        $sust->where('recordatorio_id', $id)->set(['recordatorio_id' => null])->update();
        foreach ($sust->where('recordatorio_id', null)->findAll() as $s) {
            if (($this->deElemento((string) $s['elemento'])['id'] ?? null) == $id) {
                $sust->update($s['id'], ['recordatorio_id' => $id]);
            }
        }
        $this->recalcular($id);
    }

    /** Mayúsculas, sin tildes ni espacios sobrantes, para comparar nombres. */
    public static function norm(string $s): string
    {
        $s = strtr(trim($s), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        ]);
        return mb_strtoupper(preg_replace('/\s+/', ' ', $s));
    }
}
