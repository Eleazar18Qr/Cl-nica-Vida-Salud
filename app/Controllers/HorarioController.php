<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Horario;
use App\Models\Medico;

class HorarioController extends Controller
{
    private Horario $model;
    private Medico $medicoModel;

    public function __construct()
    {
        $this->model = new Horario();
        $this->medicoModel = new Medico();
    }

    public function index(): void
    {
        $search = trim($_GET['search'] ?? '');
        $estado = $_GET['estado'] ?? 'Activo';
        $medicoId = isset($_GET['medico_id']) && $_GET['medico_id'] !== ''
            ? (int) $_GET['medico_id']
            : null;
        $diaSemana = isset($_GET['dia_semana']) && $_GET['dia_semana'] !== ''
            ? (int) $_GET['dia_semana']
            : null;

        if (!in_array($estado, ['Activo', 'Inactivo', 'Todos'], true)) {
            $estado = 'Activo';
        }

        if ($diaSemana !== null && ($diaSemana < 1 || $diaSemana > 7)) {
            $diaSemana = null;
        }

        $estadoFilter = $estado === 'Todos' ? null : $estado;
        $horarios = $this->model->all($search, $estadoFilter, $medicoId, $diaSemana);

        $this->view('horarios/index', [
            'title' => 'Horarios',
            'horarios' => $horarios,
            'search' => $search,
            'estado' => $estado,
            'medicoId' => $medicoId,
            'diaSemana' => $diaSemana,
            'medicos' => $this->medicoModel->allForSelect(),
        ]);
    }

    public function create(): void
    {
        $this->view('horarios/create', [
            'title' => 'Nuevo horario',
            'medicos' => $this->medicoModel->activeForSelect(),
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();

        $data = $this->validatedData();

        if ($data === null) {
            $this->redirect('horarios/create');
        }

        $this->model->create($data);

        Session::flash('success', 'Horario registrado correctamente.');
        $this->redirect('horarios');
    }

    public function edit(string $id): void
    {
        $horario = $this->model->find((int) $id);

        if ($horario === null) {
            Session::flash('error', 'Horario no encontrado.');
            $this->redirect('horarios');
        }

        $this->view('horarios/edit', [
            'title' => 'Editar horario',
            'horario' => $horario,
            'medicos' => $this->medicoModel->allForSelect(),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireCsrf();

        $horario = $this->model->find((int) $id);

        if ($horario === null) {
            Session::flash('error', 'Horario no encontrado.');
            $this->redirect('horarios');
        }

        $data = $this->validatedData((int) $id);

        if ($data === null) {
            $this->redirect('horarios/edit/' . $id);
        }

        $this->model->update((int) $id, $data);

        Session::flash('success', 'Horario actualizado correctamente.');
        $this->redirect('horarios');
    }

    public function delete(string $id): void
    {
        $this->requireCsrf();

        $horario = $this->model->find((int) $id);

        if ($horario === null) {
            Session::flash('error', 'Horario no encontrado.');
            $this->redirect('horarios');
        }

        if ($this->model->countFutureAppointments((int) $id) > 0) {
            Session::flash('error', 'No se puede desactivar el horario porque tiene citas futuras pendientes o confirmadas.');
            $this->redirect('horarios');
        }

        $this->model->softDelete((int) $id);

        Session::flash('success', 'Horario desactivado correctamente.');
        $this->redirect('horarios');
    }

    public function restore(string $id): void
    {
        $this->requireCsrf();

        $this->model->restore((int) $id);

        Session::flash('success', 'Horario reactivado correctamente.');
        $this->redirect('horarios');
    }

    private function validatedData(?int $excludeId = null): ?array
    {
        $data = [
            'medico_id' => trim($_POST['medico_id'] ?? ''),
            'dia_semana' => trim($_POST['dia_semana'] ?? ''),
            'hora_inicio' => trim($_POST['hora_inicio'] ?? ''),
            'hora_fin' => trim($_POST['hora_fin'] ?? ''),
            'estado' => trim($_POST['estado'] ?? 'Activo'),
        ];

        $validator = new Validator($data);
        $validator
            ->required('medico_id', 'Médico')
            ->numeric('medico_id', 'Médico')
            ->required('dia_semana', 'Día de la semana')
            ->in('dia_semana', [1, 2, 3, 4, 5, 6, 7], 'Día de la semana')
            ->required('hora_inicio', 'Hora de inicio')
            ->time('hora_inicio', 'Hora de inicio')
            ->required('hora_fin', 'Hora de fin')
            ->time('hora_fin', 'Hora de fin')
            ->in('estado', ['Activo', 'Inactivo'], 'Estado');

        if (
            $data['hora_inicio'] !== ''
            && $data['hora_fin'] !== ''
            && $data['hora_fin'] <= $data['hora_inicio']
        ) {
            $validator->add('hora_fin', 'La hora de fin debe ser mayor que la hora de inicio.');
        }

        $medicoExists = $data['medico_id'] !== ''
            && $this->medicoModel->exists((int) $data['medico_id']);

        if ($data['medico_id'] !== '' && !$medicoExists) {
            $validator->add('medico_id', 'El médico seleccionado no existe.');
        }

        if (
            $data['estado'] === 'Activo'
            && $medicoExists
            && !$this->medicoModel->isActive((int) $data['medico_id'])
        ) {
            $validator->add('medico_id', 'El médico seleccionado no está activo.');
        }

        if (
            $data['estado'] === 'Activo'
            && $medicoExists
            && $data['dia_semana'] !== ''
            && $data['hora_inicio'] !== ''
            && $data['hora_fin'] !== ''
            && $data['hora_fin'] > $data['hora_inicio']
        ) {
            $overlap = $this->model->hasOverlap(
                (int) $data['medico_id'],
                (int) $data['dia_semana'],
                $data['hora_inicio'],
                $data['hora_fin'],
                $excludeId
            );

            if ($overlap) {
                $validator->add('hora_inicio', 'El horario se superpone con otro horario activo del mismo médico.');
            }
        }

        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            Session::flash('old', $data);
            return null;
        }

        return $data;
    }
}
