<?php
// Enlazo mi conexión a la base de datos y recibo el ID del entrenador que eligieron en el filtro
include("conexion.php");

$id_entrenador_sel = isset($_GET['id_entrenador']) ? intval($_GET['id_entrenador']) : 0;

// Función para mostrar las fechas del taller en español
function formatearFechaEspanol($fechaStr) {
    if (empty($fechaStr) || $fechaStr == '0000-00-00') return 'N/D';
    $timestamp = strtotime($fechaStr);
    $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    
    $dia_semana = $dias[date('w', $timestamp)];
    $dia_mes = date('j', $timestamp);
    $mes = $meses[date('n', $timestamp)];
    $anio = date('Y', $timestamp);

    return "$dia_semana $dia_mes de $mes de $anio";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignaciones por Entrenador - Módulo 3</title>
    <!-- Estilos visuales del filtro de entrenadores y la tabla de agenda -->
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 1180px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
        h1, h2, h3 { color: #2c3e50; text-align: center; }

        .menu { margin-bottom: 25px; padding: 15px; background: #ecf0f1; border-radius: 5px; text-align: center; }
        .menu a { text-decoration: none; background: #2980b9; color: white; padding: 9px 16px; margin: 0 5px; border-radius: 4px; font-weight: bold; font-size: 13px; display: inline-block; }
        .menu a:hover { background: #34495e; }
        .menu a.btn-volver { background: #34495e; }

        .filtro-box { background: #eaf2f8; border: 1px solid #aed6f1; padding: 18px; border-radius: 6px; margin-bottom: 25px; text-align: center; }
        select { padding: 9px 14px; border: 2px solid #2980b9; border-radius: 5px; font-size: 14px; min-width: 300px; background: white; font-weight: bold; color: #2c3e50; }

        .banner-entrenador { background: #ebf5fb; border: 2px solid #2980b9; border-radius: 8px; padding: 14px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .banner-entrenador h3 { margin: 0; color: #1b4f72; font-size: 18px; }
        .badge-total { background: #2c3e50; color: white; padding: 5px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
        th, td { border: 1px solid #bdc3c7; padding: 10px; text-align: center; vertical-align: middle; }
        th { background: #2c3e50; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }

        .badge-rol { padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; color: white; display: inline-block; }
        .rol-t1 { background: #2980b9; }
        .rol-t2 { background: #16a085; }
        .rol-log { background: #8e44ad; }

        .btn-ir { background: #27ae60; color: white; text-decoration: none; padding: 5px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-block; }
        .btn-ir:hover { background: #219653; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Taller de Diálogos de Calidad</h1>

        <!-- Barra superior para regresar al menú principal o ver los grupos -->
        <div class="menu">
            <a href="index.php">← Menú Principal</a>
            <a href="mis_asignaciones.php" class="btn-volver">← Regresar a Grupos Asignados</a>
            <a href="cronograma_entrenadores.php">📅 Cronograma Visual</a>
        </div>

        <h2>👤 Asignaciones Individuales por Entrenador</h2>
        <p style="text-align: center; color: #7f8c8d; font-size: 13px; margin-bottom: 20px;">
            Selecciona tu nombre para consultar las fechas programadas, sedes a visitar y el rol asignado en cada taller.
        </p>

        <!-- Lista desplegable para filtrar por el nombre del entrenador -->
        <div class="filtro-box">
            <form method="GET" action="agenda_entrenador.php">
                <label for="id_entrenador" style="font-weight: bold; color: #1b4f72; font-size: 14px; margin-right: 10px;">
                    Seleccionar Entrenador:
                </label>
                <select name="id_entrenador" id="id_entrenador" onchange="this.form.submit()">
                    <option value="0">-- Selecciona tu nombre --</option>
                    <?php
                    $res_ent = mysqli_query($conexion, "SELECT * FROM entrenadores ORDER BY nombre_entrenador ASC");
                    while ($ent = mysqli_fetch_assoc($res_ent)) {
                        $selected = ($ent['id_entrenador'] == $id_entrenador_sel) ? 'selected' : '';
                        echo "<option value='" . $ent['id_entrenador'] . "' $selected>" . htmlspecialchars($ent['nombre_entrenador']) . "</option>";
                    }
                    ?>
                </select>
            </form>
        </div>

        <!-- Muestro los talleres donde participa el entrenador elegido como Titular 1, Titular 2 o Logística -->
        <?php if ($id_entrenador_sel > 0): ?>
            <?php
            $res_nom = mysqli_query($conexion, "SELECT nombre_entrenador FROM entrenadores WHERE id_entrenador = $id_entrenador_sel");
            $nombre_ent_sel = mysqli_fetch_assoc($res_nom)['nombre_entrenador'] ?? '';

            $sql_agenda = "SELECT g.*, 
                                  e1.nombre_entrenador AS nom1, 
                                  e2.nombre_entrenador AS nom2, 
                                  el.nombre_entrenador AS nom_log,
                                  (SELECT COUNT(*) FROM participantes_grupo p 
                                   WHERE p.id_grupo = g.id_grupo AND p.estatus_inscripcion = 'Inscrito') AS total_inscritos
                           FROM grupos g
                           LEFT JOIN entrenadores e1 ON g.id_entrenador_1 = e1.id_entrenador
                           LEFT JOIN entrenadores e2 ON g.id_entrenador_2 = e2.id_entrenador
                           LEFT JOIN entrenadores el ON g.id_entrenador_logistica = el.id_entrenador
                           WHERE g.id_entrenador_1 = $id_entrenador_sel 
                              OR g.id_entrenador_2 = $id_entrenador_sel 
                              OR g.id_entrenador_logistica = $id_entrenador_sel
                           ORDER BY g.fecha_inicio ASC";

            $res_agenda = mysqli_query($conexion, $sql_agenda);
            $total_talleres = mysqli_num_rows($res_agenda);
            ?>

            <div class="banner-entrenador">
                <h3>👤 Agenda Programada: <?php echo htmlspecialchars($nombre_ent_sel); ?></h3>
                <span class="badge-total"><?php echo $total_talleres; ?> taller(es) asignado(s)</span>
            </div>

            <?php if ($total_talleres > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Grupo</th>
                            <th>Sede / Lugar</th>
                            <th>Fechas Programadas</th>
                            <th>Tu Rol Asignado</th>
                            <th>Equipo Compañero</th>
                            <th>Inscritos</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($g = mysqli_fetch_assoc($res_agenda)): 
                            // Reviso en qué puesto quedó asignado este entrenador dentro del grupo
                            if ($g['id_entrenador_1'] == $id_entrenador_sel) {
                                $rol_texto = "Entrenador Titular 1";
                                $rol_clase = "rol-t1";
                            } elseif ($g['id_entrenador_2'] == $id_entrenador_sel) {
                                $rol_texto = "Entrenador Titular 2";
                                $rol_clase = "rol-t2";
                            } else {
                                $rol_texto = "Apoyo Logística";
                                $rol_clase = "rol-log";
                            }
                        ?>
                            <tr>
                                <td><b><?php echo htmlspecialchars($g['nombre_grupo']); ?></b></td>
                                <td>📍 <b><?php echo htmlspecialchars($g['sede']); ?></b></td>
                                <td style="text-align: left;">
                                    <b>Inicio:</b> <?php echo formatearFechaEspanol($g['fecha_inicio']); ?><br>
                                    <b>Fin:</b> <?php echo formatearFechaEspanol($g['fecha_fin']); ?>
                                </td>
                                <td><span class="badge-rol <?php echo $rol_clase; ?>"><?php echo $rol_texto; ?></span></td>
                                <td style="text-align: left; font-size: 12px;">
                                    <b>T1:</b> <?php echo htmlspecialchars($g['nom1'] ?? 'N/D'); ?><br>
                                    <b>T2:</b> <?php echo htmlspecialchars($g['nom2'] ?? 'N/D'); ?><br>
                                    <b>Log:</b> <?php echo htmlspecialchars($g['nom_log'] ?? 'N/D'); ?>
                                </td>
                                <td><b><?php echo $g['total_inscritos']; ?> / 26</b></td>
                                <td>
                                    <a class="btn-ir" href="control_asistencia.php?id_grupo=<?php echo $g['id_grupo']; ?>">
                                        Abrir Lista
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; padding: 30px; background: #f8f9fa; border-radius: 6px;">
                    Este entrenador aún no tiene talleres asignados en el calendario.
                </p>
            <?php endif; ?>

        <?php else: ?>
            <p style="text-align: center; color: #7f8c8d; margin-top: 20px;">
                👆 Selecciona un nombre en el desplegable de arriba para ver sus fechas y sedes asignadas.
            </p>
        <?php endif; ?>

    </div>

</body>
</html>