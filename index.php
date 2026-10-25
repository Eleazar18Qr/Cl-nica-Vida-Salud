<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\EspecialidadController;
use App\Controllers\HorarioController;
use App\Controllers\MedicoController;
use App\Controllers\PacienteController;
use App\Core\Router;

require __DIR__ . '/bootstrap.php';

$router = new Router();

// Autenticación
$router->get('', [AuthController::class, 'showLogin']);
$router->get('auth/login', [AuthController::class, 'showLogin']);
$router->post('auth/login', [AuthController::class, 'login']);
$router->post('auth/logout', [AuthController::class, 'logout']);

// Panel de control
$router->get('dashboard', [DashboardController::class, 'index'], ['Administrador', 'Recepcionista', 'Medico', 'Paciente']);

// Pacientes
$router->get('pacientes', [PacienteController::class, 'index'], ['Administrador', 'Recepcionista', 'Medico']);
$router->get('pacientes/create', [PacienteController::class, 'create'], ['Administrador', 'Recepcionista']);
$router->post('pacientes/store', [PacienteController::class, 'store'], ['Administrador', 'Recepcionista']);
$router->get('pacientes/show/{id}', [PacienteController::class, 'show'], ['Administrador', 'Recepcionista', 'Medico']);
$router->get('pacientes/edit/{id}', [PacienteController::class, 'edit'], ['Administrador', 'Recepcionista']);
$router->post('pacientes/update/{id}', [PacienteController::class, 'update'], ['Administrador', 'Recepcionista']);
$router->post('pacientes/delete/{id}', [PacienteController::class, 'delete'], ['Administrador', 'Recepcionista']);
$router->post('pacientes/restore/{id}', [PacienteController::class, 'restore'], ['Administrador', 'Recepcionista']);

// Médicos
$router->get('medicos', [MedicoController::class, 'index'], ['Administrador', 'Recepcionista']);
$router->get('medicos/create', [MedicoController::class, 'create'], ['Administrador']);
$router->post('medicos/store', [MedicoController::class, 'store'], ['Administrador']);
$router->get('medicos/show/{id}', [MedicoController::class, 'show'], ['Administrador', 'Recepcionista']);
$router->get('medicos/edit/{id}', [MedicoController::class, 'edit'], ['Administrador']);
$router->post('medicos/update/{id}', [MedicoController::class, 'update'], ['Administrador']);
$router->post('medicos/delete/{id}', [MedicoController::class, 'delete'], ['Administrador']);
$router->post('medicos/restore/{id}', [MedicoController::class, 'restore'], ['Administrador']);

// Especialidades
$router->get('especialidades', [EspecialidadController::class, 'index'], ['Administrador']);
$router->get('especialidades/create', [EspecialidadController::class, 'create'], ['Administrador']);
$router->post('especialidades/store', [EspecialidadController::class, 'store'], ['Administrador']);
$router->get('especialidades/edit/{id}', [EspecialidadController::class, 'edit'], ['Administrador']);
$router->post('especialidades/update/{id}', [EspecialidadController::class, 'update'], ['Administrador']);
$router->post('especialidades/delete/{id}', [EspecialidadController::class, 'delete'], ['Administrador']);
$router->post('especialidades/restore/{id}', [EspecialidadController::class, 'restore'], ['Administrador']);

// Horarios
$router->get('horarios', [HorarioController::class, 'index'], ['Administrador', 'Recepcionista']);
$router->get('horarios/create', [HorarioController::class, 'create'], ['Administrador']);
$router->post('horarios/store', [HorarioController::class, 'store'], ['Administrador']);
$router->get('horarios/edit/{id}', [HorarioController::class, 'edit'], ['Administrador']);
$router->post('horarios/update/{id}', [HorarioController::class, 'update'], ['Administrador']);
$router->post('horarios/delete/{id}', [HorarioController::class, 'delete'], ['Administrador']);
$router->post('horarios/restore/{id}', [HorarioController::class, 'restore'], ['Administrador']);

$route = $_GET['route'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $router->dispatch($method, $route);
} catch (\Throwable $e) {
    http_response_code(500);
    error_log('[Clínica Vida Salud] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    \App\Core\View::render('errors/500', ['title' => 'Error interno'], 'auth');
}
