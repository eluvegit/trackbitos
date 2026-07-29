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

    public function baseQuery(int $listaId, array $filters = [], string $orden = '')
    {
        $qb = $this->where('lista_id', $listaId);

        // Filtros
        if (isset($filters['solo_no_vistos']) && $filters['solo_no_vistos']) {
            $qb->where('visto', 0);
        }
        if (isset($filters['solo_relevantes']) && $filters['solo_relevantes']) {
            $qb->where('relevante', 1);
        }

        // Ordenación: 'posicion' hace de criterio principal salvo que se pida
        // ordenar por recientes, y de desempate en el resto de casos.
        switch ($orden) {
            case 'recientes':
                $qb->orderBy('posicion', 'DESC');
                break;
            case 'antiguos':
                $qb->orderBy('posicion', 'ASC');
                break;
            case 'no_vistos':
                $qb->orderBy('visto', 'ASC')->orderBy('posicion', 'ASC');
                break;
            case 'vistos':
                $qb->orderBy('visto', 'DESC')->orderBy('posicion', 'ASC');
                break;
            case 'relevantes':
                $qb->orderBy('relevante', 'DESC')->orderBy('posicion', 'ASC');
                break;
            case '':
            default:
                // Por defecto (sin filtro elegido): no vistos primero y, entre ellos, los más recientes.
                $qb->orderBy('visto', 'ASC')->orderBy('posicion', 'DESC');
                break;
        }

        return $qb;
    }
}
