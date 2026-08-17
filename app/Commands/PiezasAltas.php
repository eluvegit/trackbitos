<?php

namespace App\Commands;

use App\Models\PiezaCategoriaModel;
use App\Models\PiezaFamiliaModel;
use App\Models\PiezaVarianteModel;
use App\Services\PiezaService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Alta inicial del catálogo: las piezas que ya existían modeladas antes de
 * Trackbitos, con sus categorías y variantes (spec 11.1).
 *
 * Da de alta los REGISTROS, no los ficheros: cada variante queda con su
 * rama de trabajo abierta, esperando el primer `trackbitos abrir` + `subir`.
 * Los `.blend` y los `.stl` siguen subiéndose pieza a pieza — esto solo
 * ahorra los veinticuatro formularios.
 *
 * Pasa por PiezaService a propósito, en vez de por INSERTs: crear una
 * variante no es una fila, es la variante MÁS su rama abierta (invariante 2),
 * y los verbos ya saben hacerlo bien. Un SQL a mano se dejaría las ramas y
 * las piezas nacerían muertas: sin rama no hay dónde abrir sesión.
 *
 *   php spark piezas:altas --simular   # qué haría, sin tocar nada
 *   php spark piezas:altas
 *
 * Reejecutable: lo que ya existe se salta. Una pieza que ya esté dada de
 * alta NO se toca (ni siquiera para añadirle variantes que falten), para que
 * una segunda pasada no pueda estropear algo ya ajustado a mano.
 *
 * Comando de un solo uso: una vez ejecutado en producción se puede borrar.
 */
class PiezasAltas extends BaseCommand
{
    protected $group       = 'Piezas';
    protected $name        = 'piezas:altas';
    protected $description = 'Alta inicial del catálogo de piezas, con sus categorías y variantes.';
    protected $usage       = 'piezas:altas [--simular]';

    /**
     * El catálogo, tal cual está organizado en disco. Cada pieza es
     * [nombre, [variantes...]]; sin variantes nombradas, la pieza nace con
     * una sola llamada "base" (que el listado ni siquiera muestra).
     */
    private const CATALOGO = [
        'Objetos' => [
            ['Lupa'],
            ['Micrófono'],
            ['Rastrillo'],
            ['Zoleta'],
            ['Libro'],
            ['Flores'],
            ['Mojón'],
            ['Mini playmobil'],
            ['Copa'],
            ['Pistola', ['pequeña', 'grande']],
            ['Silla', ['pequeña', 'grande']],
        ],
        'Cuerpo' => [
            ['Brazo integral'],
            ['Brazo'],
            ['Mano'],
            // Un solo .blend con las dos piezas dentro: entra como pieza
            // propia, no puede colgar a la vez de "Brazo" y de "Mano".
            ['Brazo y mano'],
            ['Estructura interior'],
            ['Pelo'],
            ['Torso'],
            // Ensamblaje. Trackbitos no relaciona piezas entre sí: si cambia
            // el torso, esta no se entera. Conviene anotarlo en el `cambio`
            // de cada versión suya.
            ['Completo'],
            ['Cabeza', ['normal', 'calva']],
            ['Piernas', ['rectas']],
        ],
        'Otros' => [
            ['Junta pistola'],
        ],
        // Calibraciones: se imprimen y se miran, no llegan a validarse
        // nunca. Se quedarán siempre en "sin versión buena", y es correcto.
        'Pruebas' => [
            ['Números'],
            ['Modelo conos'],
        ],
        // Nace vacía, a la espera de sus montajes.
        'Presentaciones' => [],
    ];

    public function run(array $params)
    {
        $simular = in_array('--simular', $params, true) || CLI::getOption('simular');

        $servicio   = new PiezaService();
        $categorias = new PiezaCategoriaModel();
        $familias   = new PiezaFamiliaModel();
        $variantes  = new PiezaVarianteModel();

        if ($simular) {
            CLI::write('SIMULACIÓN: no se va a escribir nada.', 'yellow');
        }

        $creadas = $saltadas = 0;

        foreach (self::CATALOGO as $nombreCategoria => $piezas) {
            $categoria = $this->buscarPorNombre($categorias->findAll(), $nombreCategoria);

            if ($categoria) {
                CLI::write("· categoría {$nombreCategoria}: ya existe", 'dark_gray');
            } else {
                CLI::write("+ categoría {$nombreCategoria}", 'green');
                $categoria = $simular ? ['id' => 0] : $servicio->crearCategoria($nombreCategoria);
            }

            foreach ($piezas as $pieza) {
                [$nombrePieza, $nombresVariantes] = [$pieza[0], $pieza[1] ?? []];

                if ($this->buscarPorNombre($familias->findAll(), $nombrePieza)) {
                    CLI::write("  · {$nombrePieza}: ya existe, no la toco", 'dark_gray');
                    $saltadas++;

                    continue;
                }

                $detalle = $nombresVariantes === []
                    ? ''
                    : ' (' . implode(', ', $nombresVariantes) . ')';
                CLI::write("  + {$nombrePieza}{$detalle}", 'green');
                $creadas++;

                if ($simular) {
                    continue;
                }

                try {
                    $creada = $servicio->crearFamilia($nombrePieza, null, null, (int) $categoria['id']);

                    // La pieza nace con una variante "base". Cuando la pieza
                    // tiene varias líneas de diseño, esa primera se renombra
                    // en vez de crear una más: "base" y "grande" no se leen
                    // como una pareja, "pequeña" y "grande" sí.
                    if ($nombresVariantes !== []) {
                        $servicio->renombrarVariante((int) $creada['variante']['id'], $nombresVariantes[0]);

                        foreach (array_slice($nombresVariantes, 1) as $nombreVariante) {
                            $servicio->crearVariante((int) $creada['familia']['id'], $nombreVariante);
                        }
                    }
                } catch (Throwable $e) {
                    CLI::write('    FALLÓ: ' . $e->getMessage(), 'red');
                    $creadas--;
                }
            }
        }

        CLI::newLine();
        CLI::write(sprintf('%d pieza(s) %s, %d ya estaban.', $creadas, $simular ? 'se crearían' : 'creadas', $saltadas));

        if (!$simular) {
            // Comprobación explícita del invariante que un SQL a mano se
            // habría dejado: sin rama abierta la pieza no admite sesiones.
            $sinRama = $this->variantesSinRamaAbierta($variantes);
            CLI::write(
                $sinRama === 0
                    ? 'Todas las variantes tienen su rama de trabajo abierta.'
                    : "AVISO: {$sinRama} variante(s) sin rama abierta.",
                $sinRama === 0 ? 'green' : 'red'
            );
            CLI::write('Ahora, por cada pieza: trackbitos abrir "<pieza>" → copiar el .blend → subir → cerrar → promocionar.', 'dark_gray');
        }
    }

    /** Comparación insensible a mayúsculas y acentos ya normalizados por el propio texto. */
    private function buscarPorNombre(array $filas, string $nombre): ?array
    {
        foreach ($filas as $fila) {
            if (mb_strtolower($fila['nombre']) === mb_strtolower($nombre)) {
                return $fila;
            }
        }

        return null;
    }

    private function variantesSinRamaAbierta(PiezaVarianteModel $variantes): int
    {
        return $variantes->db->table('piezas_variantes v')
            ->where('NOT EXISTS (SELECT 1 FROM piezas_ramas r WHERE r.variante_id = v.id AND r.abierta = 1)', null, false)
            ->countAllResults();
    }
}
