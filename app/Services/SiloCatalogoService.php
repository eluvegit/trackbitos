<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * Réplica del catálogo de Silo en cada unidad (docs/silo-ingesta-propagacion.md
 * § "Réplica de la base de datos en cada unidad"): un volcado SQL comprimido
 * (`.catalogo.sql.gz`) de TODAS las tablas `silo_*` — solo metadatos, sin
 * binarios — más un `.catalogo.meta.json` con su sello de versión. Lo
 * escribe el agente `.py` en la raíz de cada unidad al final de cada pasada;
 * es una red de seguridad para reconstruir la web si se pierde la BD, nadie
 * lo consulta en vivo.
 *
 * El SQL lo genera PHP (no `mysqldump`, que no tiene por qué existir en el
 * servidor): una sentencia por línea, así `restaurar()` lo re-ejecuta
 * línea a línea sin parser, y sigue siendo SQL válido para
 * `gunzip -c .catalogo.sql.gz | mysql <bd>` a mano.
 *
 * Para saber si una réplica es "más nueva" que la BD viva se usa
 * `ultimo_evento_id`: `silo_eventos` solo crece (cada escaneo registra al
 * menos su resumen), así que si un disco trae un id mayor que el máximo
 * vivo, la BD viva perdió datos o se revirtió.
 */
class SiloCatalogoService
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    /** Tablas que entran en la réplica: todas las de Silo. */
    public function tablas(): array
    {
        return array_values(array_filter(
            $this->db->listTables(),
            static fn (string $t) => str_starts_with($t, 'silo_')
        ));
    }

    /** Sello de la BD viva en este momento (lo que va en `.catalogo.meta.json`). */
    public function metaActual(?int $unidadOrigen = null): array
    {
        $tablas = [];
        foreach ($this->tablas() as $t) {
            $tablas[$t] = $this->db->table($t)->countAllResults();
        }

        $migracion = $this->db->tableExists('migrations')
            ? $this->db->table('migrations')->selectMax('version')->get()->getRow('version')
            : null;

        return [
            'formato'          => 1,
            'generado_en'      => date('c'),
            'version_esquema'  => $migracion,
            'ultimo_evento_id' => (int) $this->db->table('silo_eventos')->selectMax('id')->get()->getRow('id'),
            'unidad_origen'    => $unidadOrigen,
            'tablas'           => $tablas,
        ];
    }

    /**
     * El volcado entero en texto (sin comprimir). Pesa pocos MB aun con
     * decenas de miles de ficheros catalogados, así que se arma en memoria.
     */
    public function volcarSql(array $meta): string
    {
        $lineas   = [];
        $lineas[] = '-- Silo: réplica del catálogo. ' . json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $lineas[] = '-- Restaurar: php spark silo:restaurar <este fichero>   (o: gunzip -c .catalogo.sql.gz | mysql <bd>)';
        $lineas[] = 'SET NAMES utf8mb4;';
        $lineas[] = 'SET FOREIGN_KEY_CHECKS=0;';

        foreach ($this->tablas() as $tabla) {
            $crear = $this->db->query('SHOW CREATE TABLE `' . $tabla . '`')->getRowArray();
            $lineas[] = 'DROP TABLE IF EXISTS `' . $tabla . '`;';
            // Una sola línea por sentencia (ver restaurar()).
            $lineas[] = preg_replace('/\s*\R\s*/', ' ', (string) ($crear['Create Table'] ?? '')) . ';';

            $offset = 0;
            do {
                $filas = $this->db->table($tabla)->limit(500, $offset)->get()->getResultArray();
                if ($filas) {
                    $columnas = '`' . implode('`, `', array_keys($filas[0])) . '`';
                    $valores  = [];
                    foreach ($filas as $fila) {
                        $valores[] = '(' . implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : $this->db->escape($v), $fila)) . ')';
                    }
                    $lineas[] = 'INSERT INTO `' . $tabla . '` (' . $columnas . ') VALUES ' . implode(', ', $valores) . ';';
                }
                $offset += 500;
            } while (count($filas) === 500);
        }

        $lineas[] = 'SET FOREIGN_KEY_CHECKS=1;';

        return implode("\n", $lineas) . "\n";
    }

    /** Volcado comprimido + su meta, listo para mandar al agente o guardar. */
    public function volcar(?int $unidadOrigen = null): array
    {
        $meta = $this->metaActual($unidadOrigen);
        $gz   = gzencode($this->volcarSql($meta), 6);
        $meta['bytes_gz']   = strlen($gz);
        $meta['sha256_gz']  = hash('sha256', $gz);

        return ['meta' => $meta, 'gz' => $gz];
    }

    /**
     * ¿Esta réplica (su meta) es más nueva que la BD viva? Solo compara
     * `ultimo_evento_id` (ver cabecera de la clase).
     */
    public function esMasNuevaQueLaViva(array $meta): bool
    {
        $vivo = (int) $this->db->table('silo_eventos')->selectMax('id')->get()->getRow('id');

        return (int) ($meta['ultimo_evento_id'] ?? 0) > $vivo;
    }

    /**
     * Restaura las tablas `silo_*` desde un `.catalogo.sql.gz`: antes guarda
     * una copia del estado actual en writable/silo/ por si hay que volver
     * atrás. Solo toca tablas de Silo (el volcado no trae otras).
     *
     * @return array{sentencias: int, copia_previa: string}
     */
    public function restaurar(string $rutaGz): array
    {
        $contenido = @gzdecode((string) @file_get_contents($rutaGz));
        if ($contenido === false || $contenido === '') {
            throw new \RuntimeException("No se pudo leer/descomprimir {$rutaGz}.");
        }
        if (!str_starts_with($contenido, '-- Silo: réplica del catálogo.')) {
            throw new \RuntimeException("{$rutaGz} no parece una réplica del catálogo de Silo.");
        }

        $directorio = WRITEPATH . 'silo/';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }
        $copiaPrevia = $directorio . 'catalogo-antes-de-restaurar-' . date('Ymd-His') . '.sql.gz';
        file_put_contents($copiaPrevia, $this->volcar()['gz']);

        $sentencias = 0;
        foreach (preg_split('/\R/', $contenido) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '--')) {
                continue;
            }
            $this->db->query($linea);
            $sentencias++;
        }

        return ['sentencias' => $sentencias, 'copia_previa' => $copiaPrevia];
    }
}
