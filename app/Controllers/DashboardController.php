<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Especialidad;
use App\Models\Horario;
use App\Models\Medico;
use App\Models\Paciente;

class DashboardController extends Controller
{
    public function index(): void
    {
        $user = Session::user();

        if ($user === null) {
            $this->redirect('auth/login');
        }

        $role = $user['rol'];
        $data = [
            'title' => 'Panel de control',
            'user' => $user,
        ];

        if ($role === 'Administrador') {
            $data['stats'] = [
                'pacientes' => (new Paciente())->countActive(),
                'medicos' => (new Medico())->countActive(),
                'especialidades' => (new Especialidad())->countActive(),
                'horarios' => (new Horario())->countActive(),
            ];
        } elseif ($role === 'Recepcionista') {
            $data['stats'] = [
                'pacientes' => (new Paciente())->countActive(),
                'medicos' => (new Medico())->countActive(),
                'horarios' => (new Horario())->countActive(),
            ];
        } elseif ($role === 'Medico') {
            $medico = (new Medico())->findByUserId((int) $user['id']);

            if ($medico !== null) {
                $data['medico'] = $medico;
                $data['stats'] = [
                    'horarios' => (new Horario())->countActiveByDoctor((int) $medico['id']),
                ];
            }
        } elseif ($role === 'Paciente') {
            $paciente = (new Paciente())->findByUserId((int) $user['id']);

            if ($paciente !== null) {
                $data['paciente'] = $paciente;
            }
        }

        $view = match ($role) {
            'Administrador' => 'dashboard/admin',
            'Recepcionista' => 'dashboard/recepcionista',
            'Medico' => 'dashboard/medico',
            'Paciente' => 'dashboard/paciente',
            default => 'dashboard/paciente',
        };

        $this->view($view, $data);
    }
}
