<?php

namespace App\Controllers;

use Myth\Auth\Entities\User;
use Myth\Auth\Models\GroupModel;
use Myth\Auth\Models\UserModel;

/**
 * Alta y gestión de usuarios desde la web (solo admin), para no depender de
 * spark en producción. Cada usuario tiene un único grupo: 'admin' o
 * 'inventario'.
 */
class Usuarios extends BaseController
{
    private const GRUPOS = ['admin', 'inventario'];

    private UserModel $users;
    private GroupModel $grupos;

    public function __construct()
    {
        $this->users  = model(UserModel::class);
        $this->grupos = model(GroupModel::class);
    }

    public function index()
    {
        $filas = [];
        foreach ($this->users->orderBy('username')->findAll() as $u) {
            $grupos  = array_column($this->grupos->getGroupsForUser((int) $u->id), 'name');
            $filas[] = ['user' => $u, 'grupo' => $grupos[0] ?? null];
        }

        return view('cuenta/usuarios', [
            'filas'  => $filas,
            'grupos' => self::GRUPOS,
            'yo'     => (int) user_id(),
        ]);
    }

    public function crear()
    {
        $rules = [
            'username'     => 'required',
            'email'        => 'required|valid_email',
            'password'     => 'required|min_length[10]',
            'pass_confirm' => 'required|matches[password]',
            'grupo'        => 'required|in_list[' . implode(',', self::GRUPOS) . ']',
        ];
        if (! $this->validate($rules)) {
            return $this->volver()->withInput()->with('errors', $this->validator->getErrors());
        }

        $user = new User([
            'username' => trim((string) $this->request->getPost('username')),
            'email'    => trim((string) $this->request->getPost('email')),
            'password' => $this->request->getPost('password'),
            'active'   => 1,
        ]);

        $id = $this->users->insert($user);
        if (! $id) {
            return $this->volver()->withInput()->with('errors', $this->users->errors());
        }

        service('authorization')->addUserToGroup((int) $id, $this->request->getPost('grupo'));

        return $this->volver()->with('message', 'Usuario «' . $user->username . '» creado.');
    }

    public function grupo(int $id)
    {
        $grupo = (string) $this->request->getPost('grupo');
        if (! in_array($grupo, self::GRUPOS, true) || ! $this->users->find($id)) {
            return $this->volver()->with('error', 'Datos no válidos.');
        }
        if ($id === (int) user_id() && $grupo !== 'admin') {
            return $this->volver()->with('error', 'No puedes quitarte a ti mismo el grupo admin.');
        }

        $auth = service('authorization');
        foreach (self::GRUPOS as $g) {
            $auth->removeUserFromGroup($id, $g);
        }
        $auth->addUserToGroup($id, $grupo);

        return $this->volver()->with('message', 'Grupo cambiado.');
    }

    public function activo(int $id)
    {
        $user = $this->users->find($id);
        if (! $user) {
            return $this->volver()->with('error', 'Ese usuario no existe.');
        }
        if ($id === (int) user_id()) {
            return $this->volver()->with('error', 'No puedes desactivarte a ti mismo.');
        }

        $user->active = $user->active ? 0 : 1;
        $this->users->save($user);

        return $this->volver()->with('message', $user->active ? 'Usuario activado.' : 'Usuario desactivado: ya no puede entrar.');
    }

    public function password(int $id)
    {
        $user = $this->users->find($id);
        if (! $user) {
            return $this->volver()->with('error', 'Ese usuario no existe.');
        }

        $rules = [
            'password'     => 'required|min_length[10]',
            'pass_confirm' => 'required|matches[password]',
        ];
        if (! $this->validate($rules)) {
            return $this->volver()->with('errors', $this->validator->getErrors());
        }

        $user->password = $this->request->getPost('password');
        $this->users->save($user);

        return $this->volver()->with('message', 'Contraseña de «' . $user->username . '» cambiada.');
    }

    public function borrar(int $id)
    {
        $user = $this->users->find($id);
        if (! $user) {
            return $this->volver()->with('error', 'Ese usuario no existe.');
        }
        if ($id === (int) user_id()) {
            return $this->volver()->with('error', 'No puedes borrarte a ti mismo.');
        }

        $this->users->delete($id, true);

        return $this->volver()->with('message', 'Usuario «' . $user->username . '» borrado.');
    }

    private function volver()
    {
        return redirect()->to(site_url('cuenta/usuarios'));
    }
}
