<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Especialidad;
use App\Models\Medico;

class MedicoController extends Controller
{
    private Medico $model;
    private Especialidad $especialidadModel;

    public function __construct()
    {
        $this->model = new Medico();
        $this->especialidadModel = new Especialidad();
    }

    public function index(): void
    {
        $search = trim($_GET['search'] ?? '');
        $estado = $_GET['estado'] ?? 'Activo';

        if (!in_array($estado, ['Activo', 'Inactivo', 'Todos'], true)) {
            $estado = 'Activo';
        }

        $estadoFilter = $estado === 'Todos' ? null : $estado;
        $medicos = $this->model->all($search, $estadoFilter);

        $this->view('medicos/index', [
            'title' => 'Médicos',
            'medicos' => $medicos,
            'search' => $search,
            'estado' => $estado,
        ]);
    }

    public function create(): void
    {
        $this->view('medicos/create', [
            'title' => 'Nuevo médico',
            'especialidades' => $this->especialidadModel->activeForSelect(),
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();

        $data = $this->validatedData();

        if ($data === null) {
            $this->redirect('medicos/create');
        }

        $this->model->create($data);

        Session::flash('success', 'Médico registrado correctamente.');
        $this->redirect('medicos');
    }

    public function show(string $id): void
    {
        $medico = $this->model->find((int) $id);

        if ($medico === null) {
            Session::flash('error', 'Médico no encontrado.');
            $this->redirect('medicos');
        }

        $this->view('medicos/show', [
            'title' => 'Detalle del médico',
            'medico' => $medico,
        ]);
    }

    public function edit(string $id): void
    {
        $medico = $this->model->find((int) $id);

        if ($medico === null) {
            Session::flash('error', 'Médico no encontrado.');
            $this->redirect('medicos');
        }

        $this->view('medicos/edit', [
            'title' => 'Editar médico',
            'medico' => $medico,
            'especialidades' => $this->especialidadModel->allForSelect(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireCsrf();

        $medico = $this->model->find((int) $id);

        if ($medico === null) {
            Session::flash('error', 'Médico no encontrado.');
            $this->redirect('medicos');
        }

        $data = $this->validatedData((int) $id, (int) $medico['usuario_id']);

        if ($data === null) {
            $this->redirect('medicos/edit/' . $id);
        }

        $this->model->update((int) $id, $data);

        Session::flash('success', 'Médico actualizado correctamente.');
        $this->redirect('medicos');
    }

    public function delete(string $id): void
    {
        $this->requireCsrf();

        $medico = $this->model->find((int) $id);

        if ($medico === null) {
            Session::flash('error', 'Médico no encontrado.');
            $this->redirect('medicos');
        }

        $this->model->softDelete((int) $id);

        Session::flash('success', 'Médico desactivado correctamente.');
        $this->redirect('medicos');
    }

    public function restore(string $id): void
    {
        $this->requireCsrf();

        $this->model->restore((int) $id);

        Session::flash('success', 'Médico reactivado correctamente.');
        $this->redirect('medicos');
    }

    private function validatedData(?int $excludeMedicoId = null, ?int $excludeUsuarioId = null): ?array
    {
        $data = [
            'nombres' => trim($_POST['nombres'] ?? ''),
            'apellidos' => trim($_POST['apellidos'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'telefono' => trim($_POST['telefono'] ?? ''),
            'documento_tipo' => trim($_POST['documento_tipo'] ?? ''),
            'documento_numero' => trim($_POST['documento_numero'] ?? ''),
            'especialidad_id' => trim($_POST['especialidad_id'] ?? ''),
            'numero_colegiatura' => trim($_POST['numero_colegiatura'] ?? ''),
            'biografia' => trim($_POST['biografia'] ?? ''),
            'tarifa_consulta' => trim($_POST['tarifa_consulta'] ?? ''),
            'estado' => trim($_POST['estado'] ?? 'Activo'),
        ];

        $validator = new Validator($data);
        $validator
            ->required('nombres', 'Nombres')
            ->max('nombres', 100, 'Nombres')
            ->required('apellidos', 'Apellidos')
            ->max('apellidos', 100, 'Apellidos')
            ->required('email', 'Correo electrónico')
            ->email('email', 'Correo electrónico')
            ->max('email', 150, 'Correo electrónico')
            ->required('documento_tipo', 'Tipo de documento')
            ->in('documento_tipo', ['DNI', 'CE', 'PASAPORTE'], 'Tipo de documento')
            ->required('documento_numero', 'Número de documento')
            ->max('documento_numero', 20, 'Número de documento')
            ->required('especialidad_id', 'Especialidad')
            ->numeric('especialidad_id', 'Especialidad')
            ->required('numero_colegiatura', 'Número de colegiatura')
            ->max('numero_colegiatura', 50, 'Número de colegiatura')
            ->required('tarifa_consulta', 'Tarifa de consulta')
            ->numeric('tarifa_consulta', 'Tarifa de consulta')
            ->minValue('tarifa_consulta', 0, 'Tarifa de consulta')
            ->max('telefono', 20, 'Teléfono')
            ->in('estado', ['Activo', 'Inactivo'], 'Estado');

        if (
            $data['especialidad_id'] !== ''
            && !$this->especialidadModel->exists((int) $data['especialidad_id'])
        ) {
            $validator->add('especialidad_id', 'La especialidad seleccionada no existe.');
        }

        if ($excludeMedicoId === null) {
            $validator
                ->required('password', 'Contraseña')
                ->min('password', 8, 'Contraseña');
        } elseif ($data['password'] !== '') {
            $validator->min('password', 8, 'Contraseña');
        }

        if ($this->model->existsEmail($data['email'], $excludeUsuarioId)) {
            $validator->add('email', 'Ya existe un usuario con este correo electrónico.');
        }

        if ($this->model->existsDocumento($data['documento_numero'], $excludeUsuarioId)) {
            $validator->add('documento_numero', 'Ya existe un usuario con este número de documento.');
        }

        if ($this->model->existsColegiatura($data['numero_colegiatura'], $excludeMedicoId)) {
            $validator->add('numero_colegiatura', 'Ya existe un médico con este número de colegiatura.');
        }

        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            Session::flash('old', $data);
            return null;
        }

        return $data;
    }
}
