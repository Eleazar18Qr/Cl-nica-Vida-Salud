-- ============================================================
-- Clínica Vida Salud - Datos de prueba iniciales (seed)
-- Motor: PostgreSQL 12+
-- Base de datos: bd_clinica_vida_salud
-- ============================================================
-- Credenciales de prueba (contraseñas en texto plano):
--   Administrador : admin@clinicavidasalud.com      / Admin123!
--   Recepcionista : recepcion@clinicavidasalud.com  / Recepcion123!
--   Médico        : medico@clinicavidasalud.com     / Medico123!
--   Paciente      : paciente@clinicavidasalud.com   / Paciente123!
--
-- Los hashes fueron generados con password_hash() de PHP
-- (algoritmo PASSWORD_DEFAULT / bcrypt).
-- ============================================================

BEGIN;

-- Limpieza de datos previos (solo para entorno de desarrollo)
TRUNCATE TABLE citas, horarios, medicos, pacientes, especialidades, usuarios, roles
  RESTART IDENTITY CASCADE;

-- ------------------------------------------------------------
-- 1. Roles
-- ------------------------------------------------------------
INSERT INTO roles (nombre, descripcion, estado) VALUES
  ('Administrador', 'Gestión de usuarios, roles, médicos, especialidades, horarios y reportes generales.', 'Activo'),
  ('Recepcionista', 'Registro de pacientes, consulta de disponibilidad, gestión de citas y control de asistencia.', 'Activo'),
  ('Medico', 'Consulta de agenda diaria, filtrado por fecha/estado, visualización de datos del paciente y marcado de citas como Atendida.', 'Activo'),
  ('Paciente', 'Perfil de usuario, datos personales, contacto de emergencia, historial médico y seguimiento de citas.', 'Activo');

-- ------------------------------------------------------------
-- 2. Usuarios (contraseñas hasheadas con password_hash)
-- ------------------------------------------------------------
INSERT INTO usuarios (rol_id, nombres, apellidos, email, password_hash, telefono, documento_tipo, documento_numero, estado) VALUES
  (
    (SELECT id FROM roles WHERE nombre = 'Administrador'),
    'Carlos', 'Administrador', 'admin@clinicavidasalud.com',
    '$2y$12$vmFvVGQ/DAwg860WezGob.RcJyVJjgF1JyPiXqyqtHleorBBz6Cum',
    '999111222', 'DNI', '10000001', 'Activo'
  ),
  (
    (SELECT id FROM roles WHERE nombre = 'Recepcionista'),
    'Rosa', 'Recepcionista', 'recepcion@clinicavidasalud.com',
    '$2y$12$pHM3J63i3DZ10ejclI1I8eO6hgyaW5ftc8PADtgjjz8OQ8CIC/gLy',
    '999222333', 'DNI', '10000002', 'Activo'
  ),
  (
    (SELECT id FROM roles WHERE nombre = 'Medico'),
    'Juan', 'Pérez', 'medico@clinicavidasalud.com',
    '$2y$12$hNNY1c33ig5W6sz./6uw1OkzCZ6794sujawA2LUmWUOa8O2bV/Kha',
    '999333444', 'DNI', '10000003', 'Activo'
  ),
  (
    (SELECT id FROM roles WHERE nombre = 'Paciente'),
    'María', 'Gómez', 'paciente@clinicavidasalud.com',
    '$2y$12$XVvLadOHVNnl6.1bXlpjFeNcxwKzJMSLxujPiD1ARAZd4r3nIMzdK',
    '999444555', 'DNI', '10000004', 'Activo'
  );

-- ------------------------------------------------------------
-- 3. Especialidades
-- ------------------------------------------------------------
INSERT INTO especialidades (nombre, descripcion, estado) VALUES
  ('Medicina General', 'Atención médica integral y preventiva.', 'Activo'),
  ('Pediatría', 'Atención médica especializada en niños y adolescentes.', 'Activo'),
  ('Cardiología', 'Diagnóstico y tratamiento de enfermedades cardiovasculares.', 'Activo');

-- ------------------------------------------------------------
-- 4. Médico de prueba
-- ------------------------------------------------------------
INSERT INTO medicos (usuario_id, especialidad_id, numero_colegiatura, biografia, tarifa_consulta, estado) VALUES (
  (SELECT id FROM usuarios WHERE email = 'medico@clinicavidasalud.com'),
  (SELECT id FROM especialidades WHERE nombre = 'Medicina General'),
  'CMP-12345',
  'Médico general con 10 años de experiencia en atención ambulatoria.',
  50.00,
  'Activo'
);

-- ------------------------------------------------------------
-- 5. Paciente de prueba
-- ------------------------------------------------------------
INSERT INTO pacientes (
  usuario_id, documento_tipo, documento_numero, nombres, apellidos,
  fecha_nacimiento, genero, email, telefono, direccion,
  grupo_sanguineo, alergias, contacto_emergencia_nombre,
  contacto_emergencia_telefono, estado
) VALUES (
  (SELECT id FROM usuarios WHERE email = 'paciente@clinicavidasalud.com'),
  'DNI', '10000004', 'María', 'Gómez',
  '1990-05-15', 'F', 'paciente@clinicavidasalud.com', '999444555',
  'Av. Siempre Viva 123', 'O+', 'Penicilina',
  'José Gómez', '999777666', 'Activo'
);

-- ------------------------------------------------------------
-- 6. Horario de atención del médico
--    Bloques de 30 minutos: lunes, miércoles y viernes
--    de 09:00 a 12:00.
-- ------------------------------------------------------------
INSERT INTO horarios (medico_id, dia_semana, hora_inicio, hora_fin, estado)
SELECT
  m.id,
  d.dia,
  t.hora_inicio,
  t.hora_inicio + INTERVAL '30 minutes',
  'Activo'
FROM medicos m
CROSS JOIN (VALUES (1), (3), (5)) AS d(dia)
CROSS JOIN (VALUES
  (TIME '09:00'),
  (TIME '09:30'),
  (TIME '10:00'),
  (TIME '10:30'),
  (TIME '11:00'),
  (TIME '11:30')
) AS t(hora_inicio)
WHERE m.numero_colegiatura = 'CMP-12345';

COMMIT;
