<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Tipo de pieza de una tirada (brazos, torsos…): resina, cuántas caben por
 * placa y cuántas lleva cada muñeco. "sexuado" = hay versión de hombre y de
 * mujer; "bloqueado" = espera a la confirmación del diseño.
 */
class PiezaTiradaTipoModel extends Model
{
    protected $table      = 'piezas_tiradas_tipos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = ['tirada_id', 'nombre', 'resina', 'por_placa', 'por_muneco', 'sexuado', 'bloqueado', 'color', 'orden'];

    public function deTirada(int $tiradaId): array
    {
        return $this->where('tirada_id', $tiradaId)->orderBy('orden')->orderBy('id')->findAll();
    }
}
