<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\CitaModel;
use App\Models\Medico;
use App\Models\Paciente;
use DateTimeImmutable;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * Controlador de citas médicas.
 *
 * Rutas sugeridas (se registran en index.php):
 *   GET  citas                          -> index
 *   GET  citas/crear                    -> crear
 *   POST citas/guardar                  -> guardar
 *   POST citas/cambiar-estado/{id}      -> cambiarEstado
 *   GET  citas/disponibilidad           -> disponibilidad (JSON para AJAX)
 *
 * RN13: cada acción vuelve a validar el rol en el servidor además del
 * middleware del router, y además restringe el alcance de los datos:
 *   - Administrador / Recepcionista: todas las citas y gestión completa.
 *   - Médico: solo su propia agenda y solo puede marcar citas como Atendida.
 *   - Paciente: solo sus propias citas, en modo lectura.
 */
class CitaController extends Controller
{
    private const ROLES_LECTURA = ['Administrador', 'Recepcionista', 'Medico', 'Paciente'];
    private const ROLES_GESTION = ['Administrador', 'Recepcionista'];
    private const ROLES_ESTADO = ['Administrador', 'Recepcionista', 'Medico'];

    private CitaModel $model;
    private Medico $medicoModel;
    private Paciente $pacienteModel;

    public function __construct()
    {
        $this->model = new CitaModel();
        $this->medicoModel = new Medico();
        $this->pacienteModel = new Paciente();
    }

    /**
     * Listado de citas con filtros y alcance según el rol (RN13).
     */
    public function index(): void
    {
        $user = $this->authorize(self::ROLES_LECTURA);
        $role = (string) ($user['rol'] ?? '');

        $search = trim((string) ($_GET['search'] ?? ''));
        $estado = (string) ($_GET['estado'] ?? 'Todos');

        if ($estado !== 'Todos' && !in_array($estado, CitaModel::ESTADOS, true)) {
            $estado = 'Todos';
        }

        $medicoId = isset($_GET['medico_id']) && $_GET['medico_id'] !== ''
            ? (int) $_GET['medico_id']
            : null;
        $pacienteId = null;
        $medicoActual = null;
        $pacienteActual = null;

        // Un médico solo puede ver su propia agenda.
        if ($role === 'Medico') {
            $medicoActual = $this->medicoModel->findByUserId((int) $user['id']);

            if ($medicoActual === null) {
                Session::flash('error', 'Tu usuario no está vinculado a un médico. Contacta al administrador.');
                $this->redirect('dashboard');
            }

            $medicoId = (int) $medicoActual['id'];
        }

        // Un paciente solo puede ver sus propias citas.
        if ($role === 'Paciente') {
            $pacienteActual = $this->pacienteModel->findByUserId((int) $user['id']);

            if ($pacienteActual === null) {
                Session::flash('error', 'Tu usuario no está vinculado a un paciente. Contacta al administrador.');
                $this->redirect('dashboard');
            }

            $pacienteId = (int) $pacienteActual['id'];
        }

        $fecha = trim((string) ($_GET['fecha'] ?? ''));
        $fechaDesde = trim((string) ($_GET['fecha_desde'] ?? ''));
        $fechaHasta = trim((string) ($_GET['fecha_hasta'] ?? ''));

        // El médico entra directo a la agenda del día solo en la primera visita
        // (sin parámetros de filtro). Si limpia el filtro, se muestran todas.
        $sinFiltros = !isset($_GET['search'])
            && !isset($_GET['medico_id'])
            && !isset($_GET['fecha'])
            && !isset($_GET['fecha_desde'])
            && !isset($_GET['fecha_hasta'])
            && !isset($_GET['estado']);

        if ($role === 'Medico' && $sinFiltros) {
            $fecha = date('Y-m-d');
        }

        if (!$this->esFechaValida($fecha)) {
            $fecha = '';
        }

        if (!$this->esFechaValida($fechaDesde)) {
            $fechaDesde = '';
        }

        if (!$this->esFechaValida($fechaHasta)) {
            $fechaHasta = '';
        }

        $filters = [
            'search' => $search,
            'estado' => $estado === 'Todos' ? null : $estado,
            'medico_id' => $medicoId,
            'paciente_id' => $pacienteId,
        ];

        if ($fecha !== '') {
            $filters['fecha'] = $fecha;
        }

        if ($fechaDesde !== '') {
            $filters['fecha_desde'] = $fechaDesde;
        }

        if ($fechaHasta !== '') {
            $filters['fecha_hasta'] = $fechaHasta;
        }

        $citas = $this->model->all($filters);

        $this->view('citas/index', [
            'title' => 'Citas',
            'citas' => $citas,
            'search' => $search,
            'estado' => $estado,
            'medicoId' => $medicoId,
            'pacienteId' => $pacienteId,
            'fecha' => $fecha,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'medicos' => in_array($role, self::ROLES_GESTION, true)
                ? $this->medicoModel->allForSelect()
                : [],
            'estados' => CitaModel::ESTADOS,
            'puedeGestionar' => in_array($role, self::ROLES_GESTION, true),
            'puedeCambiarEstado' => in_array($role, self::ROLES_ESTADO, true),
            'puedeMarcarAtendida' => $role === 'Medico',
            'medicoActual' => $medicoActual,
            'pacienteActual' => $pacienteActual,
        ]);
    }

    /**
     * Formulario de reserva. Genera los bloques de 30 minutos disponibles
     * comparando los horarios activos del médico con las citas activas ya
     * registradas para la fecha seleccionada (RN08).
     */
    public function crear(): void
    {
        $this->authorize(self::ROLES_GESTION);

        $medicoId = isset($_GET['medico_id']) && $_GET['medico_id'] !== ''
            ? (int) $_GET['medico_id']
            : 0;

        $fecha = trim((string) ($_GET['fecha'] ?? ''));

        if (!$this->esFechaValida($fecha)) {
            $fecha = date('Y-m-d');
        }

        $this->view('citas/create', [
            'title' => 'Nueva cita',
            'medicos' => $this->medicoModel->activeForSelect(),
            'pacientes' => $this->pacienteModel->all('', 'Activo'),
            'medicoId' => $medicoId,
            'fecha' => $fecha,
            'bloques' => $this->bloquesDisponibles($medicoId, $fecha),
            'estadosIniciales' => CitaModel::ESTADOS_INICIALES,
        ]);
    }

    /**
     * Registra una nueva cita.
     *
     * La transacción (beginTransaction / commit / rollBack) vive en
     * CitaModel::agendar() para evitar transacciones anidadas. Aquí se capturan
     * los fallos y se traducen a mensajes claros para el usuario.
     *
     * La condición de carrera del índice parcial único
     * `uq_citas_medico_fecha_horario_activa` se maneja explícitamente:
     *  - PDOException con SQLSTATE 23505 (violación única), o
     *  - RuntimeException con código CitaModel::ERR_SLOT_OCUPADO (traducida por el modelo).
     * En ambos casos se informa: "El horario seleccionado ya no está disponible."
     */
    public function guardar(): void
    {
        $user = $this->authorize(self::ROLES_GESTION);
        $this->requireCsrf();

        $data = [
            'paciente_id' => trim((string) ($_POST['paciente_id'] ?? '')),
            'medico_id' => trim((string) ($_POST['medico_id'] ?? '')),
            'horario_id' => trim((string) ($_POST['horario_id'] ?? '')),
            'fecha' => trim((string) ($_POST['fecha'] ?? '')),
            'estado' => trim((string) ($_POST['estado'] ?? CitaModel::ESTADO_PENDIENTE)),
            'motivo' => trim((string) ($_POST['motivo'] ?? '')),
            'notas' => trim((string) ($_POST['notas'] ?? '')),
        ];

        $validator = new Validator($data);
        $validator
            ->required('paciente_id', 'Paciente')
            ->numeric('paciente_id', 'Paciente')
            ->required('medico_id', 'Médico')
            ->numeric('medico_id', 'Médico')
            ->required('horario_id', 'Bloque horario')
            ->numeric('horario_id', 'Bloque horario')
            ->required('fecha', 'Fecha')
            ->date('fecha', 'Fecha')
            ->required('estado', 'Estado')
            ->in('estado', CitaModel::ESTADOS_INICIALES, 'Estado')
            ->max('motivo', 500, 'Motivo')
            ->max('notas', 1000, 'Notas');

        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), $data);
        }

        $paciente = $this->pacienteModel->find((int) $data['paciente_id']);

        if ($paciente === null) {
            $validator->add('paciente_id', 'El paciente seleccionado no existe.');
        } elseif ((string) $paciente['estado'] !== 'Activo') {
            $validator->add('paciente_id', 'El paciente está inactivo y no puede recibir nuevas citas.');
        }

        if (!$this->medicoModel->exists((int) $data['medico_id'])) {
            $validator->add('medico_id', 'El médico seleccionado no existe.');
        } elseif (!$this->medicoModel->isActive((int) $data['medico_id'])) {
            $validator->add('medico_id', 'El médico está inactivo y no puede recibir nuevas citas.');
        }

        if ($validator->fails()) {
            $this->flashValidation($validator->errors(), $data);
        }

        // RN08: el bloque debe existir, pertenecer al médico, ser de 30 minutos
        // y seguir libre en la fecha indicada.
        $bloques = $this->bloquesDisponibles((int) $data['medico_id'], $data['fecha']);
        $bloqueSeleccionado = $this->buscarBloque($bloques, (int) $data['horario_id']);

        if ($bloqueSeleccionado === null) {
            $validator->add('horario_id', 'El horario seleccionado ya no está disponible.');
            $this->flashValidation($validator->errors(), $data);
        }

        try {
            $this->model->agendar([
                'paciente_id' => (int) $data['paciente_id'],
                'medico_id' => (int) $data['medico_id'],
                'horario_id' => (int) $data['horario_id'],
                'fecha' => $data['fecha'],
                'estado' => $data['estado'],
                'motivo' => $data['motivo'],
                'notas' => $data['notas'],
            ], (int) $user['id']);

            Session::flash('success', 'Cita registrada correctamente.');
            $this->redirect('citas');
        } catch (PDOException $e) {
            // RN10: violación del índice parcial único por concurrencia.
            $this->logError($e);

            if ($this->isUniqueViolation($e)) {
                $this->flashSlotUnavailable($data);
            }

            Session::flash('error', 'No se pudo registrar la cita. Intente nuevamente.');
            $this->redirectFormulario($data);
        } catch (RuntimeException $e) {
            $this->logError($e);

            if ($e->getCode() === CitaModel::ERR_SLOT_OCUPADO) {
                $this->flashSlotUnavailable($data);
            }

            Session::flash('error', $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo registrar la cita.');
            $this->redirectFormulario($data);
        } catch (Throwable $e) {
            $this->logError($e);
            Session::flash('error', 'Ocurrió un error inesperado al registrar la cita.');
            $this->redirectFormulario($data);
        }
    }

    /**
     * Cambia el estado de una cita (confirmar, cancelar, marcar atendida,
     * registrar inasistencia). El estado llega por POST.
     *
     * RN12: al cancelar, CitaModel libera el bloque automáticamente mediante
     * el índice parcial único.
     */
    public function cambiarEstado(string $id): void
    {
        $user = $this->authorize(self::ROLES_ESTADO);
        $this->requireCsrf();

        $citaId = (int) $id;
        $estado = trim((string) ($_POST['estado'] ?? ''));

        if ($citaId <= 0 || !in_array($estado, CitaModel::ESTADOS, true)) {
            Session::flash('error', 'El estado solicitado no es válido.');
            $this->redirect('citas');
        }

        $cita = $this->model->find($citaId);

        if ($cita === null) {
            Session::flash('error', 'Cita no encontrada.');
            $this->redirect('citas');
        }

        // RN13: el médico solo opera sobre su propia agenda y solo puede
        // marcar la cita como Atendida.
        if ((string) $user['rol'] === 'Medico') {
            $medico = $this->medicoModel->findByUserId((int) $user['id']);

            if ($medico === null || (int) $medico['id'] !== (int) $cita['medico_id']) {
                Session::flash('error', 'No tienes permisos para modificar esta cita.');
                $this->redirect('citas');
            }

            if ($estado !== CitaModel::ESTADO_ATENDIDA) {
                Session::flash('error', 'Como médico solo puedes marcar la cita como Atendida.');
                $this->redirect('citas');
            }
        }

        try {
            $actualizada = $this->model->cambiarEstado($citaId, $estado);

            if (!$actualizada) {
                Session::flash('error', 'Cita no encontrada.');
                $this->redirect('citas');
            }

            Session::flash('success', 'Estado de la cita actualizado a "' . $estado . '".');
        } catch (PDOException $e) {
            $this->logError($e);
            Session::flash('error', 'No se pudo actualizar el estado de la cita. Intente nuevamente.');
        } catch (RuntimeException $e) {
            $this->logError($e);
            Session::flash('error', $e->getMessage() !== '' ? $e->getMessage() : 'No se pudo actualizar la cita.');
        } catch (Throwable $e) {
            $this->logError($e);
            Session::flash('error', 'Ocurrió un error inesperado al actualizar la cita.');
        }

        $this->redirect('citas');
    }

    /**
     * Endpoint JSON para refrescar los bloques disponibles desde el formulario
     * (AJAX/Fetch). Solo Administrador y Recepcionista.
     *
     * Parámetros: medico_id, fecha, solo_libres (1/0, por defecto 1).
     */
    public function disponibilidad(): void
    {
        $user = Session::user();

        if ($user === null) {
            $this->json(['ok' => false, 'message' => 'La sesión ha expirado.'], 401);
        }

        if (!in_array((string) ($user['rol'] ?? ''), self::ROLES_GESTION, true)) {
            $this->json(['ok' => false, 'message' => 'No tienes permisos para consultar la disponibilidad.'], 403);
        }

        $medicoId = isset($_GET['medico_id']) ? (int) $_GET['medico_id'] : 0;
        $fecha = trim((string) ($_GET['fecha'] ?? ''));
        $soloLibres = filter_var($_GET['solo_libres'] ?? '1', FILTER_VALIDATE_BOOLEAN);

        if ($medicoId <= 0 || !$this->esFechaValida($fecha)) {
            $this->json(['ok' => false, 'message' => 'Debe indicar un médico y una fecha válida.'], 422);
        }

        try {
            $bloques = $this->model->disponibilidad($medicoId, $fecha, $soloLibres, false);
            $bloques = array_values(array_filter(
                $bloques,
                fn (array $bloque): bool => $this->esBloqueDeTreintaMinutos($bloque)
            ));

            $this->json([
                'ok' => true,
                'medico_id' => $medicoId,
                'fecha' => $fecha,
                'bloques' => $bloques,
            ]);
        } catch (RuntimeException $e) {
            $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Genera los bloques de 30 minutos disponibles de un médico para una fecha.
     *
     * Compara los horarios activos del día contra las citas activas ya
     * registradas. Las citas 'Cancelada' no bloquean el horario (RN12).
     */
    private function bloquesDisponibles(int $medicoId, string $fecha): array
    {
        if ($medicoId <= 0 || !$this->esFechaValida($fecha)) {
            return [];
        }

        try {
            $bloques = $this->model->disponibilidad($medicoId, $fecha, true, false);
        } catch (RuntimeException $e) {
            return [];
        }

        return array_values(array_filter(
            $bloques,
            fn (array $bloque): bool => $this->esBloqueDeTreintaMinutos($bloque)
        ));
    }

    private function buscarBloque(array $bloques, int $horarioId): ?array
    {
        foreach ($bloques as $bloque) {
            if ((int) $bloque['horario_id'] === $horarioId) {
                return $bloque;
            }
        }

        return null;
    }

    /**
     * RN08: solo se permiten bloques de exactamente 30 minutos.
     */
    private function esBloqueDeTreintaMinutos(array $bloque): bool
    {
        $inicio = strtotime((string) ($bloque['hora_inicio'] ?? ''));
        $fin = strtotime((string) ($bloque['hora_fin'] ?? ''));

        return $inicio !== false && $fin !== false && ($fin - $inicio) === 1800;
    }

    /**
     * RN13: valida sesión y rol. Redirige si no está autorizado.
     */
    private function authorize(array $roles): array
    {
        $user = Session::user();

        if ($user === null) {
            Session::flash('error', 'Debes iniciar sesión para acceder a esta sección.');
            $this->redirect('auth/login');
        }

        if (!in_array((string) ($user['rol'] ?? ''), $roles, true)) {
            Session::flash('error', 'No tienes permisos para realizar esta acción.');
            $this->redirect('dashboard');
        }

        return $user;
    }

    private function esFechaValida(string $fecha): bool
    {
        if ($fecha === '') {
            return false;
        }

        $fechaObj = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

        return $fechaObj !== false && $fechaObj->format('Y-m-d') === $fecha;
    }

    /**
     * Detecta la violación del índice único parcial de PostgreSQL (SQLSTATE 23505).
     */
    private function isUniqueViolation(PDOException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());
        $driverCode = (string) ($e->errorInfo[1] ?? '');

        return $sqlState === '23505'
            || $driverCode === '7'
            || str_contains($e->getMessage(), 'uq_citas_medico_fecha_horario_activa');
    }

    private function flashValidation(array $errors, array $data): void
    {
        Session::flash('errors', $errors);
        Session::flash('old', $data);
        $this->redirectFormulario($data);
    }

    private function flashSlotUnavailable(array $data): void
    {
        $message = 'El horario seleccionado ya no está disponible.';

        Session::flash('error', $message);
        Session::flash('errors', ['horario_id' => $message]);
        Session::flash('old', $data);
        $this->redirectFormulario($data);
    }

    private function redirectFormulario(array $data): void
    {
        $params = [];

        if (!empty($data['medico_id'])) {
            $params['medico_id'] = $data['medico_id'];
        }

        if (!empty($data['fecha'])) {
            $params['fecha'] = $data['fecha'];
        }

        $this->redirect('citas/crear', $params);
    }

    private function logError(Throwable $e): void
    {
        error_log(
            '[Clínica Vida Salud][Citas] ' . $e->getMessage()
            . ' en ' . $e->getFile() . ':' . $e->getLine()
        );
    }
}
