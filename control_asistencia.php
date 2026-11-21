<?php
// Enlazo mi conexión a la base de datos y verifico qué grupo pidió abrir el entrenador
include("conexion.php");

$mensaje = "";
$id_grupo_sel = isset($_GET['id_grupo']) ? intval($_GET['id_grupo']) : 0;

if ($id_grupo_sel == 0) {
    header("Location: mis_asignaciones.php");
    exit();
}

// Acción 1: Cuando un entrenador da de baja a alguien, respaldo sus datos y anoto qué entrenador la reportó
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_participante'])) {
    $id_part         = mysqli_real_escape_string($conexion, $_POST['id_participante_eliminar']);
    $entrenador_baja = mysqli_real_escape_string($conexion, trim($_POST['entrenador_baja']));
    $motivo_raw      = mysqli_real_escape_string($conexion, trim($_POST['motivo_baja']));
    
    $motivo_completo = $motivo_raw . " (Reportado por: " . $entrenador_baja . ")";
    
    $res_p = mysqli_query($conexion, "SELECT * FROM participantes_grupo WHERE id_participante = '$id_part'");
    if ($p = mysqli_fetch_assoc($res_p)) {
        $nombre   = mysqli_real_escape_string($conexion, $p['nombre_completo']);
        $num_emp  = mysqli_real_escape_string($conexion, $p['numero_empleado']);
        $puesto   = mysqli_real_escape_string($conexion, $p['puesto'] ?? '');
        $centro   = mysqli_real_escape_string($conexion, $p['numero_centro'] ?? '');
        $id_grupo = intval($p['id_grupo']);
        $region   = mysqli_real_escape_string($conexion, $p['region'] ?? '');
        $correo   = mysqli_real_escape_string($conexion, $p['correo'] ?? '');
        $celular  = mysqli_real_escape_string($conexion, $p['celular'] ?? '');

        $sql_baja = "INSERT INTO bajas_participantes 
                     (nombre_completo, numero_empleado, puesto, numero_centro, id_grupo, eliminado_por, motivo_baja, region, correo, celular) 
                     VALUES 
                     ('$nombre', '$num_emp', '$puesto', '$centro', $id_grupo, 'Entrenador', '$motivo_completo', '$region', '$correo', '$celular')";
        mysqli_query($conexion, $sql_baja);

        mysqli_query($conexion, "DELETE FROM control_entrenadores WHERE id_participante = '$id_part'");
        mysqli_query($conexion, "DELETE FROM participantes_grupo WHERE id_participante = '$id_part'");
        
        $mensaje = "⚠️ El participante <b>$nombre</b> fue dado de baja por <b>$entrenador_baja</b>. Se notificó al CCORH.";
    }
}

// Acción 2: Guardo en lote las confirmaciones, Kick-off, puntos de reunión y asistencias de Día 1 y Día 2
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_control'])) {
    if (isset($_POST['control']) && is_array($_POST['control'])) {
        foreach ($_POST['control'] as $id_part_key => $datos) {
            $id_part_clean = intval($id_part_key);
            $confirmacion  = mysqli_real_escape_string($conexion, $datos['confirmacion'] ?? 'Pendiente');
            $motivo_notas  = mysqli_real_escape_string($conexion, $datos['motivo_inasistencia'] ?? '');

            // Si no confirmó asistencia, dejo bloqueados los campos operativos del taller
            if ($confirmacion != 'Confirmó') {
                $kickoff = 'Pendiente';
                $punto   = 'Ninguno';
                $dia1    = 'Pendiente';
                $dia2    = 'Pendiente';
            } else {
                $kickoff = mysqli_real_escape_string($conexion, $datos['asistencia_kickoff'] ?? 'Pendiente');
                $punto   = mysqli_real_escape_string($conexion, $datos['punto_reunion'] ?? 'Ninguno');
                $dia1    = mysqli_real_escape_string($conexion, $datos['asistencia_dia1'] ?? 'Pendiente');
                $dia2    = mysqli_real_escape_string($conexion, $datos['asistencia_dia2'] ?? 'Pendiente');
            }

            $check = mysqli_query($conexion, "SELECT id_control FROM control_entrenadores WHERE id_participante = $id_part_clean");
            if (mysqli_num_rows($check) > 0) {
                $sql_update = "UPDATE control_entrenadores SET 
                                confirmacion        = '$confirmacion',
                                asistencia_kickoff  = '$kickoff',
                                punto_reunion       = '$punto',
                                asistencia_dia1     = '$dia1',
                                asistencia_dia2     = '$dia2',
                                motivo_inasistencia = '$motivo_notas'
                               WHERE id_participante = $id_part_clean";
                mysqli_query($conexion, $sql_update);
            } else {
                $sql_insert = "INSERT INTO control_entrenadores 
                               (id_participante, confirmacion, asistencia_kickoff, punto_reunion, asistencia_dia1, asistencia_dia2, motivo_inasistencia)
                               VALUES 
                               ($id_part_clean, '$confirmacion', '$kickoff', '$punto', '$dia1', '$dia2', '$motivo_notas')";
                mysqli_query($conexion, $sql_insert);
            }
        }
        $mensaje = "✅ ¡Control de confirmaciones, asistencias y puntos de reunión guardado correctamente!";
    }
}

// Consulto los datos del grupo y quiénes son sus 3 entrenadores asignados
$sql_grupo = "SELECT g.*, 
                     e1.nombre_entrenador AS nom1, 
                     e2.nombre_entrenador AS nom2, 
                     el.nombre_entrenador AS nom_log
              FROM grupos g
              LEFT JOIN entrenadores e1 ON g.id_entrenador_1 = e1.id_entrenador
              LEFT JOIN entrenadores e2 ON g.id_entrenador_2 = e2.id_entrenador
              LEFT JOIN entrenadores el ON g.id_entrenador_logistica = el.id_entrenador
              WHERE g.id_grupo = '$id_grupo_sel'";
$res_grupo = mysqli_query($conexion, $sql_grupo);
$info_grupo = mysqli_fetch_assoc($res_grupo);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control de Asistencia y Logística - Entrenadores</title>
    <!-- Estilos visuales de la tabla de pase de lista y la ventana emergente de baja -->
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 1320px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #2c3e50; text-align: center; }
        
        .menu { margin-bottom: 25px; padding: 15px; background: #ecf0f1; border-radius: 5px; text-align: center; }
        .menu a { text-decoration: none; background: #2980b9; color: white; padding: 9px 16px; margin: 0 5px; border-radius: 4px; font-weight: bold; font-size: 13px; display: inline-block; }
        .menu a:hover { background: #34495e; }
        .menu a.btn-volver-grupos { background: #34495e; }
        .menu a.btn-volver-grupos:hover { background: #2c3e50; }

        .banner-grupo { background: #ebf5fb; border: 2px solid #2980b9; border-radius: 8px; padding: 15px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .banner-grupo h3 { margin: 0; color: #1b4f72; font-size: 18px; }
        .banner-grupo p { margin: 4px 0 0 0; font-size: 13px; color: #34495e; }

        select, input { padding: 6px; border: 1px solid #bdc3c7; border-radius: 4px; font-size: 12px; background: white; }
        select:disabled { background-color: #eaeded; color: #95a5a6; cursor: not-allowed; }
        
        .contenedor-boton { text-align: center; margin-top: 22px; }
        .btn-guardar { background: #27ae60; color: white; border: none; padding: 10px 28px; border-radius: 5px; font-weight: bold; cursor: pointer; font-size: 14px; }
        .btn-guardar:hover { background: #219653; }
        
        .btn-eliminar { background: #e74c3c; color: white; border: none; padding: 5px 10px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 12px; }
        .btn-eliminar:hover { background: #c0392b; }
        
        .alerta { background: #d4efdf; color: #145a32; padding: 12px; border-radius: 4px; text-align: center; margin-bottom: 20px; font-size: 14px; border: 1px solid #a9dfbf; font-weight: bold; }
        
        .badge-inv { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-block; }
        .inv-pendiente { background: #fef9e7; color: #b7950b; border: 1px solid #f9e79f; }
        .inv-enviada { background: #d6eaf8; color: #1b4f72; border: 1px solid #aed6f1; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
        th, td { border: 1px solid #bdc3c7; padding: 8px; text-align: center; vertical-align: middle; }
        th { background: #2c3e50; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }

        .modal-fondo { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.55); z-index: 999; justify-content: center; align-items: center; }
        .modal-caja { background: white; width: 430px; max-width: 92%; padding: 25px; border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); border-top: 5px solid #e74c3c; }
        .modal-caja h3 { margin-top: 0; color: #c0392b; text-align: center; }
        .modal-caja label { display: block; font-weight: bold; font-size: 13px; color: #2c3e50; margin-top: 12px; margin-bottom: 5px; }
        .modal-caja select, .modal-caja input { width: 100%; box-sizing: border-box; padding: 8px; font-size: 13px; }
        .modal-botones { display: flex; justify-content: center; gap: 10px; margin-top: 20px; }
        .btn-confirmar-baja { background: #e74c3c; color: white; border: none; padding: 9px 18px; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .btn-cancelar-baja { background: #7f8c8d; color: white; border: none; padding: 9px 18px; border-radius: 4px; font-weight: bold; cursor: pointer; }
    </style>

    <!-- JavaScript para habilitar/bloquear campos según confirmación y abrir el cuadro de baja -->
    <script>
    function toggleCamposFila(selectConfirmacion, idPart) {
        const confirmado = (selectConfirmacion.value === 'Confirmó');
        const campos = document.querySelectorAll('.campo-operativo-' + idPart);
        
        campos.forEach(campo => {
            campo.disabled = !confirmado;
            if (!confirmado) {
                campo.value = (campo.dataset.default) ? campo.dataset.default : 'Pendiente';
            }
        });
    }

    function abrirModalBaja(idParticipante, nombre) {
        document.getElementById('id_participante_eliminar').value = idParticipante;
        document.getElementById('nombreAlumnoBaja').innerText = nombre;
        document.getElementById('modalBaja').style.display = 'flex';
    }

    function cerrarModalBaja() {
        document.getElementById('modalBaja').style.display = 'none';
    }
    </script>
</head>
<body>

    <!-- Ventana emergente donde el entrenador selecciona su nombre y escribe el motivo de la baja -->
    <div id="modalBaja" class="modal-fondo">
        <div class="modal-caja">
            <h3>⚠️ Registrar Baja de Participante</h3>
            <p style="font-size: 13px; color: #566573; text-align: center;">
                Vas a dar de baja a: <b id="nombreAlumnoBaja" style="color: #2c3e50;"></b>
            </p>

            <form method="POST" action="control_asistencia.php?id_grupo=<?php echo $id_grupo_sel; ?>">
                <input type="hidden" name="eliminar_participante" value="1">
                <input type="hidden" id="id_participante_eliminar" name="id_participante_eliminar">

                <label for="entrenador_baja">¿Qué entrenador registra esta baja?</label>
                <select name="entrenador_baja" id="entrenador_baja" required>
                    <option value="">-- Selecciona tu nombre --</option>
                    <?php
                    if (!empty($info_grupo['nom1'])) echo "<option value='" . htmlspecialchars($info_grupo['nom1']) . "'>" . htmlspecialchars($info_grupo['nom1']) . " (Titular 1)</option>";
                    if (!empty($info_grupo['nom2'])) echo "<option value='" . htmlspecialchars($info_grupo['nom2']) . "'>" . htmlspecialchars($info_grupo['nom2']) . " (Titular 2)</option>";
                    if (!empty($info_grupo['nom_log'])) echo "<option value='" . htmlspecialchars($info_grupo['nom_log']) . "'>" . htmlspecialchars($info_grupo['nom_log']) . " (Apoyo Logística)</option>";
                    ?>
                    <option disabled>──────────</option>
                    <?php
                    $res_todos_ent = mysqli_query($conexion, "SELECT nombre_entrenador FROM entrenadores ORDER BY nombre_entrenador ASC");
                    while ($te = mysqli_fetch_assoc($res_todos_ent)) {
                        echo "<option value='" . htmlspecialchars($te['nombre_entrenador']) . "'>" . htmlspecialchars($te['nombre_entrenador']) . "</option>";
                    }
                    ?>
                </select>

                <label for="motivo_baja">Motivo de la baja:</label>
                <input type="text" name="motivo_baja" id="motivo_baja" required placeholder="Ej. Incapacidad médica, Cancelación de viaje, Vacaciones...">

                <div class="modal-botones">
                    <button type="submit" class="btn-confirmar-baja">Confirmar Baja</button>
                    <button type="button" class="btn-cancelar-baja" onclick="cerrarModalBaja()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="container">
        <h1>Taller de Diálogos de Calidad</h1>
        
        <div class="menu">
            <a href="index.php">← Menú Principal</a>
            <a href="mis_asignaciones.php" class="btn-volver-grupos">← Regresar a Selección de Grupo</a>
        </div>

        <h2>Módulo Entrenadores: Confirmación, Logística y Asistencia</h2>

        <?php if($mensaje != "") { echo "<div class='alerta'>$mensaje</div>"; } ?>

        <?php if($info_grupo): ?>
            <div class="banner-grupo">
                <div>
                    <h3>📋 <?php echo htmlspecialchars($info_grupo['nombre_grupo']); ?> — Sede: <?php echo htmlspecialchars($info_grupo['sede']); ?></h3>
                    <p><b>Fechas del taller:</b> <?php echo $info_grupo['fecha_inicio']; ?> al <?php echo $info_grupo['fecha_fin']; ?></p>
                </div>
                <div style="font-size: 12px; color: #1b4f72; background: white; padding: 8px 12px; border-radius: 5px; border: 1px solid #aed6f1;">
                    ℹ️ <b>Nota:</b> Solo si el participante <b>Confirmó</b> se habilitan los campos de Kick-off, Punto de Reunión y Asistencias.
                </div>
            </div>
        <?php endif; ?>

        <!-- Tabla donde paso lista de confirmación, Kick-off, punto de reunión, Día 1 y Día 2 -->
        <form method="POST" action="control_asistencia.php?id_grupo=<?php echo $id_grupo_sel; ?>">
            <table>
                <thead>
                    <tr>
                        <th>Participante</th>
                        <th>Región / Puesto</th>
                        <th>Invitación</th>
                        <th>Confirmación</th>
                        <th>Kick-off</th>
                        <th>Punto Reunión</th>
                        <th>Día 1</th>
                        <th>Día 2</th>
                        <th>Observaciones / Notas</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql_c = "SELECT p.id_participante, p.nombre_completo, p.numero_empleado, p.puesto, p.numero_centro, p.region, p.invitacion,
                                     c.confirmacion, c.asistencia_kickoff, c.punto_reunion, c.asistencia_dia1, c.asistencia_dia2, c.motivo_inasistencia
                              FROM participantes_grupo p
                              LEFT JOIN control_entrenadores c ON p.id_participante = c.id_participante
                              WHERE p.id_grupo = '$id_grupo_sel' AND p.estatus_inscripcion = 'Inscrito'
                              ORDER BY p.nombre_completo ASC";
                    
                    $res_c = mysqli_query($conexion, $sql_c);

                    if(mysqli_num_rows($res_c) > 0) {
                        while($row = mysqli_fetch_assoc($res_c)) {
                            $id_part      = $row['id_participante'];
                            $nombre       = htmlspecialchars($row['nombre_completo']);
                            $inv_val      = $row['invitacion'] ?? 'Pendiente';
                            $conf_val     = $row['confirmacion'] ?? 'Pendiente';
                            $kickoff_val  = $row['asistencia_kickoff'] ?? 'Pendiente';
                            $punto_val    = $row['punto_reunion'] ?? 'Ninguno';
                            $dia1_val     = $row['asistencia_dia1'] ?? 'Pendiente';
                            $dia2_val     = $row['asistencia_dia2'] ?? 'Pendiente';
                            $notas_val    = $row['motivo_inasistencia'] ?? '';

                            $disabled_attr = ($conf_val != 'Confirmó') ? 'disabled' : '';
                            $clase_inv     = ($inv_val == 'Enviada') ? 'inv-enviada' : 'inv-pendiente';
                            ?>
                            <tr>
                                <td style="text-align: left;">
                                    <b><?php echo $nombre; ?></b><br>
                                    <small style="color: #7f8c8d;">No. Emp: <?php echo $row['numero_empleado']; ?></small>
                                </td>
                                <td>
                                    <b><?php echo htmlspecialchars($row['region'] ?: 'N/D'); ?></b><br>
                                    <small><?php echo htmlspecialchars($row['puesto']) . " (" . htmlspecialchars($row['numero_centro']) . ")"; ?></small>
                                </td>

                                <td>
                                    <span class="badge-inv <?php echo $clase_inv; ?>">
                                        <?php echo htmlspecialchars($inv_val); ?>
                                    </span>
                                </td>

                                <td>
                                    <select name="control[<?php echo $id_part; ?>][confirmacion]" 
                                            onchange="toggleCamposFila(this, <?php echo $id_part; ?>)"
                                            style="font-weight: bold;">
                                        <option value="Pendiente" <?php if($conf_val=='Pendiente') echo 'selected'; ?>>Pendiente</option>
                                        <option value="Confirmó" <?php if($conf_val=='Confirmó') echo 'selected'; ?>>Confirmó</option>
                                        <option value="No confirmó" <?php if($conf_val=='No confirmó') echo 'selected'; ?>>No confirmó</option>
                                    </select>
                                </td>
                                
                                <td>
                                    <select name="control[<?php echo $id_part; ?>][asistencia_kickoff]" 
                                            class="campo-operativo-<?php echo $id_part; ?>" 
                                            data-default="Pendiente" <?php echo $disabled_attr; ?>>
                                        <option value="Pendiente" <?php if($kickoff_val=='Pendiente') echo 'selected'; ?>>Pendiente</option>
                                        <option value="Asistió" <?php if($kickoff_val=='Asistió') echo 'selected'; ?>>Asistió</option>
                                        <option value="No asistió" <?php if($kickoff_val=='No asistió') echo 'selected'; ?>>No asistió</option>
                                    </select>
                                </td>

                                <td>
                                    <select name="control[<?php echo $id_part; ?>][punto_reunion]" 
                                            class="campo-operativo-<?php echo $id_part; ?>" 
                                            data-default="Ninguno" <?php echo $disabled_attr; ?>>
                                        <option value="Ninguno" <?php if($punto_val=='Ninguno' || $punto_val=='') echo 'selected'; ?>>-- Seleccionar --</option>
                                        <option value="Aeropuerto CDMX" <?php if($punto_val=='Aeropuerto CDMX') echo 'selected'; ?>>Aeropuerto CDMX</option>
                                        <option value="Corporativo Coppel" <?php if($punto_val=='Corporativo Coppel') echo 'selected'; ?>>Corporativo Coppel</option>
                                    </select>
                                </td>

                                <td>
                                    <select name="control[<?php echo $id_part; ?>][asistencia_dia1]" 
                                            class="campo-operativo-<?php echo $id_part; ?>" 
                                            data-default="Pendiente" <?php echo $disabled_attr; ?>>
                                        <option value="Pendiente" <?php if($dia1_val=='Pendiente') echo 'selected'; ?>>Pendiente</option>
                                        <option value="Asistió" <?php if($dia1_val=='Asistió') echo 'selected'; ?>>Asistió</option>
                                        <option value="Falta" <?php if($dia1_val=='Falta') echo 'selected'; ?>>Falta</option>
                                    </select>
                                </td>

                                <td>
                                    <select name="control[<?php echo $id_part; ?>][asistencia_dia2]" 
                                            class="campo-operativo-<?php echo $id_part; ?>" 
                                            data-default="Pendiente" <?php echo $disabled_attr; ?>>
                                        <option value="Pendiente" <?php if($dia2_val=='Pendiente') echo 'selected'; ?>>Pendiente</option>
                                        <option value="Asistió" <?php if($dia2_val=='Asistió') echo 'selected'; ?>>Asistió</option>
                                        <option value="Falta" <?php if($dia2_val=='Falta') echo 'selected'; ?>>Falta</option>
                                    </select>
                                </td>

                                <td>
                                    <input type="text" name="control[<?php echo $id_part; ?>][motivo_inasistencia]" value="<?php echo htmlspecialchars($notas_val); ?>" placeholder="Notas / Motivo">
                                </td>

                                <td>
                                    <button type="button" class="btn-eliminar" onclick="abrirModalBaja(<?php echo $id_part; ?>, '<?php echo addslashes($nombre); ?>')">Dar de Baja</button>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo "<tr><td colspan='10'>No hay participantes inscritos en este grupo todavía.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            
            <div class="contenedor-boton">
                <button type="submit" name="guardar_control" class="btn-guardar">Guardar Cambios de Asistencia</button>
            </div>
        </form>
    </div>

</body>
</html>