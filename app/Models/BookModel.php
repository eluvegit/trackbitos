<?php

namespace App\Models;

use CodeIgniter\Model;

class BookModel extends Model
{
    protected $table         = 'books';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    public const ESTADOS = ['quiero_leer', 'leyendo', 'terminado', 'abandonado', 'pausado'];

    protected $allowedFields = [
        'task_id', 'title', 'author', 'cover_url', 'isbn', 'total_pages', 'current_page',
        'status', 'min_goal_pages', 'anchor_routine', 'rating', 'started_at', 'finished_at',
    ];

    protected $validationRules = [
        'title'          => 'required|max_length[255]',
        'min_goal_pages' => 'permit_empty|integer|greater_than[0]',
        'rating'         => 'permit_empty|integer|greater_than[0]|less_than_equal_to[5]',
    ];

    protected $validationMessages = [
        'title' => [
            'required' => 'El título es obligatorio.',
        ],
    ];

    public function getByStatus(string $status): array
    {
        return $this->where('status', $status)
            ->orderBy('updated_at', 'DESC')
            ->findAll();
    }

    /**
     * Porcentaje de progreso (0-100) si el libro tiene total_pages definido.
     */
    public function progreso(array $book): ?int
    {
        if (empty($book['total_pages'])) {
            return null;
        }

        $pct = ((int) $book['current_page'] / (int) $book['total_pages']) * 100;

        return (int) min(100, max(0, round($pct)));
    }
}
