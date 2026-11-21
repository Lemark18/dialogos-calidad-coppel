<?php
// Enlazo mi conexión a la base de datos y recibo qué grupo o vista pidió abrir el usuario
include("conexion.php");

$id_grupo_sel = isset($_GET['id_grupo']) ? mysqli_real_escape_string($conexion, $_GET['id_grupo']) : '';
$ver_alertas  = isset($_GET['alertas']) ? true : false;
$id_editar    = isset($_GET['editar']) ? mysqli_real_escape_string($conexion, $_GET['editar']) : '';
$mensaje      = "";
$tipo_alerta  = "exito";

// Acción 1: Aquí guardo los cambios cuando CCORH edita los datos de un participante
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'editar') {
    $id_part         = mysqli_real_escape_string($conexion, $_POST['id_participante']);
    $id_grupo_post   = mysqli_real_escape_string($conexion, $_POST['id_grupo']);
    $numero_empleado = mysqli_real_escape_string($conexion, $_POST['numero_empleado']);
    $nombre_completo = mysqli_real_escape_string($conexion, $_POST['nombre_completo']);
    $puesto          = mysqli_real_escape_string($conexion, $_POST['puesto']);
    $numero_centro   = mysqli_real_escape_string($conexion, $_POST['numero_centro']);
    $region          = mysqli_real_escape_string($conexion, $_POST['region']);
    $correo          = mysqli_real_escape_string($conexion, $_POST['correo']);
    $celular         = mysqli_real_escape_string($conexion, $_POST['celular']);

    $sql_upd = "UPDATE participantes_grupo SET 
                numero_empleado = '$numero_empleado',
                nombre_completo = '$nombre_completo',
                puesto          = '$puesto',
                numero_centro   = '$numero_centro',
                region          = '$region',
                correo          = '$correo',
                celular         = '$celular'
                WHERE id_participante = '$id_part'";

    if (mysqli_query($conexion, $sql_upd)) {
        $mensaje = "✅ Datos del participante actualizados correctamente.";
        $id_grupo_sel = $id_grupo_post;
    }
}

// Acción 2: Cuando dan de baja a alguien, primero respaldo sus datos en bajas_participantes (formato unificado) y luego libero su lugar
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'baja') {
    $id_part          = mysqli_real_escape_string($conexion, $_POST['id_participante']);
    $id_grupo_post    = mysqli_real_escape_string($conexion, $_POST['id_grupo']);
    $responsable_baja = trim($_POST['responsable_baja']);
    $motivo_raw       = trim($_POST['motivo_baja']);

    $motivo_completo = ($responsable_baja != '') 
        ? $motivo_raw . " (Reportado por: " . $responsable_baja . ")" 
        : $motivo_raw;

    $motivo_baja = mysqli_real_escape_string($conexion, $motivo_completo);

    $res_p = mysqli_query($conexion, "SELECT * FROM participantes_grupo WHERE id_participante = '$id_part'");
    if ($datos_p = mysqli_fetch_assoc($res_p)) {
        $nom = mysqli_real_escape_string($conexion, $datos_p['nombre_completo']);
        $emp = mysqli_real_escape_string($conexion, $datos_p['numero_empleado']);
        $pue = mysqli_real_escape_string($conexion, $datos_p['puesto']);
        $cen = mysqli_real_escape_string($conexion, $datos_p['numero_centro']);
        $reg = mysqli_real_escape_string($conexion, $datos_p['region']);
        $cor = mysqli_real_escape_string($conexion, $datos_p['correo']);
        $cel = mysqli_real_escape_string($conexion, $datos_p['celular']);

        $sql_hist = "INSERT INTO bajas_participantes 
                     (nombre_completo, numero_empleado, puesto, numero_centro, id_grupo, eliminado_por, motivo_baja, region, correo, celular, estatus_baja)
                     VALUES 
                     ('$nom', '$emp', '$pue', '$cen', '$id_grupo_post', 'CCORH', '$motivo_baja', '$reg', '$cor', '$cel', 'Pendiente')";
        mysqli_query($conexion, $sql_hist);

        mysqli_query($conexion, "DELETE FROM participantes_grupo WHERE id_participante = '$id_part'");

        $mensaje = "⚠️ Participante dado de baja con respaldo completo. El espacio quedó liberado.";
        $id_grupo_sel = $id_grupo_post;
    }
}

// Acción 3: Reasigno a un colaborador dado de baja hacia un grupo futuro y conservo el historial de migración
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'reasignar') {
    $id_baja         = mysqli_real_escape_string($conexion, $_POST['id_baja']);
    $id_grupo_nuevo  = mysqli_real_escape_string($conexion, $_POST['id_grupo_nuevo']);
    $ver_alertas     = true;

    $res_baja = mysqli_query($conexion, "SELECT * FROM bajas_participantes WHERE id_baja = '$id_baja'");
    if ($datos_b = mysqli_fetch_assoc($res_baja)) {
        $emp = mysqli_real_escape_string($conexion, $datos_b['numero_empleado']);
        $nom = mysqli_real_escape_string($conexion, $datos_b['nombre_completo']);
        $pue = mysqli_real_escape_string($conexion, $datos_b['puesto'] ?? '');
        $cen = mysqli_real_escape_string($conexion, $datos_b['numero_centro'] ?? '');
        $reg = mysqli_real_escape_string($conexion, $datos_b['region'] ?? '');
        $cor = mysqli_real_escape_string($conexion, $datos_b['correo'] ?? '');
        $cel = mysqli_real_escape_string($conexion, $datos_b['celular'] ?? '');

        if ($reg == '' || $cor == '' || $pue == '') {
            $res_extra = mysqli_query($conexion, "SELECT puesto, numero_centro, region, correo, celular 
                                                   FROM participantes_grupo WHERE numero_empleado = '$emp' LIMIT 1");
            if ($extra = mysqli_fetch_assoc($res_extra)) {
                if ($pue == '') $pue = mysqli_real_escape_string($conexion, $extra['puesto']);
                if ($cen == '') $cen = mysqli_real_escape_string($conexion, $extra['numero_centro']);
                if ($reg == '') $reg = mysqli_real_escape_string($conexion, $extra['region']);
                if ($cor == '') $cor = mysqli_real_escape_string($conexion, $extra['correo']);
                if ($cel == '') $cel = mysqli_real_escape_string($conexion, $extra['celular']);
            }
        }

        $res_cupo = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM participantes_grupo WHERE id_grupo = '$id_grupo_nuevo' AND estatus_inscripcion = 'Inscrito'");
        $total_inscritos = mysqli_fetch_assoc($res_cupo)['total'];

        $res_dup = mysqli_query($conexion, "SELECT id_participante FROM participantes_grupo WHERE id_grupo = '$id_grupo_nuevo' AND numero_empleado = '$emp' AND estatus_inscripcion = 'Inscrito'");

        if ($total_inscritos >= 26) {
            $mensaje = "⚠️ No se pudo reasignar: el grupo destino ya tiene sus 26 lugares llenos.";
            $tipo_alerta = "error";
        } elseif (mysqli_num_rows($res_dup) > 0) {
            $mensaje = "⚠️ Este colaborador ya se encuentra inscrito en el grupo seleccionado.";
            $tipo_alerta = "error";
        } else {
            $sql_ins = "INSERT INTO participantes_grupo 
                        (id_grupo, numero_empleado, nombre_completo, puesto, numero_centro, region, correo, celular)
                        VALUES 
                        ('$id_grupo_nuevo', '$emp', '$nom', '$pue', '$cen', '$reg', '$cor', '$cel')";
            if (mysqli_query($conexion, $sql_ins)) {
                $id_nuevo_p = mysqli_insert_id($conexion);
                mysqli_query($conexion, "INSERT INTO control_entrenadores (id_participante) VALUES ('$id_nuevo_p')");
                mysqli_query($conexion, "UPDATE bajas_participantes SET estatus_baja = 'Reasignado', id_grupo_destino = '$id_grupo_nuevo' WHERE id_baja = '$id_baja'");

                $mensaje = "✅ ¡Colaborador reasignado con todos sus datos al nuevo grupo futuro!";
                $tipo_alerta = "exito";
            }
        }
    }
}

// Acción 4: Borro definitivamente una alerta cuando el colaborador ya no va a tomar el taller
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'borrar_alerta') {
    $id_baja     = mysqli_real_escape_string($conexion, $_POST['id_baja']);
    $ver_alertas = true;

    if (mysqli_query($conexion, "DELETE FROM bajas_participantes WHERE id_baja = '$id_baja'")) {
        $mensaje = "🗑️ Registro de baja eliminado definitivamente.";
        $tipo_alerta = "exito";
    }
}

// Aquí junto en una sola lista a los activos y a los dados de baja pendientes para que funcione el buscador rápido
$participantes_json = [];

$sql_activos = "SELECT p.numero_empleado, p.nombre_completo, p.region, g.id_grupo, g.nombre_grupo, g.sede 
                FROM participantes_grupo p 
                JOIN grupos g ON p.id_grupo = g.id_grupo 
                WHERE p.estatus_inscripcion = 'Inscrito'
                ORDER BY p.nombre_completo ASC";
$res_activos = mysqli_query($conexion, $sql_activos);
while ($r = mysqli_fetch_assoc($res_activos)) {
    $r['estado'] = 'Inscrito';
    $r['motivo_baja'] = '';
    $r['eliminado_por'] = '';
    $participantes_json[] = $r;
}

$sql_bajas_busq = "SELECT b.numero_empleado, b.nombre_completo, b.region, b.motivo_baja, b.eliminado_por, g.id_grupo, g.nombre_grupo, g.sede 
                   FROM bajas_participantes b 
                   JOIN grupos g ON b.id_grupo = g.id_grupo 
                   WHERE b.estatus_baja = 'Pendiente'
                   ORDER BY b.fecha_baja DESC";
$res_bajas_busq = mysqli_query($conexion, $sql_bajas_busq);
while ($rb = mysqli_fetch_assoc($res_bajas_busq)) {
    $rb['estado'] = 'Baja';
    $rb['eliminado_por'] = ($rb['eliminado_por'] != '') ? $rb['eliminado_por'] : 'Entrenador';
    $participantes_json[] = $rb;
}

$res_total_bajas = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM bajas_participantes WHERE estatus_baja = 'Pendiente'");
$total_bajas = mysqli_fetch_assoc($res_total_bajas)['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Módulo CCORH - Grupos y Alertas de Bajas</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
        h1, h2, h3 { color: #2c3e50; text-align: center; }
        
        .menu { margin-bottom: 25px; padding: 15px; background: #ecf0f1; border-radius: 5px; text-align: center; }
        .menu a { text-decoration: none; background: #2980b9; color: white; padding: 9px 16px; margin: 0 5px; border-radius: 4px; font-weight: bold; font-size: 13px; display: inline-block; }
        .menu a:hover { background: #34495e; }
        .menu a.btn-alerta-menu { background: #e67e22; }
        .menu a.btn-alerta-menu:hover { background: #d35400; }
        .menu a.btn-volver-grupos { background: #34495e; }
        .menu a.btn-volver-grupos:hover { background: #2c3e50; }

        .alerta-msg { background: #d4efdf; color: #145a32; padding: 12px; border-radius: 4px; text-align: center; margin-bottom: 20px; font-size: 14px; border: 1px solid #a9dfbf; font-weight: bold; }
        .alerta-err { background: #fadbd8; color: #78281f; padding: 12px; border-radius: 4px; text-align: center; margin-bottom: 20px; font-size: 14px; border: 1px solid #f5b7b1; font-weight: bold; }

        .caja-buscador { background: #eaf2f8; padding: 15px; border-radius: 6px; border: 1px solid #aed6f1; margin-bottom: 25px; }
        .buscador { width: 100%; box-sizing: border-box; padding: 10px; border: 2px solid #2980b9; border-radius: 5px; font-size: 14px; background: white; }
        .resultados-busqueda { margin-top: 8px; background: white; border: 1px solid #bdc3c7; border-radius: 4px; max-height: 200px; overflow-y: auto; display: none; }
        .item-resultado { padding: 9px 12px; border-bottom: 1px solid #eee; font-size: 13px; color: #2c3e50; }
        .tag-inscrito { background: #27ae60; color: white; padding: 2px 7px; border-radius: 3px; font-size: 11px; font-weight: bold; margin-right: 6px; }
        .tag-baja { background: #e74c3c; color: white; padding: 2px 7px; border-radius: 3px; font-size: 11px; font-weight: bold; margin-right: 6px; }

        .grid-grupos { display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; margin-bottom: 25px; }
        .card-grupo { background: #f8f9fa; border: 2px solid #d5dbdb; border-radius: 8px; padding: 15px; width: 240px; text-align: center; }
        .card-grupo h4 { margin: 0 0 6px 0; color: #2c3e50; font-size: 16px; }
        .card-grupo p { margin: 4px 0; font-size: 12px; color: #566573; }
        .cupo-badge { display: inline-block; background: #2c3e50; color: white; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; margin: 8px 0; }
        .btn-ver { display: inline-block; text-decoration: none; background: #2980b9; color: white; padding: 6px 14px; border-radius: 4px; font-size: 12px; font-weight: bold; margin-top: 5px; }

        .banner-grupo { background: #ebf5fb; border: 2px solid #2980b9; border-radius: 8px; padding: 15px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .banner-grupo h3 { margin: 0; color: #1b4f72; text-align: left; }
        .banner-grupo p { margin: 4px 0 0 0; font-size: 13px; color: #34495e; }

        .box-editar { background: #fef9e7; border: 2px solid #f1c40f; padding: 20px; border-radius: 8px; margin-bottom: 25px; }
        .grid-edit { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
        .grid-edit input { width: 100%; box-sizing: border-box; padding: 7px; border: 1px solid #bdc3c7; border-radius: 4px; }

        .grupo-bajas-card { background: #fdf2e9; border: 2px solid #e67e22; padding: 18px; border-radius: 8px; margin-bottom: 25px; }
        .grupo-bajas-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f5cba7; padding-bottom: 10px; margin-bottom: 12px; }
        .grupo-bajas-header h3 { color: #d35400; margin: 0; text-align: left; font-size: 17px; }
        .badge-rol { padding: 4px 9px; border-radius: 4px; font-size: 11px; font-weight: bold; color: white; display: inline-block; }
        .rol-ccorh { background: #2980b9; }
        .rol-entrenador { background: #8e44ad; }

        .select-futuro { padding: 5px; border: 1px solid #bdc3c7; border-radius: 4px; font-size: 12px; max-width: 195px; }
        .btn-reasignar { background: #27ae60; color: white; border: none; padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; cursor: pointer; margin-left: 4px; }
        .btn-reasignar:hover { background: #219653; }
        .btn-borrar-alerta { background: #c0392b; color: white; border: none; padding: 5px 9px; border-radius: 4px; font-size: 12px; font-weight: bold; cursor: pointer; margin-left: 4px; }
        .btn-borrar-alerta:hover { background: #922b21; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; background: white; }
        th, td { border: 1px solid #bdc3c7; padding: 8px; text-align: center; }
        th { background: #2c3e50; color: white; }
        tr.fila-vacia { background-color: #fbfcfc; color: #95a5a6; font-style: italic; }
        
        .btn-editar { background: #f39c12; color: white; text-decoration: none; padding: 4px 9px; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-block; }
        .btn-editar:hover { background: #d68910; }
        .btn-baja { background: #e74c3c; color: white; border: none; padding: 4px 9px; border-radius: 4px; font-size: 12px; font-weight: bold; cursor: pointer; }
        .btn-baja:hover { background: #c0392b; }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); z-index: 1000; justify-content: center; align-items: center; }
        .modal-box { background: white; padding: 28px 30px; border-radius: 10px; width: 90%; max-width: 440px; box-shadow: 0px 10px 30px rgba(0,0,0,0.3); text-align: left; }
        .modal-box h3 { color: #e74c3c; margin: 0 0 8px 0; font-size: 20px; text-align: center; }
        .modal-sub { text-align: center; color: #566573; font-size: 14px; margin-bottom: 20px; }
        .modal-box label { display: block; font-weight: bold; color: #2c3e50; font-size: 13px; margin-bottom: 6px; margin-top: 12px; }
        .modal-box input[type="text"] { width: 100%; box-sizing: border-box; padding: 10px; border: 1px solid #bdc3c7; border-radius: 5px; font-size: 14px; }
        .modal-box input[type="text"]:focus { border-color: #e74c3c; outline: none; }
        .modal-actions { display: flex; justify-content: center; gap: 12px; margin-top: 22px; }
        .btn-confirmar-baja { background: #e74c3c; color: white; border: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; font-size: 14px; cursor: pointer; }
        .btn-confirmar-baja:hover { background: #c0392b; }
        .btn-cancelar-baja { background: #7f8c8d; color: white; border: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; font-size: 14px; cursor: pointer; }
        .btn-cancelar-baja:hover { background: #626567; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Taller de Diálogos de Calidad</h1>
        
        <div class="menu">
            <a href="index.php">← Menú Principal</a>
            
            <?php if ($id_grupo_sel != "" || $ver_alertas): ?>
                <a href="listado_grupos.php" class="btn-volver-grupos">← Ver Grupos</a>
            <?php endif; ?>

            <a href="participantes.php<?php echo ($id_grupo_sel != '') ? '?id_grupo='.$id_grupo_sel : ''; ?>">Inscribir Participante</a>
            
            <?php if (!$ver_alertas): ?>
                <a href="listado_grupos.php?alertas=1" class="btn-alerta-menu">
                    ⚠️ Alertas de Bajas (<?php echo $total_bajas; ?>)
                </a>
            <?php endif; ?>
        </div>

        <?php if ($mensaje != "") { 
            $clase_m = ($tipo_alerta == "error") ? "alerta-err" : "alerta-msg";
            echo "<div class='$clase_m'>$mensaje</div>"; 
        } ?>

        <?php if ($ver_alertas): ?>
            <h2>⚠️ Alertas de Bajas por Grupo</h2>
            <p style="text-align: center; font-size: 13px; color: #566573; margin-bottom: 25px;">
                Historial de bajas pendientes registradas por <b>CCORH</b> o <b>Entrenador</b>. Puedes reasignar a un grupo con <b>fecha futura</b> o borrar el registro si ya no tomará el taller.
            </p>

            <?php
            $sql_grupos_bajas = "SELECT DISTINCT g.id_grupo, g.nombre_grupo, g.sede, g.fecha_inicio, g.fecha_fin
                                 FROM bajas_participantes b
                                 JOIN grupos g ON b.id_grupo = g.id_grupo
                                 WHERE b.estatus_baja = 'Pendiente'
                                 ORDER BY g.fecha_inicio ASC";
            $res_gb = mysqli_query($conexion, $sql_grupos_bajas);

            if (mysqli_num_rows($res_gb) > 0):
                while ($gb = mysqli_fetch_assoc($res_gb)):
                    $id_g_baja    = $gb['id_grupo'];
                    $fecha_origen = $gb['fecha_inicio'];

                    $sql_futuros = "SELECT g.*, 
                                    (SELECT COUNT(*) FROM participantes_grupo p WHERE p.id_grupo = g.id_grupo AND p.estatus_inscripcion = 'Inscrito') AS inscritos
                                    FROM grupos g 
                                    WHERE g.fecha_inicio > '$fecha_origen' 
                                      AND g.fecha_inicio >= CURDATE()
                                    ORDER BY g.fecha_inicio ASC";
                    $res_futuros = mysqli_query($conexion, $sql_futuros);
                    $grupos_futuros = [];
                    while ($gf = mysqli_fetch_assoc($res_futuros)) {
                        $grupos_futuros[] = $gf;
                    }

                    $res_detalle_bajas = mysqli_query($conexion, "SELECT * FROM bajas_participantes WHERE id_grupo = '$id_g_baja' AND estatus_baja = 'Pendiente' ORDER BY fecha_baja DESC");
                    $cant_bajas_grupo = mysqli_num_rows($res_detalle_bajas);
            ?>
                <div class="grupo-bajas-card">
                    <div class="grupo-bajas-header">
                        <div>
                            <h3>📋 <?php echo $gb['nombre_grupo']; ?> — Sede: <?php echo $gb['sede']; ?></h3>
                            <span style="font-size: 12px; color: #7e5109;">Fechas originales: <?php echo $gb['fecha_inicio']; ?> al <?php echo $gb['fecha_fin']; ?></span>
                        </div>
                        <span class="cupo-badge" style="background: #d35400; margin: 0;">
                            <?php echo $cant_bajas_grupo; ?> baja(s) en este grupo
                        </span>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>No. Emp.</th>
                                <th>Colaborador Dado de Baja</th>
                                <th>Región</th>
                                <th>Registrada Por</th>
                                <th>Motivo de la Baja</th>
                                <th>Fecha Baja</th>
                                <th style="width: 345px;">Acciones (Reasignar o Borrar)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($b = mysqli_fetch_assoc($res_detalle_bajas)): 
                                $quien_raw = $b['eliminado_por'] ?: 'Entrenador';
                                $es_ccorh  = (stripos($quien_raw, 'CCORH') !== false);
                                $clase_rol = $es_ccorh ? 'rol-ccorh' : 'rol-entrenador';
                            ?>
                            <tr>
                                <td><?php echo $b['numero_empleado']; ?></td>
                                <td style="text-align: left;"><b><?php echo $b['nombre_completo']; ?></b></td>
                                <td><b><?php echo $b['region'] ?: '-'; ?></b></td>
                                <td><span class="badge-rol <?php echo $clase_rol; ?>"><?php echo htmlspecialchars($quien_raw); ?></span></td>
                                <td style="color: #c0392b; text-align: left;"><b><?php echo htmlspecialchars($b['motivo_baja']); ?></b></td>
                                <td><?php echo $b['fecha_baja']; ?></td>
                                <td>
                                    <div style="display:flex; justify-content:center; align-items:center; gap: 4px; flex-wrap: wrap;">
                                        <?php if (count($grupos_futuros) > 0): ?>
                                            <form method="POST" action="listado_grupos.php?alertas=1" style="display:inline-flex; align-items:center;">
                                                <input type="hidden" name="accion" value="reasignar">
                                                <input type="hidden" name="id_baja" value="<?php echo $b['id_baja']; ?>">
                                                <select name="id_grupo_nuevo" class="select-futuro" required>
                                                    <option value="">-- Elegir grupo futuro --</option>
                                                    <?php foreach ($grupos_futuros as $gf): ?>
                                                        <option value="<?php echo $gf['id_grupo']; ?>">
                                                            <?php echo $gf['nombre_grupo']; ?> (<?php echo $gf['fecha_inicio']; ?> | <?php echo $gf['inscritos']; ?>/26)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn-reasignar">Reasignar</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="font-size: 11px; color: #7f8c8d; font-style: italic; margin-right: 4px;">
                                                Sin fechas posteriores
                                            </span>
                                        <?php endif; ?>

                                        <form method="POST" action="listado_grupos.php?alertas=1" style="display:inline;" 
                                              onsubmit="return confirm('¿Seguro que deseas borrar definitivamente de la lista a <?php echo addslashes($b['nombre_completo']); ?>?');">
                                            <input type="hidden" name="accion" value="borrar_alerta">
                                            <input type="hidden" name="id_baja" value="<?php echo $b['id_baja']; ?>">
                                            <button type="submit" class="btn-borrar-alerta" title="Borrar alerta definitivamente">Borrar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <p style="text-align: center; color: #7f8c8d; padding: 30px; background: #f8f9fa; border-radius: 6px;">
                    No hay alertas de bajas pendientes en ningún grupo.
                </p>
            <?php endif; ?>

        <?php else: ?>
            <h2>Módulo CCORH</h2>
            <h2>Grupos Asignados y Participantes</h2>

            <div class="caja-buscador">
                <label for="inputBuscador" style="font-weight: bold; display: block; margin-bottom: 6px; color: #1b4f72; font-size: 13px;">
                    🔍 Buscar Participante (Inscritos activos y Dados de baja):
                </label>
                <input type="text" id="inputBuscador" class="buscador" onkeyup="buscarParticipante()" 
                       placeholder="Escribe nombre, número de empleado, grupo o región...">
                <div id="listaResultados" class="resultados-busqueda"></div>
            </div>

            <?php if ($id_grupo_sel == ""): ?>
                <div class="grid-grupos">
                    <?php
                    $sql_grupos = "SELECT g.*, 
                                   (SELECT COUNT(*) FROM participantes_grupo p 
                                    WHERE p.id_grupo = g.id_grupo AND p.estatus_inscripcion = 'Inscrito') AS total_inscritos
                                   FROM grupos g 
                                   ORDER BY g.fecha_inicio ASC";
                    $res_g = mysqli_query($conexion, $sql_grupos);

                    while($g = mysqli_fetch_assoc($res_g)):
                    ?>
                        <div class="card-grupo">
                            <h4><?php echo $g['nombre_grupo']; ?></h4>
                            <p><b>Sede:</b> <?php echo $g['sede']; ?></p>
                            <p><b>Fechas:</b> <?php echo $g['fecha_inicio']; ?> al <?php echo $g['fecha_fin']; ?></p>
                            <div class="cupo-badge"><?php echo $g['total_inscritos']; ?> / 26 inscritos</div><br>
                            <a class="btn-ver" href="listado_grupos.php?id_grupo=<?php echo $g['id_grupo']; ?>">
                                Ver Participantes
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
                <p style="text-align: center; color: #7f8c8d; margin-top: 10px;">👆 Haz clic en <b>"Ver Participantes"</b> en cualquiera de los grupos para abrir su listado de 26 lugares.</p>
            <?php endif; ?>

            <?php
            if ($id_editar != ""):
                $res_ed = mysqli_query($conexion, "SELECT * FROM participantes_grupo WHERE id_participante = '$id_editar'");
                if ($datos_ed = mysqli_fetch_assoc($res_ed)):
            ?>
                <div class="box-editar">
                    <h3 style="margin-top:0; color:#9a7d0a;">✏️ Modificar Datos del Participante</h3>
                    <form method="POST" action="listado_grupos.php">
                        <input type="hidden" name="accion" value="editar">
                        <input type="hidden" name="id_participante" value="<?php echo $datos_ed['id_participante']; ?>">
                        <input type="hidden" name="id_grupo" value="<?php echo $datos_ed['id_grupo']; ?>">

                        <div class="grid-edit">
                            <div>
                                <label style="font-size:12px; font-weight:bold;">No. Empleado:</label>
                                <input type="text" name="numero_empleado" value="<?php echo $datos_ed['numero_empleado']; ?>" required>
                            </div>
                            <div>
                                <label style="font-size:12px; font-weight:bold;">Nombre Completo:</label>
                                <input type="text" name="nombre_completo" value="<?php echo $datos_ed['nombre_completo']; ?>" required>
                            </div>
                            <div>
                                <label style="font-size:12px; font-weight:bold;">Región:</label>
                                <input type="text" name="region" value="<?php echo $datos_ed['region']; ?>" required>
                            </div>
                            <div>
                                <label style="font-size:12px; font-weight:bold;">Puesto:</label>
                                <input type="text" name="puesto" value="<?php echo $datos_ed['puesto']; ?>">
                            </div>
                            <div>
                                <label style="font-size:12px; font-weight:bold;">Centro:</label>
                                <input type="text" name="numero_centro" value="<?php echo $datos_ed['numero_centro']; ?>">
                            </div>
                            <div>
                                <label style="font-size:12px; font-weight:bold;">Correo:</label>
                                <input type="email" name="correo" value="<?php echo $datos_ed['correo']; ?>">
                            </div>
                            <div>
                                <label style="font-size:12px; font-weight:bold;">Celular:</label>
                                <input type="text" name="celular" value="<?php echo $datos_ed['celular']; ?>">
                            </div>
                        </div>

                        <div style="text-align:center; margin-top:15px;">
                            <button type="submit" style="background:#27ae60; color:white; border:none; padding:8px 20px; border-radius:4px; font-weight:bold; cursor:pointer;">
                                Guardar Cambios
                            </button>
                            <a href="listado_grupos.php?id_grupo=<?php echo $datos_ed['id_grupo']; ?>" style="margin-left:10px; color:#7f8c8d; font-size:13px; font-weight:bold; text-decoration:none;">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            <?php 
                endif;
            endif; 
            ?>

            <?php if($id_grupo_sel != ""): ?>
                <?php
                $res_info = mysqli_query($conexion, "SELECT * FROM grupos WHERE id_grupo = '$id_grupo_sel'");
                $info_grupo = mysqli_fetch_assoc($res_info);

                $lista_inscritos = [];
                $sql_listado = "SELECT * FROM participantes_grupo 
                                WHERE id_grupo = '$id_grupo_sel' AND estatus_inscripcion = 'Inscrito'
                                ORDER BY id_participante ASC";
                $res_listado = mysqli_query($conexion, $sql_listado);
                while ($fila = mysqli_fetch_assoc($res_listado)) {
                    $lista_inscritos[] = $fila;
                }
                $total_actual = count($lista_inscritos);
                ?>

                <div class="banner-grupo">
                    <div>
                        <h3>📋 <?php echo $info_grupo['nombre_grupo']; ?> — Sede: <?php echo $info_grupo['sede']; ?></h3>
                        <p><b>Fechas del taller:</b> <?php echo $info_grupo['fecha_inicio']; ?> al <?php echo $info_grupo['fecha_fin']; ?></p>
                    </div>
                    <div class="cupo-badge" style="font-size: 14px; padding: 6px 14px;">
                        Ocupación: <?php echo $total_actual; ?> / 26 lugares
                    </div>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th style="width: 45px;">#</th>
                            <th>No. Emp.</th>
                            <th>Nombre Completo</th>
                            <th>Región</th>
                            <th>Puesto</th>
                            <th>Centro</th>
                            <th>Correo</th>
                            <th>Celular</th>
                            <th style="width: 145px;">Acciones CCORH</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        for ($i = 0; $i < 26; $i++):
                            $num_lugar = $i + 1;

                            if (isset($lista_inscritos[$i])):
                                $p = $lista_inscritos[$i];
                        ?>
                                <tr>
                                    <td><b><?php echo $num_lugar; ?></b></td>
                                    <td><?php echo $p['numero_empleado']; ?></td>
                                    <td style="text-align: left;"><b><?php echo $p['nombre_completo']; ?></b></td>
                                    <td><b><?php echo $p['region'] ?: '-'; ?></b></td>
                                    <td><?php echo $p['puesto']; ?></td>
                                    <td><?php echo $p['numero_centro']; ?></td>
                                    <td><?php echo $p['correo']; ?></td>
                                    <td><?php echo $p['celular']; ?></td>
                                    <td>
                                        <a class="btn-editar" href="listado_grupos.php?id_grupo=<?php echo $id_grupo_sel; ?>&editar=<?php echo $p['id_participante']; ?>">
                                            Editar
                                        </a>
                                        <button type="button" class="btn-baja" 
                                                onclick="abrirModalBaja('<?php echo $p['id_participante']; ?>', '<?php echo $id_grupo_sel; ?>', '<?php echo htmlspecialchars(addslashes($p['nombre_completo']), ENT_QUOTES); ?>')">
                                            Baja
                                        </button>
                                    </td>
                                </tr>
                        <?php else: ?>
                                <tr class="fila-vacia">
                                    <td><?php echo $num_lugar; ?></td>
                                    <td colspan="8" style="text-align: left; padding-left: 15px;">Espacio disponible</td>
                                </tr>
                        <?php 
                            endif;
                        endfor; 
                        ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php endif; ?>

    </div>

    <!-- Modal elegante para registrar la baja en CCORH -->
    <div class="modal-overlay" id="modalBajaCCORH">
        <div class="modal-box">
            <h3>⚠️ Registrar Baja de Participante</h3>
            <p class="modal-sub">Vas a dar de baja a: <b id="modalNombrePart"></b></p>

            <form method="POST" action="listado_grupos.php">
                <input type="hidden" name="accion" value="baja">
                <input type="hidden" name="id_participante" id="modalIdPart">
                <input type="hidden" name="id_grupo" id="modalIdGrupo">

                <label for="inputResponsableCCORH">¿Quién de CCORH registra esta baja?</label>
                <input type="text" id="inputResponsableCCORH" name="responsable_baja" 
                       placeholder="Escribe tu nombre completo..." required>

                <label for="inputMotivoBaja">Motivo de la baja:</label>
                <input type="text" id="inputMotivoBaja" name="motivo_baja" 
                       placeholder="Ej. Incapacidad médica, Cancelación de viaje, Vacaciones..." required>

                <div class="modal-actions">
                    <button type="submit" class="btn-confirmar-baja">Confirmar Baja</button>
                    <button type="button" class="btn-cancelar-baja" onclick="cerrarModalBaja()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const participantes = <?php echo json_encode($participantes_json); ?>;

        function buscarParticipante() {
            const texto = document.getElementById("inputBuscador").value.toLowerCase().trim();
            const contenedor = document.getElementById("listaResultados");

            if (texto === "") {
                contenedor.style.display = "none";
                contenedor.innerHTML = "";
                return;
            }

            const filtrados = participantes.filter(p =>
                (p.nombre_completo && p.nombre_completo.toLowerCase().includes(texto)) ||
                (p.numero_empleado && p.numero_empleado.toLowerCase().includes(texto)) ||
                (p.nombre_grupo && p.nombre_grupo.toLowerCase().includes(texto)) ||
                (p.region && p.region.toLowerCase().includes(texto))
            );

            if (filtrados.length === 0) {
                contenedor.innerHTML = "<div class='item-resultado'>No se encontraron registros con ese dato (ni inscritos ni bajas).</div>";
            } else {
                contenedor.innerHTML = filtrados.map(p => {
                    if (p.estado === 'Inscrito') {
                        return `<div class='item-resultado'>
                            <span class="tag-inscrito">INSCRITO</span>
                            <b>${p.nombre_completo}</b> (Emp: ${p.numero_empleado}) — 
                            <a href="listado_grupos.php?id_grupo=${p.id_grupo}" style="color:#2980b9; font-weight:bold; text-decoration:none;">
                                ${p.nombre_grupo} (${p.sede})
                            </a> | Región: <b>${p.region || 'N/A'}</b>
                        </div>`;
                    } else {
                        return `<div class='item-resultado' style="background:#fff9f9;">
                            <span class="tag-baja">BAJA</span>
                            <b>${p.nombre_completo}</b> (Emp: ${p.numero_empleado}) — 
                            Estuvo en <a href="listado_grupos.php?alertas=1" style="color:#c0392b; font-weight:bold; text-decoration:none;">${p.nombre_grupo}</a> 
                            | Baja por: <b>${p.eliminado_por}</b> | Motivo: <i>${p.motivo_baja}</i>
                        </div>`;
                    }
                }).join("");
            }

            contenedor.style.display = "block";
        }

        function abrirModalBaja(idPart, idGrupo, nombre) {
            document.getElementById('modalIdPart').value = idPart;
            document.getElementById('modalIdGrupo').value = idGrupo;
            document.getElementById('modalNombrePart').textContent = nombre;
            document.getElementById('inputResponsableCCORH').value = '';
            document.getElementById('inputMotivoBaja').value = '';
            document.getElementById('modalBajaCCORH').style.display = 'flex';
            document.getElementById('inputResponsableCCORH').focus();
        }

        function cerrarModalBaja() {
            document.getElementById('modalBajaCCORH').style.display = 'none';
        }
    </script>

</body>
</html>