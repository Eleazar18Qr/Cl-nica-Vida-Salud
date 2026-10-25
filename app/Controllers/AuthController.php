<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Session::has('user')) {
            $this->redirect('dashboard');
        }

        $this->view('auth/login', ['title' => 'Iniciar sesión'], 'auth');
    }

    public function login(): void
    {
        $this->requireCsrf();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $validator = new Validator(['email' => $email, 'password' => $password]);
        $validator
            ->required('email', 'El correo electrónico')
            ->email('email', 'El correo electrónico')
            ->required('password', 'La contraseña');

        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            Session::flash('old', ['email' => $email]);
            $this->redirect('auth/login');
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (
            $user === null
            || $user['estado'] !== 'Activo'
            || $user['rol_estado'] !== 'Activo'
            || !password_verify($password, $user['password_hash'])
        ) {
            Session::flash('error', 'Credenciales incorrectas. Verifica tu correo y contraseña.');
            Session::flash('old', ['email' => $email]);
            $this->redirect('auth/login');
        }

        session_regenerate_id(true);

        Session::set('user', [
            'id' => (int) $user['id'],
            'rol_id' => (int) $user['rol_id'],
            'nombres' => $user['nombres'],
            'apellidos' => $user['apellidos'],
            'email' => $user['email'],
            'rol' => $user['rol'],
        ]);

        $userModel->updateLastAccess((int) $user['id']);

        Session::flash('success', 'Bienvenido(a), ' . $user['nombres'] . '.');
        $this->redirect('dashboard');
    }

    public function logout(): void
    {
        $this->requireCsrf();
        Session::destroy();
        $this->redirect('auth/login', ['logged_out' => 1]);
    }
}
