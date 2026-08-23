<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;

/**
 * Cada STL de una versión: normalmente uno, pero un modelo se imprime a
 * trozos más veces de lo que parece (los dos brazos por separado, una pieza
 * más alta que la placa cortada en dos). El `.blend` sigue siendo uno solo
 * — ahí están todas las partes juntas.
 *
 * Inmutable como el resto de lo que cuelga de una versión (invariante 4):
 * un STL se añade o se aparta a la papelera, nunca se sobreescribe. Si el
 * modelo cambió, eso es una versión nueva, no un reemplazo silencioso del
 * fichero con el que ya se imprimió.
 */
class PiezaVersionStlModel extends Model
{
    protected $table         = 'piezas_version_stls';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'version_id', 'nombre', 'ruta_stl', 'hash_stl', 'tamano_bytes',
        'ancho_mm', 'fondo_mm', 'subido_en',
    ];

    protected $validationRules = [
        'version_id' => 'required|integer',
        'nombre'     => 'required|max_length[150]',
    ];

    /**
     * Los de una versión, en el orden en que se subieron: es el orden en que
     * se imprimieron, que es más útil que el alfabético para reproducir una
     * tanda.
     */
    public function deVersion(int $versionId): array
    {
        return $this->where('version_id', $versionId)->orderBy('id', 'ASC')->findAll();
    }

    /**
     * @param int[] $versionIds
     * @return array<int, array> STLs agrupados por version_id
     */
    public function porVersiones(array $versionIds): array
    {
        if ($versionIds === []) {
            return [];
        }

        $agrupados = [];
        foreach ($this->whereIn('version_id', $versionIds)->orderBy('id', 'ASC')->findAll() as $stl) {
            $agrupados[(int) $stl['version_id']][] = $stl;
        }

        return $agrupados;
    }

    /**
     * El nombre distingue un trozo de otro dentro de la misma versión, así
     * que repetirlo dejaría dos ficheros indistinguibles al bajarlos. Se
     * comprueba aquí además del índice único para poder decir cuál choca.
     */
    public function exigirNombreLibre(int $versionId, string $nombre): string
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            throw new RuntimeException('El STL necesita un nombre: es lo que distingue un trozo de otro.');
        }

        foreach ($this->deVersion($versionId) as $stl) {
            if (mb_strtolower($stl['nombre']) === mb_strtolower($nombre)) {
                throw new RuntimeException(
                    "Esta versión ya tiene un STL llamado \"{$stl['nombre']}\". Ponle otro nombre "
                    . 'o quita el que hay.'
                );
            }
        }

        return $nombre;
    }
}
