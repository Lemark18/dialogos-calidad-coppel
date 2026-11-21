<?php
// Enlazo mi conexión a la base de datos
include("conexion.php");

$mensaje = "";

// Recibo por la URL el ID del grupo que voy a editar; si no viene, regreso a la lista
$id_grupo = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_grupo == 0) {
    header("Location: grupos.php");
    exit();
}

// Esta parte se ejecuta cuando aprieto el botón verde "Actualizar Cambios del Grupo"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_grupo = mysqli_real_escape_string($conexion, $_POST['nombre_grupo']);
    $sede = mysqli_real_escape_string($conexion, $_POST['sede']);
    $fecha_inicio = mysqli_real_escape_string($conexion, $_POST['fecha_inicio']);
    $fecha_fin = mysqli_real_escape_string($conexion, $_POST['fecha_fin']);
    $id_e1 = intval($_POST['id_entrenador_1']);
    $id_e2 = intval($_POST['id_entrenador_2']);
    $id_el = intval($_POST['id_entrenador_logistica']);

    // Reviso que no pongan a la misma persona en dos puestos distintos del mismo grupo
    if ($id_e1 == $id_e2 || $id_e1 == $id_el || $id_e2 == $id_el) {
        $mensaje = "⚠️ Error: No puedes asignar al mismo entrenador en dos roles distintos para el mismo grupo.";
    } else {
        // Reviso en la base de datos que ninguno de los 3 entrenadores tenga otro taller en esas fechas
        $sql_cruce = "SELECT * FROM grupos WHERE id_grupo != '$id_grupo' AND (
                      (
                        ('$fecha_inicio' BETWEEN fecha_inicio AND fecha_fin) OR 
                        ('$fecha_fin' BETWEEN fecha_inicio AND fecha_fin) OR 
                        (fecha_inicio BETWEEN '$fecha_inicio' AND '$fecha_fin')
                      ) AND (
                        id_entrenador_1 IN ('$id_e1', '$id_e2', '$id_el') OR 
                        id_entrenador_2 IN ('$id_e1', '$id_e2', '$id_el') OR 
                        id_entrenador_logistica IN ('$id_e1', '$id_e2', '$id_el')
                      )
                    )";
        
        $res_cruce = mysqli_query($conexion, $sql_cruce);

        if ($res_cruce && mysqli_num_rows($res_cruce) > 0) {
            $mensaje = "⚠️ Error: Uno o más entrenadores seleccionados ya tienen asignado otro grupo en estas mismas fechas (Cruce de agenda).";
        } else {
            // Si todo está libre, guardo los cambios del grupo en MySQL
            $sql_update = "UPDATE grupos SET 
                           nombre_grupo = '$nombre_grupo', 
                           sede = '$sede', 
                           fecha_inicio = '$fecha_inicio', 
                           fecha_fin = '$fecha_fin', 
                           id_entrenador_1 = '$id_e1', 
                           id_entrenador_2 = '$id_e2', 
                           id_entrenador_logistica = '$id_el' 
                           WHERE id_grupo = '$id_grupo'";
            
            if (mysqli_query($conexion, $sql_update)) {
                header("Location: grupos.php?id_grupo=$id_grupo&actualizado=1");
                exit();
            } else {
                $mensaje = "Error al actualizar el grupo: " . mysqli_error($conexion);
            }
        }
    }
}

// Consulto los datos actuales del grupo para rellenar el formulario
$sql_datos = "SELECT * FROM grupos WHERE id_grupo = '$id_grupo'";
$res_datos = mysqli_query($conexion, $sql_datos);
$grupo = mysqli_fetch_assoc($res_datos);

if (!$grupo) {
    header("Location: grupos.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Grupo - Logística</title>
    <!-- Estilos visuales del formulario para editar el grupo -->
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #2c3e50; text-align: center; }
        
        .menu { 
            margin-bottom: 25px; 
            padding: 15px; 
            background: #ecf0f1; 
            border-radius: 6px; 
            text-align: center; 
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .menu a { 
            text-decoration: none; 
            background: #2980b9; 
            color: white; 
            padding: 10px 18px; 
            border-radius: 4px; 
            font-weight: bold; 
            font-size: 13px;
            display: inline-block;
        }
        .menu a.btn-regresar { background: #7f8c8d; }
        .menu a:hover { opacity: 0.9; }

        form { display: flex; flex-direction: column; background: #fafafa; padding: 20px; border-radius: 6px; border: 1px solid #e1e8ed; }
        label { margin-top: 12px; font-weight: bold; color: #34495e; font-size: 13px; }
        input, select { padding: 9px; margin-top: 5px; border: 1px solid #bdc3c7; border-radius: 4px; font-size: 14px; }
        
        button { 
            margin-top: 25px; 
            background: #27ae60; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 4px; 
            font-size: 13px; 
            font-weight: bold; 
            cursor: pointer; 
            display: inline-block;
            width: auto;
            align-self: flex-start;
        }
        button:hover { background: #219653; }

        .alerta { background: #fadbd8; color: #78281f; padding: 12px; border-radius: 4px; text-align: center; margin-bottom: 20px; font-size: 14px; border: 1px solid #f5b7b1; }
        .exito { background: #d4efdf; color: #145a32; padding: 12px; border-radius: 4px; text-align: center; margin-bottom: 20px; font-size: 14px; border: 1px solid #a9dfbf; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Taller de Diálogos de Calidad</h1>
        
        <div class="menu">
            <a href="grupos.php?id_grupo=<?php echo $id_grupo; ?>" class="btn-regresar">← Volver al Grupo</a>
            <a href="grupos.php">📋 Grupos</a>
        </div>

        <h2>Modificar Información del Grupo</h2>

        <?php 
        if($mensaje != "") { 
            echo "<div class='alerta'>$mensaje</div>"; 
        } 
        ?>

        <!-- Formulario precargado con los datos actuales del taller para poder cambiar fechas, sede o entrenadores -->
        <form action="editar_grupo.php?id=<?php echo $id_grupo; ?>" method="POST">
            <input type="hidden" name="id" value="<?php echo $id_grupo; ?>">

            <label for="nombre_grupo">Nombre del Grupo:</label>
            <input type="text" id="nombre_grupo" name="nombre_grupo" value="<?php echo htmlspecialchars($grupo['nombre_grupo']); ?>" required>

            <label for="sede">Sede del Taller:</label>
            <select id="sede" name="sede" required>
                <option value="">-- Seleccionar Sede --</option>
                <option value="Hotel Avándaro" <?php if($grupo['sede'] == 'Hotel Avándaro') echo 'selected'; ?>>Hotel Avándaro</option>
                <option value="Rancho San Diego" <?php if($grupo['sede'] == 'Rancho San Diego') echo 'selected'; ?>>Rancho San Diego</option>
            </select>

            <label for="fecha_inicio">Fecha de Inicio:</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo $grupo['fecha_inicio']; ?>" required>

            <label for="fecha_fin">Fecha de Fin:</label>
            <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo $grupo['fecha_fin']; ?>" required>

            <label for="id_entrenador_1">Entrenador Titular 1:</label>
            <select id="id_entrenador_1" name="id_entrenador_1" required>
                <option value="">-- Seleccionar Entrenador Titular 1 --</option>
                <?php
                $res_e1 = mysqli_query($conexion, "SELECT * FROM entrenadores ORDER BY nombre_entrenador ASC");
                while($e = mysqli_fetch_assoc($res_e1)) {
                    $selected = ($e['id_entrenador'] == $grupo['id_entrenador_1']) ? 'selected' : '';
                    echo "<option value='" . $e['id_entrenador'] . "' $selected>" . htmlspecialchars($e['nombre_entrenador']) . "</option>";
                }
                ?>
            </select>

            <label for="id_entrenador_2">Entrenador Titular 2:</label>
            <select id="id_entrenador_2" name="id_entrenador_2" required>
                <option value="">-- Seleccionar Entrenador Titular 2 --</option>
                <?php
                $res_e2 = mysqli_query($conexion, "SELECT * FROM entrenadores ORDER BY nombre_entrenador ASC");
                while($e = mysqli_fetch_assoc($res_e2)) {
                    $selected = ($e['id_entrenador'] == $grupo['id_entrenador_2']) ? 'selected' : '';
                    echo "<option value='" . $e['id_entrenador'] . "' $selected>" . htmlspecialchars($e['nombre_entrenador']) . "</option>";
                }
                ?>
            </select>

            <label for="id_entrenador_logistica">Apoyo (Logística):</label>
            <select id="id_entrenador_logistica" name="id_entrenador_logistica" required>
                <option value="">-- Seleccionar Apoyo Logística --</option>
                <?php
                $res_e3 = mysqli_query($conexion, "SELECT * FROM entrenadores ORDER BY nombre_entrenador ASC");
                while($e = mysqli_fetch_assoc($res_e3)) {
                    $selected = ($e['id_entrenador'] == $grupo['id_entrenador_logistica']) ? 'selected' : '';
                    echo "<option value='" . $e['id_entrenador'] . "' $selected>" . htmlspecialchars($e['nombre_entrenador']) . "</option>";
                }
                ?>
            </select>

            <button type="submit">Actualizar Cambios del Grupo</button>
        </form>
    </div>

</body>
</html>