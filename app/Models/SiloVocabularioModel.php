<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Vocabulario abierto de Silo (categoría/evento/lugar/persona/tema) en una
 * única tabla, diferenciado por `tipo`. El alta get-or-create vive en
 * SiloService::getOrCreateVocabulario(), no aquí — este modelo es solo
 * acceso a datos.
 */
class SiloVocabularioModel extends Model
{
    protected $table         = 'silo_vocabulario';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = '';

    protected $allowedFields = ['tipo', 'nombre', 'slug'];

    protected $validationRules = [
        'tipo'   => 'required|in_list[categoria,evento,lugar,persona,tema]',
        'nombre' => 'required|max_length[150]',
        'slug'   => 'required|max_length[150]',
    ];

    public function porTipo(string $tipo): array
    {
        return $this->where('tipo', $tipo)->orderBy('nombre', 'ASC')->findAll();
    }
}
