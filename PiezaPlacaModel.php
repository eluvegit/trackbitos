<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Una placa descargada: fecha, nombre (autogenerado, editable) y la
 * bitácora de esa impresión (fase 38) — cuándo se imprimió de verdad, la
 * exposición, el peso de resina antes y después, y las notas y conclusiones
 * para la próxima. El contenido real —qué versiones llevaba y cuántas copias
 * de cada una— vive en PiezaPlacaVersionModel; las pruebas, en
 * PiezaPlacaPruebaModel; los enlaces a lo que hay fuera (Drive, fotos), en
 * PiezaPlacaEnlaceModel.
 */
class PiezaPlacaModel extends Model
{
    protected $table         = 'piezas_placas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = '';

    /**
     * Cómo salió la placa, en una palabra. Mismo espíritu que el veredicto de
     * una versión (impresa → validada/descartada): mientras no se juzga es
     * null, no "regular" — no haber mirado todavía no es una nota media.
     */
    public const VEREDICTOS = [
        'buena'   => 'Salió bien',
        'regular' => 'Bien, pero con fallos',
        'repetir' => 'Hay que repetirla',
    ];

    protected $allowedFields = [
        'nombre', 'impresa_en', 'exposicion', 'peso_antes', 'peso_despues',
        'notas', 'conclusiones',
        'resina', 'temperatura', 'veredicto',
        'minutos_estimados', 'minutos_previstos', 'minutos_reales', 'numero_capas', 'resina_estimada',
        'origen_placa_id', 'es_reparto', 'descargada_en', 'pedido_id',
        'inventario_sincronizado_en',
    ];

    protected $validationRules = [
        'nombre' => 'required|max_length[150]',
        // La bitácora se rellena a trozos y a destiempo, así que todo lo suyo
        // es opcional; solo se comprueba que lo que llegue quepa y sea un peso
        // creíble (permit_empty deja pasar el campo en blanco).
        'exposicion'   => 'permit_empty|max_length[255]',
        'peso_antes'   => 'permit_empty|decimal|greater_than_equal_to[0]|less_than[1000000]',
        'peso_despues' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than[1000000]',
        'resina_estimada' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than[1000000]',
        'resina'       => 'permit_empty|max_length[120]',
        'temperatura'  => 'permit_empty|decimal|greater_than_equal_to[-50]|less_than[200]',
        'veredicto'    => 'permit_empty|in_list[buena,regular,repetir]',
        // Sin tope realista fijado: la mayoría de piezas rondan las
        // centenas, pero una pieza alta a capa fina puede pasar de mil.
        'numero_capas' => 'permit_empty|is_natural_no_zero',
    ];

    public const FUENTES_TIEMPO = [
        'minutos_estimados' => 'Programa',
        'minutos_previstos' => 'Máquina',
        'minutos_reales'    => 'Real',
    ];

    // minutos = porCapa · capas, por mínimos cuadrados sobre las placas con capas.
    public function reglasTiempo(): array
    {
        $filas = $this->select('numero_capas, ' . implode(', ', array_keys(self::FUENTES_TIEMPO)))
            ->where('numero_capas >', 0)
            ->findAll();

        $reglas = [];
        foreach (self::FUENTES_TIEMPO as $campo => $etiqueta) {
            $puntos = [];
            foreach ($filas as $f) {
                if ($f[$campo] !== null && (float) $f[$campo] > 0) {
                    $puntos[] = [(float) $f['numero_capas'], (float) $f[$campo]];
                }
            }
            $reglas[$campo] = ['etiqueta' => $etiqueta, 'n' => count($puntos)] + $this->ajusteLineal($puntos);
        }

        return $reglas;
    }

    private function ajusteLineal(array $puntos): array
    {
        if (!$puntos) {
            return ['porCapa' => null];
        }

        return ['porCapa' => array_sum(array_map(fn ($p) => $p[0] * $p[1], $puntos))
            / array_sum(array_map(fn ($p) => $p[0] ** 2, $puntos))];
    }
}
