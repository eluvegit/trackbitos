<?// app/Models/YoutubeListasModel.php
namespace App\Models;

use CodeIgniter\Model;

class YoutubeListasModel extends Model
{
    protected $table         = 'youtube_listas';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['nombre','slug'];
    protected $useTimestamps = true;

    public function findBySlug(string $slug)
    {
        return $this->where('slug',$slug)->first();
    }
}
