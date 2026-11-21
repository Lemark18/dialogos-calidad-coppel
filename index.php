<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Universidad Corporativa Coppel - Diálogos de Calidad</title>
    
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        .header { background-color: #2c3e50; color: white; text-align: center; padding: 30px 20px; box-shadow: 0px 2px 5px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; font-size: 40px; }
        .header p { margin: 8px 0 0 0; color: #bdc3c7; font-size: 20px; }
        
        /* Sección de selección de área con tarjetas/botones grandes */
        .role-section { max-width: 960px; margin: 45px auto; padding: 0 20px; text-align: center; }
        .role-title { color: #2c3e50; font-size: 20px; margin-bottom: 8px; }
        .role-subtitle { color: #7f8c8d; font-size: 14px; margin-bottom: 35px; }

        .role-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 25px; }
        .role-card { background: white; border-radius: 10px; padding: 30px 25px; box-shadow: 0px 4px 12px rgba(0,0,0,0.08); cursor: pointer; transition: all 0.25s ease; border-bottom: 5px solid transparent; display: flex; flex-direction: column; justify-content: space-between; }
        .role-card:hover { transform: translateY(-6px); box-shadow: 0px 8px 20px rgba(0,0,0,0.12); }

        .role-card.logistica { border-bottom-color: #e67e22; }
        .role-card.ccorh { border-bottom-color: #27ae60; }
        .role-card.entrenadores { border-bottom-color: #2980b9; }

        .role-badge { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-bottom: 15px; }
        .role-card.logistica .role-badge { background: #fef5e7; color: #e67e22; }
        .role-card.ccorh .role-badge { background: #eafaf1; color: #27ae60; }
        .role-card.entrenadores .role-badge { background: #ebf5fb; color: #2980b9; }

        .role-card h2 { color: #2c3e50; margin: 0 0 10px 0; font-size: 20px; }
        .role-card p { color: #7f8c8d; font-size: 13px; line-height: 1.5; margin-bottom: 22px; }

        .role-btn { padding: 12px 20px; border-radius: 6px; color: white; font-weight: bold; font-size: 14px; border: none; cursor: pointer; width: 100%; }
        .role-card.logistica .role-btn { background: #e67e22; }
        .role-card.ccorh .role-btn { background: #27ae60; }
        .role-card.entrenadores .role-btn { background: #2980b9; }

        /* Barra superior cuando ya se eligió un área */
        .modules-wrapper { display: none; max-width: 900px; margin: 35px auto 45px auto; padding: 0 20px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 10px; }
        .active-area-title { color: #2c3e50; font-size: 19px; margin: 0; }
        .btn-back { background: #ecf0f1; color: #2c3e50; border: 1px solid #bdc3c7; padding: 8px 16px; border-radius: 5px; font-size: 13px; font-weight: bold; cursor: pointer; transition: background 0.2s; }
        .btn-back:hover { background: #dfe6e9; }

        /* Contenedor de módulos filtrados */
        .container { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 380px)); gap: 25px; justify-content: center; }
        
        .card { background: white; border-radius: 8px; box-shadow: 0px 4px 10px rgba(0,0,0,0.08); padding: 25px; text-align: center; display: none; flex-direction: column; justify-content: space-between; border-top: 5px solid #2980b9; transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        
        .card.mod-1 { border-top-color: #27ae60; }
        .card.mod-2 { border-top-color: #e67e22; }
        .card.mod-3 { border-top-color: #2980b9; }
        .card.mod-4 { border-top-color: #8e44ad; }
        .card.mod-5 { border-top-color: #16a085; }

        .card h3 { color: #2c3e50; margin-top: 0; font-size: 17px; }
        .card p { color: #7f8c8d; font-size: 13px; line-height: 1.5; margin-bottom: 20px; }
        
        .btn { text-decoration: none; color: white; padding: 10px 20px; border-radius: 4px; font-weight: bold; font-size: 13px; display: inline-block; }
        .btn-1 { background: #27ae60; } .btn-1:hover { background: #219653; }
        .btn-2 { background: #e67e22; } .btn-2:hover { background: #d35400; }
        .btn-3 { background: #2980b9; } .btn-3:hover { background: #2471a3; }
        .btn-4 { background: #8e44ad; } .btn-4:hover { background: #7d3c98; }
        .btn-5 { background: #16a085; } .btn-5:hover { background: #117a65; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Coppel Universidad Corporativa </h1>
        <p>Sistema Web de Gestión de Talleres: Diálogos de Calidad</p>
    </div>

    <!-- Pantalla 1: Selección visual por perfil/área -->
    <div class="role-section" id="roleSection">
        <h2 class="role-title">Selecciona tu perfil operativo</h2>
        <p class="role-subtitle">Elige tu área para ingresar a tus herramientas de gestión</p>

        <div class="role-grid">
            <!-- Perfil Logística -->
            <div class="role-card logistica" onclick="seleccionarArea('logistica', 'Área de Logística')">
                <div>
                    <span class="role-badge">Planeación y Control</span>
                    <h2>Logística</h2>
                    <p>Creación de grupos, asignación de sedes, programación de entrenadores y panel de reportería.</p>
                </div>
                <button class="role-btn">Ingresar</button>
            </div>

            <!-- Perfil CCORH (Acceso directo a Grupos) -->
            <div class="role-card ccorh" onclick="window.location.href='listado_grupos.php'">
                <div>
                    <span class="role-badge">Inscripciones y Gestión de Grupos</span>
                    <h2>Coordinador de Capacitación Operación RH (CCORH)</h2>
                    <p>Registro de participantes por grupo, control de lugares disponibles y gestión de bajas.</p>
                </div>
                <button type="button" class="role-btn">Ingresar</button>
            </div>

            <!-- Perfil Entrenadores -->
            <div class="role-card entrenadores" onclick="seleccionarArea('entrenadores', 'Equipo de Entrenadores')">
                <div>
                    <span class="role-badge">Asistencias y Seguimiento</span>
                    <h2>Entrenadores</h2>
                    <p>Envío de invitaciones digitales, Consulta de grupos asignados y Toma de asistencias.</p>
                </div>
                <button class="role-btn">Ingresar</button>
            </div>
        </div>
    </div>

    <!-- Pantalla 2: Módulos habilitados para el área elegida -->
    <div class="modules-wrapper" id="modulesWrapper">
        <div class="top-bar">
            <h2 class="active-area-title" id="activeAreaTitle">Módulos disponibles</h2>
            <button class="btn-back" onclick="volverInicio()">← Inicio</button>
        </div>

        <div class="container">
            <!-- Tarjeta Logística -->
            <div class="card mod-2" data-areas="logistica">
                <div>
                    <h3>Módulo de Coordinación y Logística</h3>
                    <p>Gestión operativa de sedes, control de grupos, asignación de entrenadores y seguimiento de puntos de reunión.</p>
                </div>
                <div>
                    <a href="grupos.php" class="btn btn-2">Acceso</a>
                </div>
            </div>
            
            <!-- Tarjeta CCORH -->
<div class="card mod-1" data-areas="ccorh">
    <div>
        <h3>Módulo de Gestión de Participantes</h3>
        <p>Registro de participantes, gestión de participantes por grupo y registro de bajas.</p>
    </div>
    <div>
        <a href="listado_grupos.php" class="btn btn-1">Acceso</a>
    </div>
</div>

            <!-- Tarjeta Comunicación -->
            <div class="card mod-4" data-areas="entrenadores">
                <div>
                    <h3>Módulo de Comunicación Digital</h3>
                    <p>Control automatizado y estatus de invitaciones y notificaciones a los participantes.</p>
                </div>
                <div>
                    <a href="invitaciones.php" class="btn btn-4">Gestión de Invitaciones</a>
                </div>
            </div>

            <!-- Tarjeta Entrenadores -->
            <div class="card mod-3" data-areas="entrenadores">
                <div>
                    <h3>Módulo de Confirmación, Logística y Asistencia</h3>
                    <p>Visualización de grupos programados para entrenadores, toma de asistencia Kick off, taller presencial y cronograma de asignaciones.</p>
                </div>
                <div>
                    <a href="mis_asignaciones.php" class="btn btn-3">Acceso</a>
                </div>
            </div>

            <!-- Tarjeta Reportería (solo Logística) -->
            <div class="card mod-5" data-areas="logistica">
                <div>
                    <h3>Módulo de Control y Reportería</h3>
                    <p>Panel interactivo de ocupación, control de asistencias, y reportes en tiempo real.</p>
                </div>
                <div>
                    <a href="reportes.php" class="btn btn-5">Ver Reportes y Panel</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function seleccionarArea(area, titulo) {
            document.getElementById('roleSection').style.display = 'none';
            document.getElementById('modulesWrapper').style.display = 'block';
            document.getElementById('activeAreaTitle').textContent = titulo + ' — Módulos habilitados';

            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                const permitidas = card.getAttribute('data-areas').split(' ');
                if (permitidas.includes(area)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function volverInicio() {
            document.getElementById('modulesWrapper').style.display = 'none';
            document.getElementById('roleSection').style.display = 'block';
        }
    </script>

</body>
</html>