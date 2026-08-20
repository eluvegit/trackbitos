<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Las preguntas que llevaba una placa y qué se respondió al mirarla impresa
 * (fase 38). Pregunta y respuesta viven en dos momentos distintos —una antes
 * de imprimir, otra después—, de ahí que sean dos columnas y no una nota
 * suelta: al volver meses más tarde hay que poder distinguir qué se quería
 * averiguar de qué se averiguó.
 */
class PiezaPlacaPruebaModel extends Model
{
    protected $table         = 'piezas_placa_pruebas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['placa_id', 'pregunta', 'respuesta', 'orden'];

    protected $validationRules = [
        'pregunta' => 'required|max_length[255]',
    ];
}
