<?php
// Enlazo mi conexión a la base de datos y recibo qué grupo seleccionaron
include("conexion.php");

$id_grupo_sel = isset($_GET['id_grupo']) ? intval($_GET['id_grupo']) : 0;
$mensaje = "";

// Acción 1: Marca como "Enviada" la invitación de un participante en individual
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enviar_individual'])) {
    $id_part = intval($_POST['id_participante']);
    $nombre_p = htmlspecialchars($_POST['nombre_participante']);
    $correo_p = htmlspecialchars($_POST['correo_participante']);

    $sql_upd = "UPDATE participantes_grupo SET invitacion = 'Enviada' WHERE id_participante = $id_part";
    if (mysqli_query($conexion, $sql_upd)) {
        $mensaje = "✅ Invitación enviada y registrada correctamente para <b>$nombre_p</b> ($correo_p).";
    }
}

// Acción 2: Marca de un solo jalón todas las invitaciones pendientes de ese grupo como "Enviada"
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enviar_todos'])) {
    $id_g_masivo = intval($_POST['id_grupo_masivo']);
    $sql_masivo = "UPDATE participantes_grupo 
                   SET invitacion = 'Enviada' 
                   WHERE id_grupo = $id_g_masivo AND estatus_inscripcion = 'Inscrito' AND (invitacion = 'Pendiente' OR invitacion IS NULL)";
    if (mysqli_query($conexion, $sql_masivo)) {
        $afectados = mysqli_affected_rows($conexion);
        $mensaje = "🚀 ¡Envío masivo completado! Se enviaron <b>$afectados</b> invitaciones pendientes de este grupo.";
    }
}

// Función para mostrar las fechas del taller en español
function formatearFechaEspanol($fechaStr) {
    if (empty($fechaStr) || $fechaStr == '0000-00-00') return 'N/D';
    $timestamp = strtotime($fechaStr);
    $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    return $dias[date('w', $timestamp)] . " " . date('j', $timestamp) . " de " . $meses[date('n', $timestamp)] . " de " . date('Y', $timestamp);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Módulo 4 - Gestión de Invitaciones</title>
    <!-- Estilos visuales de tarjetas de grupos, plantilla de correo y tabla de estatus -->
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
        h1, h2, h3 { color: #2c3e50; text-align: center; }

        .menu { margin-bottom: 25px; padding: 15px; background: #ecf0f1; border-radius: 5px; text-align: center; }
        .menu a { text-decoration: none; background: #2980b9; color: white; padding: 9px 16px; margin: 0 5px; border-radius: 4px; font-weight: bold; font-size: 13px; display: inline-block; }
        .menu a:hover { background: #34495e; }
        .menu a.btn-volver { background: #34495e; }

        .alerta-msg { background: #d4efdf; color: #145a32; padding: 12px; border-radius: 4px; text-align: center; margin-bottom: 20px; font-size: 14px; border: 1px solid #a9dfbf; font-weight: bold; }

        .grid-grupos { display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; margin-bottom: 25px; }
        .card-grupo { background: #f8f9fa; border: 2px solid #d5dbdb; border-radius: 8px; padding: 15px; width: 250px; text-align: center; }
        .card-grupo h4 { margin: 0 0 6px 0; color: #2c3e50; font-size: 16px; }
        .card-grupo p { margin: 4px 0; font-size: 12px; color: #566573; }
        .cupo-badge { display: inline-block; background: #2c3e50; color: white; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; margin: 8px 0; }
        .btn-ver { display: inline-block; text-decoration: none; background: #2980b9; color: white; padding: 6px 14px; border-radius: 4px; font-size: 12px; font-weight: bold; margin-top: 5px; }

        .banner-grupo { background: #ebf5fb; border: 2px solid #2980b9; border-radius: 8px; padding: 15px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .banner-grupo h3 { margin: 0; color: #1b4f72; text-align: left; }
        .banner-grupo p { margin: 4px 0 0 0; font-size: 13px; color: #34495e; }

        .plantilla-box { background: #f8f9fa; border: 1px dashed #2980b9; border-radius: 6px; padding: 15px 20px; margin-bottom: 20px; font-size: 13px; color: #2c3e50; }
        .plantilla-box h4 { margin: 0 0 8px 0; color: #1b4f72; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
        th, td { border: 1px solid #bdc3c7; padding: 9px; text-align: center; vertical-align: middle; }
        th { background: #2c3e50; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }

        .badge-pendiente { background: #fef9e7; color: #b7950b; border: 1px solid #f9e79f; padding: 4px 9px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-enviada { background: #d4efdf; color: #145a32; border: 1px solid #a9dfbf; padding: 4px 9px; border-radius: 4px; font-size: 11px; font-weight: bold; }

        .btn-enviar { background: #27ae60; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; cursor: pointer; }
        .btn-enviar:hover { background: #219653; }
        .btn-reenviar { background: #7f8c8d; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; cursor: pointer; }
        .btn-masivo { background: #27ae60; color: white; border: none; padding: 9px 18px; border-radius: 5px; font-size: 13px; font-weight: bold; cursor: pointer; }
        .btn-masivo:hover { background: #219653; }
        .btn-copiar { background: #2980b9; color: white; border: none; padding: 6px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; cursor: pointer; margin-left: 4px; }
    </style>

    <!-- Función en JavaScript para copiar el texto de la invitación y pegarlo directo en el correo -->
    <script>
    function copiarInvitacion(nombre, grupo, sede, fechas) {
        const texto = `Estimado(a) ${nombre},\n\nPor este medio te extendemos una cordial invitación al Taller "Diálogos de Calidad" correspondiente al ${grupo}.\n\n📍 Sede: ${sede}\n📅 Fechas: ${fechas}\n\nPor favor confirma tu asistencia y punto de reunión a la brevedad.\n\nSaludos,\nCoordinación Diálogos de Calidad`;
        navigator.clipboard.writeText(texto).then(() => {
            alert("📋 Texto de invitación copiado al portapapeles para " + nombre + ". Ya puedes pegarlo en tu correo.");
        });
    }
    </script>
</head>
<body>

    <div class="container">
        <h1>Taller de Diálogos de Calidad</h1>

        <!-- Barra superior para regresar al menú principal o a la lista de grupos -->
        <div class="menu">
            <a href="index.php">← Menú Principal</a>
            <?php if ($id_grupo_sel > 0): ?>
                <a href="invitaciones.php" class="btn-volver">← Ver Grupos</a>
            <?php endif; ?>
        </div>

        <h2>✉️ Módulo 4: Envío y Control de Invitaciones</h2>

        <?php if ($mensaje != "") { echo "<div class='alerta-msg'>$mensaje</div>"; } ?>

        <!-- Vista 1: Tarjetas de los grupos con el contador de invitaciones enviadas vs pendientes -->
        <?php if ($id_grupo_sel == 0): ?>
            <p style="text-align: center; color: #7f8c8d; font-size: 13px; margin-bottom: 20px;">
                Selecciona un grupo para gestionar el envío de convocatorias por correo electrónico a los participantes inscritos.
            </p>

            <div class="grid-grupos">
                <?php
                $sql_g = "SELECT g.*, 
                                 (SELECT COUNT(*) FROM participantes_grupo p WHERE p.id_grupo = g.id_grupo AND p.estatus_inscripcion = 'Inscrito') AS total_inscritos,
                                 (SELECT COUNT(*) FROM participantes_grupo p WHERE p.id_grupo = g.id_grupo AND p.estatus_inscripcion = 'Inscrito' AND p.invitacion = 'Enviada') AS total_enviadas
                          FROM grupos g 
                          ORDER BY g.fecha_inicio ASC";
                $res_g = mysqli_query($conexion, $sql_g);

                while ($g = mysqli_fetch_assoc($res_g)):
                ?>
                    <div class="card-grupo">
                        <h4><?php echo htmlspecialchars($g['nombre_grupo']); ?></h4>
                        <p><b>Sede:</b> <?php echo htmlspecialchars($g['sede']); ?></p>
                        <p><b>Fechas:</b> <?php echo $g['fecha_inicio']; ?> al <?php echo $g['fecha_fin']; ?></p>
                        <div class="cupo-badge">
                            ✉️ <?php echo $g['total_enviadas']; ?> / <?php echo $g['total_inscritos']; ?> enviadas
                        </div><br>
                        <a class="btn-ver" href="invitaciones.php?id_grupo=<?php echo $g['id_grupo']; ?>">
                            Gestionar Invitaciones
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>

        <!-- Vista 2: Listado del grupo seleccionado con botones para enviar individual, masivo o copiar texto -->
        <?php else: ?>
            <?php
            $res_info = mysqli_query($conexion, "SELECT * FROM grupos WHERE id_grupo = $id_grupo_sel");
            $info_grupo = mysqli_fetch_assoc($res_info);
            $fechas_texto = "Del " . formatearFechaEspanol($info_grupo['fecha_inicio']) . " al " . formatearFechaEspanol($info_grupo['fecha_fin']);

            $sql_part = "SELECT * FROM participantes_grupo 
                         WHERE id_grupo = $id_grupo_sel AND estatus_inscripcion = 'Inscrito' 
                         ORDER BY nombre_completo ASC";
            $res_part = mysqli_query($conexion, $sql_part);
            $total_p = mysqli_num_rows($res_part);
            ?>

            <div class="banner-grupo">
                <div>
                    <h3>📋 <?php echo htmlspecialchars($info_grupo['nombre_grupo']); ?> — Sede: <?php echo htmlspecialchars($info_grupo['sede']); ?></h3>
                    <p><b>📅 Fechas:</b> <?php echo $fechas_texto; ?></p>
                </div>

                <?php if ($total_p > 0): ?>
                <form method="POST" action="invitaciones.php?id_grupo=<?php echo $id_grupo_sel; ?>">
                    <input type="hidden" name="id_grupo_masivo" value="<?php echo $id_grupo_sel; ?>">
                    <button type="submit" name="enviar_todos" class="btn-masivo">
                        🚀 Enviar Todas las Pendientes
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <!-- Vista previa del mensaje de convocatoria -->
            <div class="plantilla-box">
                <h4>📄 Vista Previa de Plantilla Oficial de Convocatoria:</h4>
                <b>Asunto:</b> Invitación Oficial — Taller Diálogos de Calidad (<?php echo htmlspecialchars($info_grupo['nombre_grupo']); ?>)<br>
                <b>Mensaje:</b> <i>"Estimado(a) colaborador(a), te invitamos al Taller Diálogos de Calidad en <b><?php echo htmlspecialchars($info_grupo['sede']); ?></b> (<?php echo $fechas_texto; ?>). Por favor confirma tu asistencia y punto de reunión."</i>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>No. Emp.</th>
                        <th>Participante</th>
                        <th>Región</th>
                        <th>Correo Electrónico</th>
                        <th>Celular</th>
                        <th>Estatus Invitación</th>
                        <th style="width: 220px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($total_p > 0):
                        $num = 1;
                        while ($p = mysqli_fetch_assoc($res_part)):
                            $estatus_inv = $p['invitacion'] ?? 'Pendiente';
                            $es_enviada = ($estatus_inv == 'Enviada');
                    ?>
                        <tr>
                            <td><b><?php echo $num++; ?></b></td>
                            <td><?php echo htmlspecialchars($p['numero_empleado']); ?></td>
                            <td style="text-align: left;"><b><?php echo htmlspecialchars($p['nombre_completo']); ?></b></td>
                            <td><b><?php echo htmlspecialchars($p['region'] ?: '-'); ?></b></td>
                            <td><?php echo htmlspecialchars($p['correo'] ?: 'Sin correo'); ?></td>
                            <td><?php echo htmlspecialchars($p['celular'] ?: '-'); ?></td>
                            <td>
                                <?php if ($es_enviada): ?>
                                    <span class="badge-enviada">✅ Enviada</span>
                                <?php else: ?>
                                    <span class="badge-pendiente">⏳ Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="invitaciones.php?id_grupo=<?php echo $id_grupo_sel; ?>" style="display:inline;">
                                    <input type="hidden" name="id_participante" value="<?php echo $p['id_participante']; ?>">
                                    <input type="hidden" name="nombre_participante" value="<?php echo htmlspecialchars($p['nombre_completo']); ?>">
                                    <input type="hidden" name="correo_participante" value="<?php echo htmlspecialchars($p['correo']); ?>">
                                    <button type="submit" name="enviar_individual" class="<?php echo $es_enviada ? 'btn-reenviar' : 'btn-enviar'; ?>">
                                        <?php echo $es_enviada ? 'Reenviar' : '✉️ Enviar'; ?>
                                    </button>
                                </form>

                                <button type="button" class="btn-copiar" 
                                        onclick="copiarInvitacion('<?php echo addslashes($p['nombre_completo']); ?>', '<?php echo addslashes($info_grupo['nombre_grupo']); ?>', '<?php echo addslashes($info_grupo['sede']); ?>', '<?php echo addslashes($fechas_texto); ?>')"
                                        title="Copiar texto del correo">
                                    📋 Copiar
                                </button>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr><td colspan="8">No hay participantes inscritos en este grupo todavía.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </div>

</body>
</html>