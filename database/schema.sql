-- ============================================================
-- Clínica Vida Salud - Esquema de Base de Datos
-- Motor: PostgreSQL 12+
-- Base de datos: bd_clinica_vida_salud
-- ============================================================
-- Ejecutar conectado a la base de datos:
--   psql -U postgres -d bd_clinica_vida_salud -f database/schema.sql
--
-- Si la base de datos no existe, crearla previamente:
--   CREATE DATABASE bd_clinica_vida_salud
--     WITH ENCODING 'UTF8' TEMPLATE template0;
-- ============================================================

-- Limpieza (orden inverso a las dependencias)
DROP TABLE IF EXISTS citas CASCADE;
DROP TABLE IF EXISTS horarios CASCADE;
DROP TABLE IF EXISTS medicos CASCADE;
DROP TABLE IF EXISTS pacientes CASCADE;
DROP TABLE IF EXISTS especialidades CASCADE;
DROP TABLE IF EXISTS usuarios CASCADE;
DROP TABLE IF EXISTS roles CASCADE;
DROP FUNCTION IF EXISTS set_updated_at() CASCADE;

-- Función genérica para actualizar updated_at
CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = CURRENT_TIMESTAMP;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ------------------------------------------------------------
-- 1. Roles
-- ------------------------------------------------------------
CREATE TABLE roles (
  id          SERIAL PRIMARY KEY,
  nombre      VARCHAR(50) NOT NULL UNIQUE,
  descripcion VARCHAR(255),
  estado      VARCHAR(10) NOT NULL DEFAULT 'Activo'
              CHECK (estado IN ('Activo', 'Inactivo')),
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER trg_roles_updated_at
BEFORE UPDATE ON roles
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ------------------------------------------------------------
-- 2. Usuarios
-- ------------------------------------------------------------
CREATE TABLE usuarios (
  id               SERIAL PRIMARY KEY,
  rol_id           INTEGER NOT NULL REFERENCES roles(id)
                   ON UPDATE CASCADE ON DELETE RESTRICT,
  nombres          VARCHAR(100) NOT NULL,
  apellidos        VARCHAR(100) NOT NULL,
  email            VARCHAR(150) NOT NULL UNIQUE,
  password_hash    VARCHAR(255) NOT NULL,
  telefono         VARCHAR(20),
  documento_tipo   VARCHAR(20) NOT NULL DEFAULT 'DNI'
                   CHECK (documento_tipo IN ('DNI', 'CE', 'PASAPORTE')),
  documento_numero VARCHAR(20) NOT NULL UNIQUE,
  estado           VARCHAR(15) NOT NULL DEFAULT 'Activo'
                   CHECK (estado IN ('Activo', 'Inactivo', 'Suspendido')),
  ultimo_acceso    TIMESTAMP,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_usuarios_rol ON usuarios(rol_id);
CREATE INDEX idx_usuarios_estado ON usuarios(estado);

CREATE TRIGGER trg_usuarios_updated_at
BEFORE UPDATE ON usuarios
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ------------------------------------------------------------
-- 3. Especialidades
-- ------------------------------------------------------------
CREATE TABLE especialidades (
  id          SERIAL PRIMARY KEY,
  nombre      VARCHAR(100) NOT NULL UNIQUE,
  descripcion TEXT,
  estado      VARCHAR(10) NOT NULL DEFAULT 'Activo'
              CHECK (estado IN ('Activo', 'Inactivo')),
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER trg_especialidades_updated_at
BEFORE UPDATE ON especialidades
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ------------------------------------------------------------
-- 4. Médicos
-- ------------------------------------------------------------
CREATE TABLE medicos (
  id                 SERIAL PRIMARY KEY,
  usuario_id         INTEGER NOT NULL UNIQUE REFERENCES usuarios(id)
                     ON UPDATE CASCADE ON DELETE RESTRICT,
  especialidad_id    INTEGER NOT NULL REFERENCES especialidades(id)
                     ON UPDATE CASCADE ON DELETE RESTRICT,
  numero_colegiatura VARCHAR(50) NOT NULL UNIQUE,
  biografia          TEXT,
  tarifa_consulta    NUMERIC(10,2) NOT NULL DEFAULT 0.00,
  estado             VARCHAR(10) NOT NULL DEFAULT 'Activo'
                     CHECK (estado IN ('Activo', 'Inactivo')),
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_medicos_especialidad ON medicos(especialidad_id);
CREATE INDEX idx_medicos_estado ON medicos(estado);

CREATE TRIGGER trg_medicos_updated_at
BEFORE UPDATE ON medicos
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ------------------------------------------------------------
-- 5. Pacientes
-- ------------------------------------------------------------
CREATE TABLE pacientes (
  id                           SERIAL PRIMARY KEY,
  usuario_id                   INTEGER UNIQUE REFERENCES usuarios(id)
                               ON UPDATE CASCADE ON DELETE SET NULL,
  documento_tipo               VARCHAR(20) NOT NULL DEFAULT 'DNI'
                               CHECK (documento_tipo IN ('DNI', 'CE', 'PASAPORTE')),
  documento_numero             VARCHAR(20) NOT NULL UNIQUE,
  nombres                      VARCHAR(100) NOT NULL,
  apellidos                    VARCHAR(100) NOT NULL,
  fecha_nacimiento             DATE NOT NULL,
  genero                       VARCHAR(10) NOT NULL DEFAULT 'O'
                               CHECK (genero IN ('M', 'F', 'O')),
  email                        VARCHAR(150),
  telefono                     VARCHAR(20),
  direccion                    VARCHAR(255),
  grupo_sanguineo              VARCHAR(5),
  alergias                     TEXT,
  contacto_emergencia_nombre   VARCHAR(100),
  contacto_emergencia_telefono VARCHAR(20),
  estado                       VARCHAR(10) NOT NULL DEFAULT 'Activo'
                               CHECK (estado IN ('Activo', 'Inactivo')),
  created_at                   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_pacientes_nombre ON pacientes(apellidos, nombres);
CREATE INDEX idx_pacientes_estado ON pacientes(estado);

CREATE TRIGGER trg_pacientes_updated_at
BEFORE UPDATE ON pacientes
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ------------------------------------------------------------
-- 6. Horarios (bloques de atención)
--    Cada fila representa un bloque/slot de atención.
--    Ej.: Lunes 09:00-09:30, Lunes 09:30-10:00, etc.
--    dia_semana: 1=Lunes, 2=Martes, ..., 7=Domingo.
-- ------------------------------------------------------------
CREATE TABLE horarios (
  id          SERIAL PRIMARY KEY,
  medico_id   INTEGER NOT NULL REFERENCES medicos(id)
              ON UPDATE CASCADE ON DELETE RESTRICT,
  dia_semana  SMALLINT NOT NULL CHECK (dia_semana BETWEEN 1 AND 7),
  hora_inicio TIME NOT NULL,
  hora_fin    TIME NOT NULL,
  estado      VARCHAR(10) NOT NULL DEFAULT 'Activo'
              CHECK (estado IN ('Activo', 'Inactivo')),
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_horarios_horas CHECK (hora_fin > hora_inicio),
  CONSTRAINT uq_horarios_medico_dia_hora UNIQUE (medico_id, dia_semana, hora_inicio),
  CONSTRAINT uq_horarios_id_medico_horas UNIQUE (id, medico_id, hora_inicio, hora_fin)
);

CREATE INDEX idx_horarios_medico_dia ON horarios(medico_id, dia_semana);

CREATE TRIGGER trg_horarios_updated_at
BEFORE UPDATE ON horarios
FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ------------------------------------------------------------
-- 7. Citas
--    El índice único parcial impide que un médico tenga dos
--    citas activas en el mismo bloque/fecha. Las citas en
--    estado 'Cancelada' no participan del índice y liberan
--    el bloque de inmediato.
-- ------------------------------------------------------------
CREATE TABLE citas (
  id          BIGSERIAL PRIMARY KEY,
  paciente_id INTEGER NOT NULL REFERENCES pacientes(id)
              ON UPDATE CASCADE ON DELETE RESTRICT,
  medico_id   INTEGER NOT NULL REFERENCES medicos(id)
              ON UPDATE CASCADE ON DELETE RESTRICT,
  horario_id  INTEGER NOT NULL,
  fecha       DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin    TIME NOT NULL,
  estado      VARCHAR(20) NOT NULL DEFAULT 'Pendiente'
              CHECK (estado IN ('Pendiente', 'Confirmada', 'Atendida', 'Cancelada', 'No_Asistio')),
  motivo      TEXT,
  notas       TEXT,
  created_by  INTEGER REFERENCES usuarios(id)
              ON UPDATE CASCADE ON DELETE SET NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_citas_horas CHECK (hora_fin > hora_inicio),
  CONSTRAINT fk_citas_horario FOREIGN KEY (horario_id, medico_id, hora_inicio, hora_fin)
    REFERENCES horarios(id, medico_id, hora_inicio, hora_fin)
    ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE INDEX idx_citas_paciente ON citas(paciente_id);
CREATE INDEX idx_citas_medico_fecha ON citas(medico_id, fecha);
CREATE INDEX idx_citas_fecha_estado ON citas(fecha, estado);
CREATE INDEX idx_citas_estado ON citas(estado);

-- Índice único parcial para evitar citas superpuestas
CREATE UNIQUE INDEX uq_citas_medico_fecha_horario_activa
ON citas (medico_id, fecha, horario_id)
WHERE estado <> 'Cancelada';

CREATE TRIGGER trg_citas_updated_at
BEFORE UPDATE ON citas
FOR EACH ROW EXECUTE FUNCTION set_updated_at();
