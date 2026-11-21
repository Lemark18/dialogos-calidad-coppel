<?php
// Enlazo mi conexión a la base de datos y valido las reglas antes de crear un grupo nuevo
include("conexion.php");

$mensaje = "";
$tipo_alerta = "exito";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_grupo = mysqli_real_escape_string($conexion, trim($_POST['nombre_grupo']));
    $sede         = mysqli_real_escape_string($conexion, $_POST['sede']);
    $fecha_inicio = mysqli_real_escape_string($conexion, $_POST['fecha_inicio']);
    $fecha_fin    = mysqli_real_escape_string($conexion, $_POST['fecha_fin']);
    $id_e1        = intval($_POST['id_entrenador_1']);
    $id_e2        = intval($_POST['id_entrenador_2']);
    $id_el        = intval($_POST['id_entrenador_logistica']);

    // Reviso que no exista ya otro grupo con el mismo nombre en la base de datos
    $sql_dup_grupo = "SELECT id_grupo FROM grupos WHERE LOWER(TRIM(nombre_grupo)) = LOWER('$nombre_grupo')";
    $res_dup_grupo = mysqli_query($conexion, $sql_dup_grupo);

    if (mysqli_num_rows($res_dup_grupo) > 0) {
        $mensaje = "⚠️ Error: Ya existe un grupo registrado con el nombre '$nombre_grupo'. Elige otro número o nombre.";
        $tipo_alerta = "error";
    } elseif ($fecha_fin < $fecha_inicio) {
        // Reviso que la fecha de fin no quede antes de la fecha de inicio
        $mensaje = "⚠️ Error: La fecha de fin no puede ser anterior a la fecha de inicio.";
        $tipo_alerta = "error";
    } elseif ($id_e1 == $id_e2 || $id_e1 == $id_el || $id_e2 == $id_el) {
        // Reviso que los 3 puestos (Titular 1, Titular 2 y Logística) sean personas diferentes
        $mensaje = "⚠️ Error: No puedes asignar al mismo entrenador en dos roles distintos para el mismo grupo.";
        $tipo_alerta = "error";
    } else {
        // Reviso que ninguno de los 3 entrenadores tenga ya otro taller asignado en esas mismas fechas
        $sql_cruce = "SELECT nombre_grupo FROM grupos WHERE 
                      (
                        ('$fecha_inicio' BETWEEN fecha_inicio AND fecha_fin) OR 
                        ('$fecha_fin' BETWEEN fecha_inicio AND fecha_fin) OR 
                        (fecha_inicio BETWEEN '$fecha_inicio' AND '$fecha_fin')
                      ) AND (
                        id_entrenador_1 IN ('$id_e1', '$id_e2', '$id_el') OR 
                        id_entrenador_2 IN ('$id_e1', '$id_e2', '$id_el') OR 
                        id_entrenador_logistica IN ('$id_e1', '$id_e2', '$id_el')
                      )";
        
        $res_cruce = mysqli_query($conexion, $sql_cruce);

        if (mysqli_num_rows($res_cruce) > 0) {
            $g_cruce = mysqli_fetch_assoc($res_cruce)['nombre_grupo'];
            $mensaje = "⚠️ Error: Uno o más facilitadores seleccionados ya están asignados en '$g_cruce' durante esas fechas.";
            $tipo_alerta = "error";
        } else {
            // Si pasa todas las validaciones, guardo el grupo nuevo en MySQL
            $sql_insert = "INSERT INTO grupos (nombre_grupo, sede, fecha_inicio, fecha_fin, id_entrenador_1, id_entrenador_2, id_entrenador_logistica) 
                           VALUES ('$nombre_grupo', '$sede', '$fecha_inicio', '$fecha_fin', '$id_e1', '$id_e2', '$id_el')";
            
            if (mysqli_query($conexion, $sql_insert)) {
                $mensaje = "✅ ¡Grupo '$nombre_grupo' programado y entrenadores asignados con éxito!";
                $tipo_alerta = "exito";
            } else {
                $mensaje = "❌ Error al guardar el grupo: " . mysqli_error($conexion);
                $tipo_alerta = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Programar Nuevo Grupo - Logística</title>
    <!-- Estilos visuales del formulario para programar grupos nuevos -->
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 1180px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #2c3e50; text-align: center; }
        
        .menu { margin-bottom: 25px; padding: 15px; background: #ecf0f1; border-radius: 5px; text-align: center; }
        .menu a { text-decoration: none; background: #2980b9; color: white; padding: 9px 16px; margin: 0 5px; border-radius: 4px; font-weight: bold; font-size: 13px; display: inline-block; }
        .menu a:hover { background: #34495e; }
        .menu a.btn-volver-grupos { background: #34495e; }

        form { max-width: 750px; margin: 0 auto; display: flex; flex-direction: column; background: #f8f9fa; padding: 25px; border-radius: 8px; border: 2px solid #d5dbdb; }
        label { margin-top: 12px; font-weight: bold; color: #34495e; font-size: 13px; }
        input, select { padding: 9px; margin-top: 5px; border: 1px solid #bdc3c7; border-radius: 4px; font-size: 14px; background: white; }
        
        .contenedor-boton { text-align: center; margin-top: 22px; }
        button { background: #27ae60; color: white; padding: 10px 28px; border: none; border-radius: 5px; font-size: 14px; font-weight: bold; cursor: pointer; display: inline-block; }
        button:hover { background: #219653; }
        
        .alerta-exito { max-width: 750px; margin: 0 auto 20px auto; background: #d4efdf; color: #145a32; padding: 12px; border-radius: 4px; text-align: center; font-size: 14px; border: 1px solid #a9dfbf; font-weight: bold; }
        .alerta-error { max-width: 750px; margin: 0 auto 20px auto; background: #fadbd8; color: #78281f; padding: 12px; border-radius: 4px; text-align: center; font-size: 14px; border: 1px solid #f5b7b1; font-weight: bold; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Taller de Diálogos de Calidad</h1>
        
        <div class="menu">
            <a href="index.php">← Menú Principal</a>
            <a href="grupos.php" class="btn-volver-grupos">← Ver Grupos</a>
            <a href="cronograma_visual.php">📅 Cronograma Visual</a>
        </div>

                <h2>Programar Nuevo Grupo</h2>

        <?php 
        if($mensaje != "") { 
            $clase_alerta = ($tipo_alerta == 'exito') ? 'alerta-exito' : 'alerta-error';
            echo "<div class='$clase_alerta'>$mensaje</div>"; 
        } 
        ?>

        <!-- Formulario donde Logística captura el nombre del grupo, sede, fechas y los 3 entrenadores -->
        <form action="nuevo_grupo.php" method="POST">
            <h3 style="margin-top: 0; color: #2c3e50; text-align: center;">Datos del Grupo y Asignación de Entrenadores</h3>

            <label for="nombre_grupo">Nombre del Grupo (Debe ser único):</label>
            <input type="text" id="nombre_grupo" name="nombre_grupo" required placeholder="Escribir nombre del grupo">

            <label for="sede">Sede del Taller:</label>
            <select id="sede" name="sede" required>
                <option value="">-- Seleccionar Sede --</option>
                <option value="Hotel Avándaro">Hotel Avándaro</option>
                <option value="Rancho San Diego">Rancho San Diego</option>
            </select>

            <label for="fecha_inicio">Fecha de Inicio:</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" required>

            <label for="fecha_fin">Fecha de Fin:</label>
            <input type="date" id="fecha_fin" name="fecha_fin" required>

            <label for="id_entrenador_1">Entrenador Titular 1:</label>
            <select id="id_entrenador_1" name="id_entrenador_1" required>
                <option value="">-- Seleccionar Entrenador Titular 1 --</option>
                <?php
                $res_e1 = mysqli_query($conexion, "SELECT * FROM entrenadores ORDER BY nombre_entrenador ASC");
                while($e = mysqli_fetch_assoc($res_e1)) {
                    echo "<option value='" . $e['id_entrenador'] . "'>" . htmlspecialchars($e['nombre_entrenador']) . "</option>";
                }
                ?>
            </select>

            <label for="id_entrenador_2">Entrenador Titular 2:</label>
            <select id="id_entrenador_2" name="id_entrenador_2" required>
                <option value="">-- Seleccionar Entrenador Titular 2 --</option>
                <?php
                $res_e2 = mysqli_query($conexion, "SELECT * FROM entrenadores ORDER BY nombre_entrenador ASC");
                while($e = mysqli_fetch_assoc($res_e2)) {
                    echo "<option value='" . $e['id_entrenador'] . "'>" . htmlspecialchars($e['nombre_entrenador']) . "</option>";
                }
                ?>
            </select>

            <label for="id_entrenador_logistica">Apoyo (Logística):</label>
            <select id="id_entrenador_logistica" name="id_entrenador_logistica" required>
                <option value="">-- Seleccionar Apoyo Logística --</option>
                <?php
                $res_e3 = mysqli_query($conexion, "SELECT * FROM entrenadores ORDER BY nombre_entrenador ASC");
                while($e = mysqli_fetch_assoc($res_e3)) {
                    echo "<option value='" . $e['id_entrenador'] . "'>" . htmlspecialchars($e['nombre_entrenador']) . "</option>";
                }
                ?>
            </select>

            <div class="contenedor-boton">
                <button type="submit">Crear y Guardar Grupo</button>
            </div>
        </form>
    </div>

</body>
</html>