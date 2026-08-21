<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * La carpeta en la que vive una pieza (cuerpo, accesorio, diorama...).
 * Un nivel por encima de la familia, plano a propósito: no hay categorías
 * dentro de categorías porque las carpetas que esto reproduce tampoco
 * anidan, y un árbol de dos piezas de profundidad es más de lo que hace
 * falta para media docena de nombres.
 *
 * La unicidad del nombre la comprueba PiezaService para poder decir cuál
 * es la que ya existe; el índice único del esquema es la red de abajo.
 */
class PiezaCategoriaModel extends Model
{
    protected $table         = 'piezas_categorias';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = '';

    protected $allowedFields = ['nombre', 'orden', 'visible_sterclicks'];

    protected $validationRules = [
        'nombre' => 'required|max_length[100]',
    ];

    /**
     * En el orden en que se leen, no en el alfabético: el usuario coloca
     * sus carpetas como las tiene en la cabeza. El nombre desempata para
     * que dos categorías con el mismo `orden` (las recién creadas) no
     * bailen de una carga a otra.
     */
    public function ordenadas(): array
    {
        return $this->orderBy('orden', 'ASC')->orderBy('nombre', 'ASC')->findAll();
    }
}
