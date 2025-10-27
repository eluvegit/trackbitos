<?php
// app/Models/YoutubeVideosModel.php
namespace App\Models;

use CodeIgniter\Model;

class YoutubeVideosModel extends Model
{
    protected $table         = 'youtube_videos';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['lista_id','posicion','url','video_id','titulo','visto','relevante', 'largo'];
    protected $useTimestamps = false;

    public function baseQuery(int $listaId, array $filters = [], array $sort = [])
    {
        $qb = $this->where('lista_id', $listaId);

        // Filtros
        if (isset($filters['solo_no_vistos']) && $filters['solo_no_vistos']) {
            $qb->where('visto', 0);
        }
        if (isset($filters['solo_relevantes']) && $filters['solo_relevantes']) {
            $qb->where('relevante', 1);
        }

        // Ordenación
        // 1) orden original (posicion ASC) — siempre primero
        $qb->orderBy('posicion','ASC');
        // 2) “vistos” (los no vistos primero si sort['vistos']='no_vistos_primero')
        if (!empty($sort['vistos']) && $sort['vistos'] === 'no_vistos_primero') {
            $qb->orderBy('visto','ASC');
        } elseif (!empty($sort['vistos']) && $sort['vistos'] === 'vistos_primero') {
            $qb->orderBy('visto','DESC');
        }
        // 3) “relevantes” (relevantes primero)
        if (!empty($sort['relevantes']) && $sort['relevantes'] === 'primero') {
            $qb->orderBy('relevante','DESC');
        }

        return $qb;
    }
}
