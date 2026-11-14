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

    /**
     * Igual que constanciaVentana() pero sumando todos los libros: un día
     * cuenta si tocaste cualquiera de ellos.
     */
    public function constanciaGlobal(int $dias = 30): array
    {
        $desde = (new \DateTime())->modify('-' . ($dias - 1) . ' days')->format('Y-m-d');

        $fechas = array_flip(array_column($this->builder()
            ->select('session_date')->distinct()
            ->where('skipped', 0)
            ->where('session_date >=', $desde)
            ->get()->getResultArray(), 'session_date'));

        $ventana = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $fecha = (new \DateTime())->modify("-{$i} days")->format('Y-m-d');
            $ventana[] = ['fecha' => $fecha, 'tocado' => isset($fechas[$fecha])];
        }

        return ['tocados' => count($fechas), 'dias' => $dias, 'ventana' => $ventana];
    }

    /** Sesión de hoy (tocada o saltada) de cada libro pedido: [book_id => sesión]. */
    public function hoyPorLibro(array $bookIds): array
    {
        if (!$bookIds) {
            return [];
        }
        $filas = $this->whereIn('book_id', $bookIds)
            ->where('session_date', date('Y-m-d'))
            ->orderBy('id', 'ASC')
            ->findAll();

        return array_column($filas, null, 'book_id');
    }

    /** Última fecha en que se tocó (no saltó) cada libro: [book_id => Y-m-d]. */
    public function ultimaPorLibro(array $bookIds): array
    {
        if (!$bookIds) {
            return [];
        }
        $filas = $this->builder()
            ->select('book_id, MAX(session_date) AS ultima')
            ->whereIn('book_id', $bookIds)
            ->where('skipped', 0)
            ->groupBy('book_id')
            ->get()->getResultArray();

        return array_column($filas, 'ultima', 'book_id');
    }

    /** Última sesión leída de cualquier libro: ['fecha' => Y-m-d, 'title' => ...] o null. */
    public function ultimaGlobal(): ?array
    {
        $fila = $this->builder()
            ->select('reading_sessions.session_date AS fecha, books.id AS book_id, books.title')
            ->join('books', 'books.id = reading_sessions.book_id')
            ->where('reading_sessions.skipped', 0)
            ->orderBy('reading_sessions.session_date', 'DESC')
            ->orderBy('reading_sessions.id', 'DESC')
            ->limit(1)
            ->get()->getRowArray();

        return $fila ?: null;
    }

    /** Totales de un libro para la ficha: sesiones, minutos, veces que se perdió el hilo. */
    public function resumenLibro(int $bookId): array
    {
        $r = $this->builder()
            ->select('COUNT(*) AS sesiones, COALESCE(SUM(minutes), 0) AS minutos, COALESCE(SUM(lost_thread_count), 0) AS hilo, MIN(session_date) AS primera, MAX(session_date) AS ultima')
            ->where('book_id', $bookId)
            ->where('skipped', 0)
            ->get()->getRowArray();

        return [
            'sesiones' => (int) ($r['sesiones'] ?? 0),
            'minutos'  => (int) ($r['minutos'] ?? 0),
            'hilo'     => (int) ($r['hilo'] ?? 0),
            'primera'  => $r['primera'] ?? null,
            'ultima'   => $r['ultima'] ?? null,
        ];
    }
}
