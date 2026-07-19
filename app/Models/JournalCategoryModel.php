<?php

namespace App\Models;

use CodeIgniter\Model;

class JournalCategoryModel extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'id';

    // Campos permitidos para insert/update
    protected $allowedFields = ['name', 'color', 'icon', 'description', 'peso'];

    // Timestamps automáticos
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Obtener todas las categorías, ordenadas por nombre
     *
     * @return array
     */
    public function getAll(): array
    {
         return $this->orderBy('group_order', 'ASC')
                ->orderBy('name', 'ASC')
                ->findAll();
    }

    /**
     * Obtener categoría por ID
     *
     * @param int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        return $this->find($id);
    }
}