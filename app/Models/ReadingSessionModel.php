<?php

namespace App\Models;

use CodeIgniter\Model;

class ReadingSessionModel extends Model
{
    protected $table         = 'reading_sessions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $allowedFields = [
        'book_id', 'session_date', 'minutes', 'page_reached', 'note',
        'lost_thread_count', 'parked_thought', 'skipped',
    ];

    public function getForBook(int $bookId): array
    {
        return $this->where('book_id', $bookId)
            ->orderBy('session_date', 'DESC')
            ->findAll();
    }

    /**
     * La sesión (si existe) ya registrada hoy para este libro —tocada o
     * saltada, da igual—, para pintar el check binario como "ya resuelto".
     */
    public function getForToday(int $bookId): ?array
    {
        return $this->where('book_id', $bookId)
            ->where('session_date', date('Y-m-d'))
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * Constancia como ventana móvil: "X de los últimos N días tocaste el
     * libro" —nunca una racha que se resetea a 0 y se ve como una alarma—.
     * Solo cuentan los días con sesión "tocada" (skipped = 0); un "hoy no
     * toca" no resta ni suma, simplemente no cuenta como día tocado.
     */
    public function constanciaVentana(int $bookId, int $dias = 21): array
    {
        $desde = (new \DateTime())->modify('-' . ($dias - 1) . ' days')->format('Y-m-d');

        $fechasTocadas = array_column($this->where('book_id', $bookId)
            ->where('skipped', 0)
            ->where('session_date >=', $desde)
            ->distinct()
            ->select('session_date')
            ->findAll(), 'session_date');

        $fechasTocadas = array_flip($fechasTocadas);

        $diasVentana = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $fecha = (new \DateTime())->modify("-{$i} days")->format('Y-m-d');
            $diasVentana[] = ['fecha' => $fecha, 'tocado' => isset($fechasTocadas[$fecha])];
        }

        return [
            'tocados' => count($fechasTocadas),
            'dias'    => $dias,
            'ventana' => $diasVentana,
        ];
    }
}
