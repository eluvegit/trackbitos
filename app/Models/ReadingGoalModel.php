<?php

namespace App\Models;

use CodeIgniter\Model;

class ReadingGoalModel extends Model
{
    protected $table         = 'reading_goals';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';

    protected $allowedFields = ['year', 'target_books'];

    public function forYear(int $year): ?array
    {
        return $this->where('year', $year)->first();
    }
}
