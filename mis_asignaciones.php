<?php
// Enlazo mi conexión a la base de datos y preparo la función para mostrar fechas en español
include("conexion.php");

function formatearFechaEspanol($fechaStr) {
    if (empty($fechaStr) || $fechaStr == '0000-00-00') return 'N/D';
    $timestamp = strtotime($fechaStr);
    $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    return $dias[date('w', $timestamp)] . " " . date('j', $timestamp) . " de " . $meses[date('n', $timestamp)] . " de " . date('Y', $timestamp);
}

// Cargo los participantes inscritos para que el entrenador pueda buscarlos rápido
$sql_busq = "SELECT p.nombre_completo, p.numero_empleado, p.region, p.puesto, 
                    g.id_grupo, g.nombre_grupo, g.sede, g.fecha_inicio, g.fecha_fin
             FROM participantes_grupo p
             JOIN grupos g ON p.id_grupo = g.id_grupo
             WHERE p.estatus_inscripcion = 'Inscrito'
             ORDER BY p.nombre_completo ASC";
$res_busq = mysqli_query($conexion, $sql_busq);
$lista_busqueda = [];
while ($rb = mysqli_fetch_assoc($res_busq)) {
    $lista_busqueda[] = $rb;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Módulo Entrenadores - Talleres Asignados</title>
    <!-- Estilos visuales de las tarjetas de grupos y el buscador de participantes -->
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #2c3e50; text-align: center; }

        .menu { margin-bottom: 25px; padding: 15px; background: #ecf0f1; border-radius: 5px; text-align: center; }
        .menu a { text-decoration: none; background: #2980b9; color: white; padding: 9px 16px; margin: 0 5px; border-radius: 4px; font-weight: bold; font-size: 13px; display: inline-block; }
        .menu a:hover { background: #34495e; }

        .buscador-box { background: #ebf5fb; border: 1px solid #aed6f1; border-radius: 6px; padding: 15px 20px; margin-bottom: 25px; }
        .buscador-box label { display: block; font-weight: bold; color: #1b4f72; font-size: 13px; margin-bottom: 8px; }
        .buscador-box input { width: 100%; box-sizing: border-box; padding: 10px 12px; border: 2px solid #2980b9; border-radius: 5px; font-size: 14px; }
        .resultados-busq { margin-top: 10px; display: none; background: white; border: 1px solid #bdc3c7; border-radius: 5px; max-height: 260px; overflow-y: auto; }
        .item-res { padding: 10px 14px; border-bottom: 1px solid #ecf0f1; display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
        .item-res:last-child { border-bottom: none; }
        .item-res:hover { background: #f8f9fa; }
        .btn-ir-lista { background: #27ae60; color: white; text-decoration: none; padding: 5px 12px; border-radius: 4px; font-weight: bold; font-size: 12px; }

        .grid-grupos { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; margin-top: 15px; }
        .card-grupo { background: #f8f9fa; border: 2px solid #d5dbdb; border-radius: 8px; padding: 18px; width: 295px; text-align: center; }
        .card-grupo h4 { margin: 0 0 6px 0; color: #1b4f72; font-size: 17px; }
        .card-grupo p { margin: 4px 0; font-size: 12.5px; color: #34495e; }
        .fechas-pill { background: #ebf5fb; border: 1px solid #aed6f1; color: #1b4f72; padding: 6px 10px; border-radius: 5px; font-size: 12px; font-weight: bold; margin: 10px 0; }
        .staff-box { background: white; border: 1px solid #e5e8e8; border-radius: 5px; padding: 8px 10px; text-align: left; font-size: 12px; color: #2c3e50; margin-bottom: 10px; line-height: 1.5; }
        .cupo-badge { display: inline-block; background: #2c3e50; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; margin-bottom: 10px; }
        .btn-gestionar { display: inline-block; text-decoration: none; background: #2980b9; color: white; padding: 8px 18px; border-radius: 4px; font-size: 13px; font-weight: bold; }
        .btn-gestionar:hover { background: #1f618d; }
    </style>

    <!-- Filtro en JavaScript para buscar al colaborador y saltar directo a su lista de asistencia -->
    <script>
    const participantesData = <?php echo json_encode($lista_busqueda, JSON_UNESCAPED_UNICODE); ?>;

    function filtrarParticipantes(texto) {
        const contenedor = document.getElementById('resultadosBuscador');
        const q = texto.trim().toLowerCase();

        if (q.length < 2) {
            contenedor.style.display = 'none';
            contenedor.innerHTML = '';
            return;
        }

        const coincidencias = participantesData.filter(p =>
            (p.nombre_completo && p.nombre_completo.toLowerCase().includes(q)) ||
            (p.numero_empleado && p.numero_empleado.toLowerCase().includes(q)) ||
            (p.nombre_grupo && p.nombre_grupo.toLowerCase().includes(q)) ||
            (p.region && p.region.toLowerCase().includes(q))
        );

        contenedor.style.display = 'block';
        if (coincidencias.length === 0) {
            contenedor.innerHTML = '<div style="padding:12px; color:#7f8c8d; text-align:center;">No se encontraron participantes inscritos con ese criterio.</div>';
            return;
        }

        let html = '';
        coincidencias.slice(0, 20).forEach(p => {
            html += `
                <div class="item-res">
                    <div>
                        <b>${p.nombre_completo}</b> <span style="color:#7f8c8d;">(No. Emp: ${p.numero_empleado})</span><br>
                        <small style="color:#1b4f72;"><b>${p.nombre_grupo}</b> — Sede: ${p.sede} (${p.fecha_inicio} al ${p.fecha_fin}) | Región: ${p.region || 'N/D'}</small>
                    </div>
                    <a href="control_asistencia.php?id_grupo=${p.id_grupo}" class="btn-ir-lista">Ir a Lista →</a>
                </div>
            `;
        });
        contenedor.innerHTML = html;
    }
    </script>
</head>
<body>

    <div class="container">
        <h1>Taller de Diálogos de Calidad</h1>

        <!-- Barra superior para ver asignaciones individuales o el cronograma visual -->
        <div class="menu">
            <a href="index.php">← Menú Principal</a>
            <a href="agenda_entrenador.php">👤 Asignaciones por Entrenador</a>
            <a href="cronograma_entrenadores.php">📅 Cronograma Visual</a>
        </div>

        <h2>Módulo Entrenadores: Talleres Asignados</h2>
        <p style="text-align: center; color: #7f8c8d; font-size: 13px; margin-bottom: 18px;">
            Selecciona un grupo para gestionar confirmaciones, asistencia al Kick-off, puntos de reunión y asistencia de Día 1 y Día 2.
        </p>

        <!-- Buscador rápido para ubicar en qué grupo está cada colaborador -->
        <div class="buscador-box">
            <label for="inputBuscador">🔎 Buscar Participante (Inscritos activos):</label>
            <input type="text" id="inputBuscador" onkeyup="filtrarParticipantes(this.value)" 
                   placeholder="Escribe nombre, número de empleado, grupo o región...">
            <div id="resultadosBuscador" class="resultados-busq"></div>
        </div>

        <!-- Tarjetas de cada grupo con sus entrenadores asignados y el acceso al pase de lista -->
        <div class="grid-grupos">
            <?php
            $sql_g = "SELECT g.*, 
                             e1.nombre_entrenador AS nom1, 
                             e2.nombre_entrenador AS nom2, 
                             el.nombre_entrenador AS nom_log,
                             (SELECT COUNT(*) FROM participantes_grupo p WHERE p.id_grupo = g.id_grupo AND p.estatus_inscripcion = 'Inscrito') AS total_inscritos
                      FROM grupos g
                      LEFT JOIN entrenadores e1 ON g.id_entrenador_1 = e1.id_entrenador
                      LEFT JOIN entrenadores e2 ON g.id_entrenador_2 = e2.id_entrenador
                      LEFT JOIN entrenadores el ON g.id_entrenador_logistica = el.id_entrenador
                      ORDER BY g.fecha_inicio ASC";
            $res_g = mysqli_query($conexion, $sql_g);

            while ($g = mysqli_fetch_assoc($res_g)):
            ?>
                <div class="card-grupo">
                    <h4><?php echo htmlspecialchars($g['nombre_grupo']); ?></h4>
                    <p><b>Sede:</b> <?php echo htmlspecialchars($g['sede']); ?></p>
                    <div class="fechas-pill">
                        📅 Del <?php echo formatearFechaEspanol($g['fecha_inicio']); ?><br>
                        al <?php echo formatearFechaEspanol($g['fecha_fin']); ?>
                    </div>
                    <div class="staff-box">
                        👤 <b>Titular 1:</b> <?php echo htmlspecialchars($g['nom1'] ?? 'N/D'); ?><br>
                        👤 <b>Titular 2:</b> <?php echo htmlspecialchars($g['nom2'] ?? 'N/D'); ?><br>
                        📋 <b>Logística:</b> <?php echo htmlspecialchars($g['nom_log'] ?? 'N/D'); ?>
                    </div>
                    <div class="cupo-badge"><?php echo $g['total_inscritos']; ?> / 26 inscritos</div><br>
                    <a class="btn-gestionar" href="control_asistencia.php?id_grupo=<?php echo $g['id_grupo']; ?>">
                        Gestionar Grupo
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

</body>
</html>