# Contexto del Proyecto: Sistema Web de Gestión de Citas Médicas - Clínica Vida Salud

## 1. Stack Tecnológico y Arquitectura
- **Backend:** PHP 8+ puro bajo arquitectura MVC (Modelo-Vista-Controlador), Programación Orientada a Objetos (POO), y código modular.
- **Base de Datos:** PostgreSQL. Conexión exclusiva mediante `PDO` (`pdo_pgsql`). Consultas siempre parametrizadas (`prepare` / `execute`) para prevenir inyección SQL.
- **Frontend:** HTML5, CSS3 (Bootstrap 5 vía CDN o estilos limpios) y JavaScript Vanilla (ES6+) para validaciones e interactividad (AJAX/Fetch).
- **Seguridad:** Contraseñas hasheadas con `password_hash()` y verificadas con `password_verify()`. Control estricto de sesiones PHP (`session_start()`) y verificación de roles por ruta.

## 2. Estructura del Proyecto
- `/config/Database.php` -> Conexión PDO Singleton a PostgreSQL.
- `/database/schema.sql` -> DDL de la base de datos con tablas, llaves foráneas e índices únicos.
- `/database/seed.sql` -> Datos iniciales (roles, usuarios, médicos, especialidades, horarios, pacientes).
- `/app/Models/` -> Clases de acceso a datos para cada entidad.
- `/app/Controllers/` -> Lógica de negocio y control de peticiones.
- `/views/` -> Interfaz de usuario organizada por módulos (auth, pacientes, medicos, especialidades, horarios, citas, reportes).
- `index.php` -> Enrutador frontal (Front Controller).

## 3. Conexión a Base de Datos Local (PostgreSQL)
- **Host:** `localhost`
- **Puerto:** `5432`
- **Nombre de BD:** `bd_clinica_vida_salud`
- **Usuario:** `postgres`
- **Contraseña:** *(la que hayas configurado en tu entorno local de PostgreSQL)*

## 4. Reglas de Negocio Críticas (RN01 - RN14)
1. **Borrado Lógico Obligatorio:** Nunca se realizan borrados físicos (`DELETE`) de pacientes, médicos o citas. Se utiliza un campo de estado (`Activo` / `Inactivo`) para preservar la trazabilidad y el historial médico.
2. **Médicos Inactivos:** Un médico inactivo no puede recibir nuevas citas, pero su agenda histórica se debe conservar intacta.
3. **Validación de Horarios:** Las citas deben ajustarse estrictores a los bloques configurados (por defecto bloques de 30 minutos) dentro del horario de atención del médico para ese día de la semana (`hora_fin > hora_inicio`).
4. **Prevención de Citas Duplicadas:** A nivel de base de datos se utiliza un índice único parcial para impedir que un médico tenga dos citas superpuestas en la misma fecha y hora (excepto si la cita previa está `Cancelada`).
5. **Liberación de Horarios:** Si una cita pasa a estado `Cancelada`, el bloque horario vuelve a quedar disponible de inmediato para nuevas reservas.
6. **Restricción de Estados:** Las citas `Atendida` o `Cancelada` no se pueden reprogramar directamente ni cambiar de flujo arbitrariamente.
7. **Transacciones:** Las operaciones críticas de programación y reprogramación de citas deben ejecutarse dentro de transacciones PDO (`beginTransaction`, `commit`, `rollBack`) verificando disponibilidad en el servidor en tiempo real. 

## 5. Roles del Sistema y Permisos
- **Administrador:** Gestión de usuarios, roles, médicos, especialidades, horarios y reportes generales[cite: 8].
- **Recepcionista:** Registro de pacientes, consulta de disponibilidad, gestión de citas (programar, reprogramar, cancelar) y control de asistencia[cite: 9].
- **Médico:** Consulta de agenda diaria, filtrado por fecha/estado, visualización de datos del paciente y marcado de citas como "Atendida"[cite: 9, 10].
- **Paciente:** Perfil de usuario (datos personales, contacto de emergencia, historial médico) y seguimiento de citas[cite: 10].