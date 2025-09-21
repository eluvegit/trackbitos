<?php
namespace App\Controllers;

use App\Models\{RodajesProyectoModel, RodajesEscenaModel, RodajesEscenaImagenModel};

class RodajesEscenas extends BaseController
{
    // Carpeta pública donde se guardarán las imágenes
    protected $publicDir = 'images/rodajes'; // dentro de /public

    public function index($proyectoId)
    {
        $proyectos = new RodajesProyectoModel();
        $escenas   = new RodajesEscenaModel();

        $proyecto = $proyectos->find($proyectoId);
        if (!$proyecto) {
            return redirect()->to(site_url('rodajes'));
        }

        $data['proyecto'] = $proyecto;
        $data['escenas']  = $escenas->where('proyecto_id', $proyectoId)
                                    ->orderBy('orden', 'ASC')
                                    ->orderBy('id', 'ASC')
                                    ->findAll();

        return view('rodajes/escenas/index', $data);
    }

    public function create($proyectoId)
    {
        $proyectos = new RodajesProyectoModel();
        $data['proyecto'] = $proyectos->find($proyectoId);
        if (!$data['proyecto']) {
            return redirect()->to(site_url('rodajes'));
        }
        $data['escena'] = null;
        return view('rodajes/escenas/form', $data);
    }

    public function store($proyectoId)
    {
        $escenas  = new RodajesEscenaModel();
        $this->request->setGlobal('post', array_merge(
            $this->request->getPost(),
            [
                'proyecto_id'      => $proyectoId,
                'plano_actores'    => $this->request->getPost('plano_actores') ? 'S' : 'N',
                'sonido_ambiente'  => $this->request->getPost('sonido_ambiente') ? 'S' : 'N',
                'sonido_antiviento'=> $this->request->getPost('sonido_antiviento') ? 'S' : 'N',
            ]
        ));

        $post = $this->request->getPost();

        if (!$escenas->save($post)) {
            return redirect()->back()->withInput()->with('errors', $escenas->errors());
        }
        $escenaId = $escenas->getInsertID();

        // Subidas múltiples por categoría -> a /public/images/rodajes/{escenaId}/
        $this->handleUploads($proyectoId, $escenaId, 'lugar_objetos');
        $this->handleUploads($proyectoId, $escenaId, 'inspiracion');

        return redirect()->to(site_url("rodajes/$proyectoId/escenas"));
    }

    public function edit($proyectoId, $id)
    {
        $proyectos = new RodajesProyectoModel();
        $escenas   = new RodajesEscenaModel();
        $imgs      = new RodajesEscenaImagenModel();

        $data['proyecto'] = $proyectos->find($proyectoId);
        $data['escena']   = $escenas->find($id);
        if (!$data['proyecto'] || !$data['escena']) {
            return redirect()->to(site_url("rodajes/$proyectoId/escenas"));
        }

        $data['imagenes_lugar'] = $imgs->where(['escena_id'=>$id,'categoria'=>'lugar_objetos'])->findAll();
        $data['imagenes_insp']  = $imgs->where(['escena_id'=>$id,'categoria'=>'inspiracion'])->findAll();

        return view('rodajes/escenas/form', $data);
    }

    public function update($proyectoId, $id)
    {
        $escenas = new RodajesEscenaModel();

        $post = $this->request->getPost();
        $post['id']               = $id;
        $post['proyecto_id']      = $proyectoId;
        $post['plano_actores']    = $this->request->getPost('plano_actores') ? 'S' : 'N';
        $post['sonido_ambiente']  = $this->request->getPost('sonido_ambiente') ? 'S' : 'N';
        $post['sonido_antiviento']= $this->request->getPost('sonido_antiviento') ? 'S' : 'N';

        if (!$escenas->save($post)) {
            return redirect()->back()->withInput()->with('errors', $escenas->errors());
        }

        // Nuevas subidas (opcionales)
        $this->handleUploads($proyectoId, $id, 'lugar_objetos');
        $this->handleUploads($proyectoId, $id, 'inspiracion');

        return redirect()->to(site_url("rodajes/$proyectoId/escenas"));
    }

    public function delete($proyectoId, $id)
    {
        $escenas = new RodajesEscenaModel();
        $escenas->delete($id);
        return redirect()->to(site_url("rodajes/$proyectoId/escenas"));
    }

    public function deleteImage($proyectoId, $escenaId, $imageId)
    {
        $imgs = new RodajesEscenaImagenModel();
        $img  = $imgs->find($imageId);
        if ($img) {
            // Ruta pública
            $path = FCPATH . ltrim($img['ruta'], '/'); // ruta relativa guardada a /public
            if (is_file($path)) {
                @unlink($path);
            }
            $imgs->delete($imageId);
        }
        return redirect()->to(site_url("rodajes/$proyectoId/escenas/edit/$escenaId"));
    }

    /**
     * Guarda imágenes en /public/images/rodajes/{escenaId}/ y persiste rutas públicas.
     * $categoria: 'lugar_objetos' | 'inspiracion'
     */
    protected function handleUploads(int $proyectoId, int $escenaId, string $categoria): void
    {
        $files = $this->request->getFiles();
        if (!isset($files[$categoria])) {
            return;
        }

        // Carpeta pública: /public/images/rodajes/{escenaId}/
        $targetDir = rtrim($this->publicDir, '/').'/'.$escenaId;
        $absDir    = rtrim(FCPATH, '/').'/'.$targetDir;

        if (!is_dir($absDir)) {
            @mkdir($absDir, 0775, true);
        }

        $imgs = new RodajesEscenaImagenModel();

        // Múltiples archivos: name="lugar_objetos[]" / "inspiracion[]"
        foreach ($files[$categoria] as $file) {
            if (!$file->isValid() || $file->hasMoved()) {
                continue;
            }

            // Seguridad básica: solo imágenes
            $mime = $file->getMimeType();
            if (strpos($mime, 'image/') !== 0) {
                continue;
            }

            // Nombre aleatorio para evitar colisiones
            $newName = $file->getRandomName();
            $file->move($absDir, $newName);

            // Ruta pública relativa para usar con base_url()
            $relative = $targetDir . '/' . $newName; // p.ej. images/rodajes/123/xxxx.jpg

            $imgs->insert([
                'escena_id' => $escenaId,
                'categoria' => $categoria,
                'ruta'      => $relative, // almacenamos ruta pública
            ]);
        }
    }
}
