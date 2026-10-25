<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Paciente;

class PacienteController extends Controller
{
    private Paciente $model;

    public function __construct()
    {
        $this->model = new Paciente();
    }

    public function index(): void
    {
        $search = trim($_GET['search'] ?? '');
        $estado = $_GET['estado'] ?? 'Activo';

        if (!in_array($estado, ['Activo', 'Inactivo', 'Todos'], true)) {
            $estado = 'Activo';
        }

        $estadoFilter = $estado === 'Todos' ? null : $estado;
        $pacientes = $this->model->all($search, $estadoFilter);

        $this->view('pacientes/index', [
            'title' => 'Pacientes',
            'pacientes' => $pacientes,
            'search' => $search,
            'estado' => $estado,
        ]);
    }

    public function create(): void
    {
        $this->view('pacientes/create', ['title' => 'Nuevo paciente']);
    }

    public function store(): void
    {
        $this->requireCsrf();

        $data = $this->validatedData();

        if ($data === null) {
            $this->redirect('pacientes/create');
        }

        $this->model->create($data);

        Session::flash('success', 'Paciente registrado correctamente.');
        $this->redirect('pacientes');
    }

    public function show(string $id): void
    {
        $paciente = $this->model->find((int) $id);

        if ($paciente === null) {
            Session::flash('error', 'Paciente no encontrado.');
            $this->redirect('pacientes');
        }

        $this->view('pacientes/show', [
            'title' => 'Detalle del paciente',
            'paciente' => $paciente,
        ]);
    }

    public function edit(string $id): void
    {
        $paciente = $this->model->find((int) $id);

        if ($paciente === null) {
            Session::flash('error', 'Paciente no encontrado.');
            $this->redirect('pacientes');
        }

        $this->view('pacientes/edit', [
            'title' => 'Editar paciente',
            'paciente' => $paciente,
        ]);
    }

    public function update(string $id): void
    {
        $this->requireCsrf();

        $paciente = $this->model->find((int) $id);

        if ($paciente === null) {
            Session::flash('error', 'Paciente no encontrado.');
            $this->redirect('pacientes');
        }

        $data = $this->validatedData((int) $id);

        if ($data === null) {
            $this->redirect('pacientes/edit/' . $id);
        }

        $this->model->update((int) $id, $data);

        Session::flash('success', 'Paciente actualizado correctamente.');
        $this->redirect('pacientes');
    }

    public function delete(string $id): void
    {
        $this->requireCsrf();

        $paciente = $this->model->find((int) $id);

        if ($paciente === null) {
            Session::flash('error', 'Paciente no encontrado.');
            $this->redirect('pacientes');
        }

        $this->model->softDelete((int) $id);

        Session::flash('success', 'Paciente desactivado correctamente.');
        $this->redirect('pacientes');
    }

    public function restore(string $id): void
    {
        $this->requireCsrf();

        $this->model->restore((int) $id);

        Session::flash('success', 'Paciente reactivado correctamente.');
        $this->redirect('pacientes');
    }

    private function validatedData(?int $excludeId = null): ?array
    {
        $data = [
            'documento_tipo' => trim($_POST['documento_tipo'] ?? ''),
            'documento_numero' => trim($_POST['documento_numero'] ?? ''),
            'nombres' => trim($_POST['nombres'] ?? ''),
            'apellidos' => trim($_POST['apellidos'] ?? ''),
            'fecha_nacimiento' => trim($_POST['fecha_nacimiento'] ?? ''),
            'genero' => trim($_POST['genero'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'grupo_sanguineo' => trim($_POST['grupo_sanguineo'] ?? ''),
            'alergias' => trim($_POST['alergias'] ?? ''),
            'contacto_emergencia_nombre' => trim($_POST['contacto_emergencia_nombre'] ?? ''),
            'contacto_emergencia_telefono' => trim($_POST['contacto_emergencia_telefono'] ?? ''),
            'estado' => trim($_POST['estado'] ?? 'Activo'),
        ];

        $validator = new Validator($data);
        $validator
            ->required('documento_tipo', 'Tipo de documento')
            ->in('documento_tipo', ['DNI', 'CE', 'PASAPORTE'], 'Tipo de documento')
            ->required('documento_numero', 'Número de documento')
            ->max('documento_numero', 20, 'Número de documento')
            ->required('nombres', 'Nombres')
            ->max('nombres', 100, 'Nombres')
            ->required('apellidos', 'Apellidos')
            ->max('apellidos', 100, 'Apellidos')
            ->required('fecha_nacimiento', 'Fecha de nacimiento')
            ->date('fecha_nacimiento', 'Fecha de nacimiento')
            ->dateNotFuture('fecha_nacimiento', 'Fecha de nacimiento')
            ->required('genero', 'Género')
            ->in('genero', ['M', 'F', 'O'], 'Género')
            ->email('email', 'Correo electrónico')
            ->max('email', 150, 'Correo electrónico')
            ->max('telefono', 20, 'Teléfono')
            ->max('direccion', 255, 'Dirección')
            ->max('grupo_sanguineo', 5, 'Grupo sanguíneo')
            ->max('contacto_emergencia_nombre', 100, 'Contacto de emergencia')
            ->max('contacto_emergencia_telefono', 20, 'Teléfono de emergencia')
            ->in('estado', ['Activo', 'Inactivo'], 'Estado');

        if ($this->model->existsDocumento($data['documento_numero'], $excludeId)) {
            $validator->add('documento_numero', 'Ya existe un paciente con este número de documento.');
        }

        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            Session::flash('old', $data);
            return null;
        }

        return $data;
    }
}
