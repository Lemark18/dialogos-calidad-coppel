# dialogos-calidad-coppel
Sistema Web de Gestión de Talleres Diálogos de Calidad - Proyecto Final IDS

## Sistema de Gestión — Taller Diálogos de Calidad
Aplicación web desarrollada en PHP y MySQL para administrar de forma centralizada la operación, logística y seguimiento de participantes de los talleres Diálogos de Calidad.

## ¿Qué hace este sistema?
El proyecto organiza el flujo completo del taller en 3 perfiles operativos conectados a una misma base de datos[cite: 5]:

1. **Planeación, Logística y Reportería (Logística):** Facilita la creación y edición de grupos, asignación de sedes (Hotel Rancho San Diego y Hotel Avándaro), validación automática para evitar que un entrenador tenga talleres empalmados en las mismas fechas y consulta de indicadores globales en tiempo real con exportación directa a Excel en un clic[cite: 5].
2. **Inscripciones y Gestión de Grupos (CCORH):** Permite dar de alta participantes de manera individual o mediante carga masiva desde Excel/CSV, respetando el cupo máximo de 26 lugares por grupo, evitando duplicados y registrando bajas o reasignaciones a grupos futuros[cite: 5].
3. **Asistencias y Seguimiento (Entrenadores):** Permite a los facilitadores consultar sus grupos asignados, controlar el envío de invitaciones por correo electrónico, registrar confirmaciones, asignar puntos de reunión (Aeropuerto CDMX o Corporativo Coppel) y pasar lista en Kick-off, Día 1 y Día 2[cite: 5].

## Estructura de los archivos principales
- `index.php` — Pantalla principal con selección de perfil operativo (Logística, CCORH y Entrenadores)[cite: 5].
- `conexion.php` — Archivo de enlace con la base de datos MySQL.
- `nuevo_grupo.php`, `editar_grupo.php`, `cronograma_visual.php`, `entrenadores.php` y `reportes.php` — Creación y modificación de grupos, calendario mensual, padrón de facilitadores y tablero ejecutivo descargable en Excel para Logística[cite: 5].
- `participantes.php` y `grupos.php` — Alta de colaboradores, validación de cupo de 26 lugares, bajas y reasignación entre grupos para CCORH[cite: 5].
- `mis_asignaciones.php`, `agenda_entrenador.php`, `cronograma_entrenadores.php`, `invitaciones.php` y `control_asistencia.php` — Agenda personal, seguimiento de invitaciones y pase de lista para Entrenadores[cite: 5].
- `cargar_demo.php` — Utilidad para cargar o limpiar grupos iniciales de prueba.
- `dialogos_calidad.sql` — Respaldo de la base de datos con la estructura de las 6 tablas[cite: 5].

## Cómo instalarlo en un servidor local (XAMPP)
1. Descarga o clona este repositorio y guarda la carpeta del proyecto dentro de `C:\xampp\htdocs\`.
2. Abre el panel de control de XAMPP e inicia los servicios de Apache y MySQL.
3. Accede a `http://localhost/phpmyadmin/`, crea una base de datos llamada `dialogos_calidad` e importa el archivo `dialogos_calidad.sql`.
4. Entra a `http://localhost/phpmyadmin/`, crea una base de datos llamada `dialogos_calidad` e importa el archivo `dialogos_calidad.sql`.
5. Verifica que los datos de acceso en `conexion.php` coincidan con tu configuración local (por defecto usuario `root` y contraseña vacía).
6. Abre tu navegador y entra a `http://localhost/nombre_de_tu_carpeta/index.php`.

---

**Autor:** Mario Alberto Ramírez Ochoa  
**Programa:** Prácticas Profesionales • Convocatoria #12 — Ingeniería en Desarrollo de Software

