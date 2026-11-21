<?php
// Aquí enlazo mi archivo de conexión para poder leer los grupos y guardar participantes en MySQL
include("conexion.php");

$mensaje = "";
$tipo_alerta = "exito";

// Esta parte se ejecuta cuando aprieto el botón verde "Inscribir Participante"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_grupo        = mysqli_real_escape_string($conexion, $_POST['id_grupo']);
    $numero_empleado = mysqli_real_escape_string($conexion, $_POST['numero_empleado']);
    $nombre_completo = mysqli_real_escape_string($conexion, $_POST['nombre_completo']);
    $puesto          = mysqli_real_escape_string($conexion, $_POST['puesto']);
    $numero_centro   = mysqli_real_escape_string($conexion, $_POST['numero_centro']);
    $region          = mysqli_real_escape_string($conexion, $_POST['region']);
    $correo          = mysqli_real_escape_string($conexion, $_POST['correo']);
    $celular         = mysqli_real_escape_string($conexion, $_POST['celular']);

    // Aquí cuento cuántos inscritos van en el grupo para respetar el límite de 26
    $sql_cupo = "SELECT COUNT(*) AS total FROM participantes_grupo 
                 WHERE id_grupo = '$id_grupo' AND estatus_inscripcion = 'Inscrito'";
    $res_cupo = mysqli_query($conexion, $sql_cupo);
    $total_inscritos = mysqli_fetch_assoc($res_cupo)['total'];

    // Aquí reviso que ese número de empleado no esté ya inscrito en el mismo grupo
    $sql_duplicado = "SELECT id_participante FROM participantes_grupo 
                      WHERE id_grupo = '$id_grupo' AND numero_empleado = '$numero_empleado' 
                      AND estatus_inscripcion = 'Inscrito'";
    $res_duplicado = mysqli_query($conexion, $sql_duplicado);

    if ($total_inscritos >= 26) {
        $mensaje = "⚠️ El grupo ya alcanzó el límite máximo de 26 participantes.";
        $tipo_alerta = "error";
    } elseif (mysqli_num_rows($res_duplicado) > 0) {
        $mensaje = "⚠️ Este número de empleado ya está inscrito en el grupo seleccionado.";
        $tipo_alerta = "error";
    } else {
        // Si hay lugar y no está repetido, lo guardo en participantes_grupo
        $sql_participante = "INSERT INTO participantes_grupo 
                             (id_grupo, numero_empleado, nombre_completo, puesto, numero_centro, region, correo, celular) 
                             VALUES 
                             ('$id_grupo', '$numero_empleado', '$nombre_completo', '$puesto', '$numero_centro', '$region', '$correo', '$celular')";

        if (mysqli_query($conexion, $sql_participante)) {
            // En cuanto se guarda el participante, le creo su fila en control_entrenadores para que le pasen lista después
            $id_participante_nuevo = mysqli_insert_id($conexion);
            mysqli_query($conexion, "INSERT INTO control_entrenadores (id_participante) VALUES ('$id_participante_nuevo')");

            $mensaje = "✅ ¡Participante inscrito correctamente! Lugares ocupados en el grupo: " . ($total_inscritos + 1) . "/26.";
            $tipo_alerta = "exito";
        } else {
            $mensaje = "❌ Error al inscribir al participante: " . mysqli_error($conexion);
            $tipo_alerta = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inscribir Participante - CCORH</title>
    <!-- Aquí defino los estilos visuales del formulario, los botones y los avisos de alerta -->
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #2c3e50; text-align: center; }
        
        .menu { margin-bottom: 25px; padding: 15px; background: #ecf0f1; border-radius: 5px; text-align: center; }
        .menu a { text-decoration: none; background: #2980b9; color: white; padding: 9px 16px; margin: 0 5px; border-radius: 4px; font-weight: bold; font-size: 13px; display: inline-block; }
        .menu a:hover { background: #34495e; }
        
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
        
        <!-- Barra superior para regresar al menú principal o ir a ver los grupos -->
        <div class="menu">
            <a href="index.php">← Menú Principal</a>
            <a href="listado_grupos.php">Ver Grupos</a>
        </div>

        <h2>Módulo CCORH: Inscripción de Participantes</h2>

        <!-- Aquí muestro el mensaje verde de éxito o rojo de error cuando se envía el formulario -->
        <?php if($mensaje != "") { 
            $clase = ($tipo_alerta == "error") ? "alerta-error" : "alerta-exito";
            echo "<div class='$clase'>$mensaje</div>"; 
        } ?>

        <!-- Formulario donde CCORH captura los datos del colaborador y selecciona su grupo y región -->
        <form action="participantes.php" method="POST">
            <h3 style="margin-top: 0; color: #2c3e50; text-align: center;">Inscribir Nuevo Colaborador a un Grupo</h3>
            
            <label for="id_grupo">Seleccione el Grupo Programado (Máximo 26 lugares):</label>
            <select id="id_grupo" name="id_grupo" required>
                <option value="">-- Seleccione Grupo y Sede --</option>
                <?php
                // Consulto los grupos disponibles y muestro cuántos lugares ocupados lleva cada uno
                $sql_grupos = "SELECT g.*, 
                               (SELECT COUNT(*) FROM participantes_grupo p 
                                WHERE p.id_grupo = g.id_grupo AND p.estatus_inscripcion = 'Inscrito') AS inscritos
                               FROM grupos g";
                $res_grupos = mysqli_query($conexion, $sql_grupos);
                while($g = mysqli_fetch_assoc($res_grupos)) {
                    $lleno = ($g['inscritos'] >= 26) ? " - [GRUPO LLENO]" : "";
                    echo "<option value='" . $g['id_grupo'] . "'>" 
                         . $g['nombre_grupo'] . " (Sede: " . $g['sede'] . " | " . $g['inscritos'] . "/26 lugares" . $lleno . ")"
                         . "</option>";
                }
                ?>
            </select>

            <label for="numero_empleado">Número de Empleado:</label>
            <input type="text" id="numero_empleado" name="numero_empleado" required placeholder="Ej. 96766026">

            <label for="nombre_completo">Nombre Completo del Colaborador:</label>
            <input type="text" id="nombre_completo" name="nombre_completo" required placeholder="Ej. Juan Pérez López">

            <label for="puesto">Puesto:</label>
            <input type="text" id="puesto" name="puesto" placeholder="Ej. Gerente de Tienda">

            <label for="numero_centro">Número de Centro:</label>
            <input type="text" id="numero_centro" name="numero_centro" placeholder="Ej. 240552">

            <label for="region">Región:</label>
            <select name="region" required>
                <option value="">-- Selecciona Región --</option>
                <option value="AZCP">AZCP</option>
                <option value="CDJZ">CDJZ</option>
                <option value="CTLZ">CTLZ</option>
                <option value="CLCN">CLCN</option>
                <option value="GDLJ I">GDLJ I</option>
                <option value="GDLJ II">GDLJ II</option>
                <option value="HLLO">HLLO</option>
                <option value="IXTP">IXTP</option>
                <option value="IZTP">IZTP</option>
                <option value="LEON">LEON</option>
                <option value="LEON II">LEON II</option>
                <option value="LMCH">LMCH</option>
                <option value="MRDA">MRDA</option>
                <option value="MTRY I">MTRY I</option>
                <option value="MTRY II">MTRY II</option>
                <option value="MXLI">MXLI</option>
                <option value="OAXC">OAXC</option>
                <option value="PBLA">PBLA</option>
                <option value="PBLA II">PBLA II</option>
                <option value="SNLP">SNLP</option>
                <option value="TCMC">TCMC</option>
                <option value="TOLC">TOLC</option>
                <option value="TRRN">TRRN</option>
                <option value="TXCC">TXCC</option>
                <option value="VLLH">VLLH</option>
                <option value="VRCZ">VRCZ</option>
            </select>

            <label for="correo">Correo Electrónico:</label>
            <input type="email" id="correo" name="correo" placeholder="correo@coppel.com">

            <label for="celular">Celular:</label>
            <input type="text" id="celular" name="celular" placeholder="3312345678">

            <div class="contenedor-boton">
                <button type="submit">Inscribir Participante</button>
            </div>
        </form>
    </div>

</body>
</html>