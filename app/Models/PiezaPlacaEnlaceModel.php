<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Lo que vive fuera de Trackbitos y pertenece a esa impresión (fase 39): el
 * proyecto del laminador en Drive, la carpeta de fotos de cómo salió, el hilo
 * donde estaba la receta de exposición.
 *
 * Tabla propia y no una columna en la placa porque de una misma tanda suelen
 * colgar varios sitios, y amontonarlos en un campo de texto los deja sin
 * poder pinchar. Se reescriben enteros al guardar la bitácora, igual que las
 * pruebas: la lista es corta y se edita de una vez.
 */
class PiezaPlacaEnlaceModel extends Model
{
    protected $table         = 'piezas_placa_enlaces';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['placa_id', 'url', 'titulo', 'orden'];

    protected $validationRules = [
        // Sin `valid_url`: aquí se pega lo que haya en el portapapeles y el
        // controlador ya le pone el https:// delante si faltaba. Rechazar por
        // la forma exacta de la URL solo conseguiría perder el enlace.
        'url'    => 'required|max_length[700]',
        'titulo' => 'permit_empty|max_length[150]',
    ];
}
