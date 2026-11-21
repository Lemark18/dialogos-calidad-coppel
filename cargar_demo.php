<?php
// =========================================================================
// SCRIPT TEMPORAL DE CARGA PARA SIMULACIÓN Y PRUEBAS (2 GRUPOS)
// Grupo Demo 1: 26/26 participantes (Lleno - ideal para probar bloqueo y reportería)
// Grupo Demo 2: 14/26 participantes (A medias - ideal para altas manuales y reasignación)
// =========================================================================
include("conexion.php");

$mensaje = "";

// -------------------------------------------------------------------------
// OPCIÓN A: GENERAR LOS 2 GRUPOS DE DEMOSTRACIÓN
// -------------------------------------------------------------------------
if (isset($_POST['generar_datos'])) {
    // 1. Obtener IDs de entrenadores disponibles en la BD
    $res_ent = mysqli_query($conexion, "SELECT id_entrenador FROM entrenadores ORDER BY id_entrenador ASC");
    $ids_ent = [];
    while ($e = mysqli_fetch_assoc($res_ent)) {
        $ids_ent[] = $e['id_entrenador'];
    }

    // Si hay menos de 6 entrenadores registrados, creamos 6 de prueba
    if (count($ids_ent) < 6) {
        $nombres_ent_demo = [
            'Luis Covarrubias', 'Ana Sofía Mendoza', 'Carlos Eduardo Vega',
            'Fernanda Ríos', 'Jorge Alberto Salgado', 'Patricia Morales'
        ];
        foreach ($nombres_ent_demo as $ne) {
            mysqli_query($conexion, "INSERT INTO entrenadores (nombre_entrenador) VALUES ('$ne')");
        }
        $res_ent = mysqli_query($conexion, "SELECT id_entrenador FROM entrenadores ORDER BY id_entrenador ASC");
        $ids_ent = [];
        while ($e = mysqli_fetch_assoc($res_ent)) {
            $ids_ent[] = $e['id_entrenador'];
        }
    }

    $total_e = count($ids_ent);

    // 2. Definir los 2 grupos de demostración: [Nombre, Sede, Inicio, Fin, Ent1, Ent2, Log, CantidadParticipantes]
    $grupos_demo = [
        ['Grupo Demo 1', 'Hotel Avándaro',   '2027-02-01', '2027-02-05', $ids_ent[0 % $total_e], $ids_ent[1 % $total_e], $ids_ent[2 % $total_e], 26],
        ['Grupo Demo 2', 'Rancho San Diego', '2027-02-08', '2027-02-12', $ids_ent[3 % $total_e], $ids_ent[4 % $total_e], $ids_ent[5 % $total_e], 14],
    ];

    // Catálogos para armar nombres y datos realistas (26 nombres distintos)
    $nombres = ['Alejandro', 'Mariana', 'Roberto', 'Daniela', 'Fernando', 'Valeria', 'Ricardo', 'Gabriela', 'Eduardo', 'Sofía', 'Héctor', 'Paola', 'Arturo', 'Andrea', 'Oscar', 'Karla', 'Sergio', 'Natalia', 'Miguel', 'Lucía', 'Raúl', 'Claudia', 'Javier', 'Mónica', 'Adrián', 'Beatriz'];
    $apellidos = ['Hernández', 'García', 'Martínez', 'López', 'González', 'Pérez', 'Rodríguez', 'Sánchez', 'Ramírez', 'Cruz', 'Flores', 'Gómez', 'Morales', 'Vázquez', 'Jiménez', 'Reyes', 'Díaz', 'Torres', 'Gutiérrez', 'Ruiz', 'Mendoza', 'Aguilar', 'Ortiz', 'Castillo', 'Moreno', 'Salazar'];
    $regiones = ['CULN', 'GDLJ', 'CDMX', 'MTYY', 'TIJN', 'SNLP', 'PBLA', 'MRDA', 'LEON', 'VRCR'];
    $puestos = ['Gerente de Zona', 'Gerente Regional', 'Jefe de Operaciones', 'Coordinador de Tienda', 'Líder de Proyecto'];

    $grupos_creados = 0;
    $participantes_creados = 0;

    foreach ($grupos_demo as $idx_g => $gd) {
        $nom_g       = $gd[0];
        $sede        = $gd[1];
        $f_ini       = $gd[2];
        $f_fin       = $gd[3];
        $t1          = $gd[4];
        $t2          = $gd[5];
        $log         = $gd[6];
        $cant_part   = $gd[7];

        // Evitar duplicar si ya se corrió antes
        $existe = mysqli_query($conexion, "SELECT id_grupo FROM grupos WHERE nombre_grupo = '$nom_g'");
        if (mysqli_num_rows($existe) > 0) continue;

        $sql_ins_g = "INSERT INTO grupos (nombre_grupo, sede, fecha_inicio, fecha_fin, id_entrenador_1, id_entrenador_2, id_entrenador_logistica)
                      VALUES ('$nom_g', '$sede', '$f_ini', '$f_fin', $t1, $t2, $log)";
        
        if (mysqli_query($conexion, $sql_ins_g)) {
            $id_grupo_nuevo = mysqli_insert_id($conexion);
            $grupos_creados++;

            for ($i = 0; $i < $cant_part; $i++) {
                $nom_completo = $nombres[$i] . " " . $apellidos[($i + $idx_g * 3) % 26] . " " . $apellidos[($i * 2 + 5) % 26];
                $num_emp      = 90000000 + ($idx_g * 100) + $i + 1;
                $region       = $regiones[($i + $idx_g) % count($regiones)];
                $puesto       = $puestos[$i % count($puestos)];
                $centro       = 760000 + ($idx_g * 100) + $i;
                $correo       = strtolower(str_replace(' ', '.', $nombres[$i])) . "." . ($idx_g + 1) . ($i + 1) . "@coppel.com";
                $celular      = "667" . str_pad(($idx_g * 1000000 + $i * 12345) % 9999999, 7, "0", STR_PAD_LEFT);

                // Grupo 1 (lleno): todos con invitación enviada. Grupo 2 (a medias): mitad enviada, mitad pendiente
                if ($idx_g === 0) {
                    $invitacion = 'Enviada';
                } else {
                    $invitacion = ($i < 7) ? 'Enviada' : 'Pendiente';
                }

                $sql_ins_p = "INSERT INTO participantes_grupo 
                              (id_grupo, numero_empleado, nombre_completo, puesto, numero_centro, region, correo, celular, estatus_inscripcion, invitacion)
                              VALUES 
                              ($id_grupo_nuevo, '$num_emp', '$nom_completo', '$puesto', '$centro', '$region', '$correo', '$celular', 'Inscrito', '$invitacion')";
                
                if (mysqli_query($conexion, $sql_ins_p)) {
                    $id_part_nuevo = mysqli_insert_id($conexion);
                    $participantes_creados++;

                    if ($idx_g === 0) {
                        // Grupo Demo 1 (26/26): datos completos de confirmación y asistencia para lucir en Reportería
                        $punto   = ($i % 2 == 0) ? 'Aeropuerto CDMX' : 'Corporativo Coppel';
                        $kickoff = ($i < 24) ? 'Asistió' : 'No asistió';
                        $dia1    = ($i < 23) ? 'Asistió' : 'Pendiente';
                        $dia2    = ($i < 22) ? 'Asistió' : 'Pendiente';
                        $motivo  = ($i >= 24) ? 'Vuelo demorado por clima' : '';

                        mysqli_query($conexion, "INSERT INTO control_entrenadores 
                            (id_participante, confirmacion, asistencia_kickoff, punto_reunion, asistencia_dia1, asistencia_dia2, motivo_inasistencia)
                            VALUES ($id_part_nuevo, 'Confirmó', '$kickoff', '$punto', '$dia1', '$dia2', '$motivo')");
                    } else {
                        // Grupo Demo 2 (14/26): avance parcial
                        if ($i < 7) {
                            $punto = ($i % 2 == 0) ? 'Aeropuerto CDMX' : 'Corporativo Coppel';
                            mysqli_query($conexion, "INSERT INTO control_entrenadores 
                                (id_participante, confirmacion, asistencia_kickoff, punto_reunion, asistencia_dia1, asistencia_dia2, motivo_inasistencia)
                                VALUES ($id_part_nuevo, 'Confirmó', 'Pendiente', '$punto', 'Pendiente', 'Pendiente', '')");
                        } else {
                            mysqli_query($conexion, "INSERT INTO control_entrenadores 
                                (id_participante, confirmacion, asistencia_kickoff, punto_reunion, asistencia_dia1, asistencia_dia2, motivo_inasistencia)
                                VALUES ($id_part_nuevo, 'Pendiente', 'Pendiente', 'Ninguno', 'Pendiente', 'Pendiente', '')");
                        }
                    }
                }
            }
        }
    }

    if ($grupos_creados > 0) {
        $mensaje = "✅ ¡Listo! Se crearon <b>$grupos_creados grupos demo</b> con <b>$participantes_creados participantes</b> (Grupo Demo 1 lleno al 26/26 y Grupo Demo 2 a medias con 14/26).";
    } else {
        $mensaje = "ℹ️ Los grupos de demostración ya estaban cargados en la base de datos.";
    }
}

// -------------------------------------------------------------------------
// OPCIÓN B: ELIMINAR TODOS LOS DATOS DEMO
// -------------------------------------------------------------------------
if (isset($_POST['limpiar_datos'])) {
    $res_gd = mysqli_query($conexion, "SELECT id_grupo FROM grupos WHERE nombre_grupo LIKE 'Grupo Demo %'");
    while ($g = mysqli_fetch_assoc($res_gd)) {
        $idg = $g['id_grupo'];
        mysqli_query($conexion, "DELETE c FROM control_entrenadores c JOIN participantes_grupo p ON c.id_participante = p.id_participante WHERE p.id_grupo = $idg");
        mysqli_query($conexion, "DELETE FROM participantes_grupo WHERE id_grupo = $idg");
        mysqli_query($conexion, "DELETE FROM bajas_participantes WHERE id_grupo = $idg OR id_grupo_destino = $idg");
        mysqli_query($conexion, "DELETE FROM grupos WHERE id_grupo = $idg");
    }
    $mensaje = "🧹 Se eliminaron correctamente todos los grupos y participantes de demostración.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generador de Datos de Prueba - Diálogos de Calidad</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 30px; }
        .container { max-width: 750px; margin: 0 auto; background: white; padding: 35px; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.1); text-align: center; }
        h1 { color: #2c3e50; margin-top: 0; }
        p { color: #566573; font-size: 14px; line-height: 1.6; }
        .alerta { background: #d4efdf; color: #145a32; padding: 14px; border-radius: 5px; margin: 20px 0; font-weight: bold; border: 1px solid #a9dfbf; }
        .botones { display: flex; justify-content: center; gap: 15px; margin-top: 25px; flex-wrap: wrap; }
        .btn-generar { background: #27ae60; color: white; border: none; padding: 12px 24px; border-radius: 5px; font-weight: bold; font-size: 14px; cursor: pointer; }
        .btn-generar:hover { background: #219653; }
        .btn-limpiar { background: #e74c3c; color: white; border: none; padding: 12px 24px; border-radius: 5px; font-weight: bold; font-size: 14px; cursor: pointer; }
        .btn-limpiar:hover { background: #c0392b; }
        .btn-inicio { display: inline-block; margin-top: 25px; text-decoration: none; background: #2980b9; color: white; padding: 10px 20px; border-radius: 5px; font-weight: bold; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛠️ Generador de Datos de Simulación</h1>
        <p>
            Esta utilidad crea <b>2 grupos de prueba</b> para tu presentación:<br>
            • <code>Grupo Demo 1</code> (Hotel Avándaro): <b>26/26 participantes (Lleno)</b> con asistencias cargadas para reportería.<br>
            • <code>Grupo Demo 2</code> (Rancho San Diego): <b>14/26 participantes (A medias)</b> con 12 cupos disponibles para altas y reasignaciones.
        </p>

        <?php if ($mensaje != ""): ?>
            <div class="alerta"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <form method="POST" class="botones">
            <button type="submit" name="generar_datos" class="btn-generar">🚀 Generar 2 Grupos Demo (40 Participantes)</button>
            <button type="submit" name="limpiar_datos" class="btn-limpiar" onclick="return confirm('¿Seguro que deseas borrar los Grupos Demo y sus participantes?');">🧹 Borrar Datos Demo</button>
        </form>

        <a href="index.php" class="btn-inicio">← Ir al Menú Principal del Sistema</a>
    </div>
</body>
</html>