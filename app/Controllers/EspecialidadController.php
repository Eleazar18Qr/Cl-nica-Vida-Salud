<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Especialidad;

class EspecialidadController extends Controller
{
    private Especialidad $model;

    public function __construct()
    {
        $this->model = new Especialidad();
    }

    public function index(): void
    {
        $search = trim($_GET['search'] ?? '');
        $estado = $_GET['estado'] ?? 'Activo';

        if (!in_array($estado, ['Activo', 'Inactivo', 'Todos'], true)) {
            $estado = 'Activo';
        }

        $estadoFilter = $estado === 'Todos' ? null : $estado;
        $especialidades = $this->model->all($search, $estadoFilter);

        $this->view('especialidades/index', [
            'title' => 'Especialidades',
            'especialidades' => $especialidades,
            'search' => $search,
            'estado' => $estado,
        ]);
    }

    public function create(): void
    {
        $this->view('especialidades/create', ['title' => 'Nueva especialidad']);
    }

    public function store(): void
    {
        $this->requireCsrf();

        $data = $this->validatedData();

        if ($data === null) {
            $this->redirect('especialidades/create');
        }

        $this->model->create($data);

        Session::flash('success', 'Especialidad registrada correctamente.');
        $this->redirect('especialidades');
    }

    public function edit(string $id): void
    {
        $especialidad = $this->model->find((int) $id);

        if ($especialidad === null) {
            Session::flash('error', 'Especialidad no encontrada.');
            $this->redirect('especialidades');
        }

        $this->view('especialidades/edit', [
            'title' => 'Editar especialidad',
            'especialidad' => $especialidad,
        ]);
    }

    public function update(string $id): void
    {
        $this->requireCsrf();

        $especialidad = $this->model->find((int) $id);

        if ($especialidad === null) {
            Session::flash('error', 'Especialidad no encontrada.');
            $this->redirect('especialidades');
        }

        $data = $this->validatedData((int) $id);

        if ($data === null) {
            $this->redirect('especialidades/edit/' . $id);
        }

        $this->model->update((int) $id, $data);

        Session::flash('success', 'Especialidad actualizada correctamente.');
        $this->redirect('especialidades');
    }

    public function delete(string $id): void
    {
        $this->requireCsrf();

        $especialidad = $this->model->find((int) $id);

        if ($especialidad === null) {
            Session::flash('error', 'Especialidad no encontrada.');
            $this->redirect('especialidades');
        }

        if ($this->model->countActiveDoctors((int) $id) > 0) {
            Session::flash('error', 'No se puede desactivar la especialidad porque tiene médicos activos asociados.');
            $this->redirect('especialidades');
        }

        $this->model->softDelete((int) $id);

        Session::flash('success', 'Especialidad desactivada correctamente.');
        $this->redirect('especialidades');
    }

    public function restore(string $id): void
    {
        $this->requireCsrf();

        $this->model->restore((int) $id);

        Session::flash('success', 'Especialidad reactivada correctamente.');
        $this->redirect('especialidades');
    }

    private function validatedData(?int $excludeId = null): ?array
    {
        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'estado' => trim($_POST['estado'] ?? 'Activo'),
        ];

        $validator = new Validator($data);
        $validator
            ->required('nombre', 'Nombre')
            ->max('nombre', 100, 'Nombre')
            ->in('estado', ['Activo', 'Inactivo'], 'Estado');

        if ($this->model->existsNombre($data['nombre'], $excludeId)) {
            $validator->add('nombre', 'Ya existe una especialidad con este nombre.');
        }

        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            Session::flash('old', $data);
            return null;
        }

        return $data;
    }
}
