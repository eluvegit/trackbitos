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
}
