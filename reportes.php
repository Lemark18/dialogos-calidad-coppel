<?php
// Enlazo mi conexión a la base de datos y calculo los conteos y porcentajes generales del sistema
include("conexion.php");

$id_grupo_sel = isset($_GET['id_grupo']) ? intval($_GET['id_grupo']) : 0;
$vista_activa = isset($_GET['vista']) ? $_GET['vista'] : 'grupos';

// Cuento cuántos grupos hay programados y multiplico por 26 para sacar la capacidad total
$total_grupos = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS t FROM grupos"))['t'] ?? 0;
$capacidad_total = $total_grupos * 26;

// Cuento los participantes inscritos activos y cuántas invitaciones por correo ya se enviaron
$total_inscritos = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS t FROM participantes_grupo WHERE estatus_inscripcion = 'Inscrito'"))['t'] ?? 0;
$correos_enviados = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS t FROM participantes_grupo WHERE estatus_inscripcion = 'Inscrito' AND invitacion = 'Enviada'"))['t'] ?? 0;
$correos_pendientes = $total_inscritos - $correos_enviados;
$pct_enviados = ($total_inscritos > 0) ? round(($correos_enviados / $total_inscritos) * 100, 1) : 0;

// Calculo cuántos ya confirmaron asistencia, quiénes dijeron que no y quiénes siguen pendientes
$confirmados = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS t FROM control_entrenadores c JOIN participantes_grupo p ON c.id_participante = p.id_participante WHERE p.estatus_inscripcion = 'Inscrito' AND c.confirmacion = 'Confirmó'"))['t'] ?? 0;
$no_confirmados = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS t FROM control_entrenadores c JOIN participantes_grupo p ON c.id_participante = p.id_participante WHERE p.estatus_inscripcion = 'Inscrito' AND c.confirmacion = 'No confirmó'"))['t'] ?? 0;
$pendientes_conf = $total_inscritos - ($confirmados + $no_confirmados);

$pct_confirmados = ($total_inscritos > 0) ? round(($confirmados / $total_inscritos) * 100, 1) : 0;
$pct_no_confirmados = ($total_inscritos > 0) ? round(($no_confirmados / $total_inscritos) * 100, 1) : 0;

// Saco el porcentaje real de asistencia al taller contra los inscritos y contra los confirmados
$asistieron_total = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS t FROM control_entrenadores c JOIN participantes_grupo p ON c.id_participante = p.id_participante WHERE p.estatus_inscripcion = 'Inscrito' AND (c.asistencia_dia1 = 'Asistió' OR c.asistencia_dia2 = 'Asistió')"))['t'] ?? 0;
$no_asistieron_total = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS t FROM control_entrenadores c JOIN participantes_grupo p ON c.id_participante = p.id_participante WHERE p.estatus_inscripcion = 'Inscrito' AND (c.asistencia_dia1 = 'Falta' OR c.asistencia_dia2 = 'Falta' OR c.confirmacion = 'No confirmó')"))['t'] ?? 0;

$pct_asistencia_inscritos = ($total_inscritos > 0) ? round(($asistieron_total / $total_inscritos) * 100, 1) : 0;
$pct_asistencia_confirmados = ($confirmados > 0) ? round(($asistieron_total / $confirmados) * 100, 1) : 0;
$pct_no_asistencia = ($total_inscritos > 0) ? round(($no_asistieron_total / $total_inscritos) * 100, 1) : 0;

// Cuento las bajas registradas por CCORH y por Entrenadores, más los reasignados
$total_bajas = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS t FROM bajas_participantes"))['t'] ?? 0;
$bajas_ccorh = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS t FROM bajas_participantes WHERE eliminado_por LIKE 'CCORH%'"))['t'] ?? 0;
$bajas_entrenador = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS t FROM bajas_participantes WHERE eliminado_por NOT LIKE 'CCORH%'"))['t'] ?? 0;
$total_reasignados = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS t FROM bajas_participantes WHERE estatus_baja = 'Reasignado'"))['t'] ?? 0;

// Consulta agrupada por sede y punto de reunión (Aeropuerto CDMX vs Corporativo Coppel)
$sql_sedes = "SELECT g.sede,
                     COUNT(DISTINCT g.id_grupo) AS num_grupos,
                     COUNT(p.id_participante) AS inscritos,
                     SUM(CASE WHEN c.confirmacion = 'Confirmó' THEN 1 ELSE 0 END) AS confirmados,
                     SUM(CASE WHEN c.punto_reunion = 'Aeropuerto CDMX' THEN 1 ELSE 0 END) AS cdmx,
                     SUM(CASE WHEN c.punto_reunion = 'Corporativo Coppel' THEN 1 ELSE 0 END) AS coppel,
                     SUM(CASE WHEN c.asistencia_dia1 = 'Asistió' OR c.asistencia_dia2 = 'Asistió' THEN 1 ELSE 0 END) AS asistieron
              FROM grupos g
              LEFT JOIN participantes_grupo p ON g.id_grupo = p.id_grupo AND p.estatus_inscripcion = 'Inscrito'
              LEFT JOIN control_entrenadores c ON p.id_participante = c.id_participante
              GROUP BY g.sede
              ORDER BY g.sede ASC";

// Consulta para contar cuántas veces participó cada entrenador como Titular 1, Titular 2 o Logística
$sql_entrenadores = "SELECT e.id_entrenador, e.nombre_entrenador,
                            (SELECT COUNT(*) FROM grupos g1 WHERE g1.id_entrenador_1 = e.id_entrenador) AS veces_t1,
                            (SELECT COUNT(*) FROM grupos g2 WHERE g2.id_entrenador_2 = e.id_entrenador) AS veces_t2,
                            (SELECT COUNT(*) FROM grupos gl WHERE gl.id_entrenador_logistica = e.id_entrenador) AS veces_log
                     FROM entrenadores e
                     ORDER BY (veces_t1 + veces_t2 + veces_log) DESC, e.nombre_entrenador ASC";

// Consulta compartida para el historial de bajas y grupo destino de reasignación
$sql_historial_bajas = "SELECT b.*, 
                               g.nombre_grupo AS grupo_origen, g.sede AS sede_origen,
                               gd.nombre_grupo AS grupo_destino, gd.sede AS sede_destino, gd.fecha_inicio AS fecha_destino
                        FROM bajas_participantes b 
                        LEFT JOIN grupos g ON b.id_grupo = g.id_grupo 
                        LEFT JOIN grupos gd ON b.id_grupo_destino = gd.id_grupo
                        ORDER BY b.fecha_baja DESC";

// Si aprietan el botón verde "Descargar Todo en Excel", armo y descargo el archivo .xls con todas las tablas
if (isset($_GET['exportar']) && $_GET['exportar'] == 'excel') {
    $fecha_archivo = date("Y-m-d_Hi");
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=Reporte_Ejecutivo_Dialogos_Calidad_$fecha_archivo.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "\xEF\xBB\xBF";
    echo "<html><head><meta charset='UTF-8'></head><body>";

    echo "<h3>1. RESUMEN EJECUTIVO GLOBAL - TALLER DIÁLOGOS DE CALIDAD</h3>";
    echo "<table border='1'>
            <tr style='background-color:#2c3e50; color:#ffffff; font-weight:bold;'>
                <th>Indicador</th><th>Cantidad</th><th>Porcentaje / Base</th>
            </tr>
            <tr><td>Grupos Programados</td><td>$total_grupos</td><td>Capacidad Total: $capacidad_total lugares</td></tr>
            <tr><td>Participantes Inscritos Activos</td><td>$total_inscritos</td><td>" . ($capacidad_total > 0 ? round(($total_inscritos/$capacidad_total)*100, 1) : 0) . "% de ocupación</td></tr>
            <tr><td>Invitaciones Enviadas por Correo</td><td>$correos_enviados</td><td>$pct_enviados% de inscritos</td></tr>
            <tr><td>Participantes Confirmados</td><td>$confirmados</td><td>$pct_confirmados% de inscritos</td></tr>
            <tr><td>Participantes No Confirmaron</td><td>$no_confirmados</td><td>$pct_no_confirmados% de inscritos</td></tr>
            <tr><td>Pendientes de Confirmar</td><td>$pendientes_conf</td><td>" . ($total_inscritos > 0 ? round(($pendientes_conf/$total_inscritos)*100, 1) : 0) . "% de inscritos</td></tr>
            <tr><td>Asistencia Efectiva (Asistieron)</td><td>$asistieron_total</td><td>$pct_asistencia_inscritos% de inscritos ($pct_asistencia_confirmados% de confirmados)</td></tr>
            <tr><td>Inasistencias (No Asistieron)</td><td>$no_asistieron_total</td><td>$pct_no_asistencia% de inscritos</td></tr>
            <tr><td>Bajas Totales Registradas</td><td>$total_bajas</td><td>CCORH: $bajas_ccorh | Entrenadores: $bajas_entrenador</td></tr>
            <tr><td>Participantes Reasignados</td><td>$total_reasignados</td><td>Reubicados en otro grupo</td></tr>
          </table><br>";

    echo "<h3>2. RESUMEN POR SEDE Y PUNTOS DE REUNIÓN</h3>";
    echo "<table border='1'>
            <tr style='background-color:#16a085; color:#ffffff; font-weight:bold;'>
                <th>Sede</th><th>Grupos</th><th>Inscritos</th><th>Confirmados</th>
                <th>Punto: Aeropuerto CDMX</th><th>Punto: Corporativo Coppel</th><th>Asistieron</th><th>% Asistencia</th>
            </tr>";
    $res_s_exp = mysqli_query($conexion, $sql_sedes);
    while ($s = mysqli_fetch_assoc($res_s_exp)) {
        $pct_s = ($s['inscritos'] > 0) ? round(($s['asistieron'] / $s['inscritos']) * 100, 1) : 0;
        echo "<tr>
                <td>" . htmlspecialchars($s['sede']) . "</td>
                <td>" . $s['num_grupos'] . "</td>
                <td>" . $s['inscritos'] . "</td>
                <td>" . intval($s['confirmados']) . "</td>
                <td>" . intval($s['cdmx']) . "</td>
                <td>" . intval($s['coppel']) . "</td>
                <td>" . intval($s['asistieron']) . "</td>
                <td>$pct_s%</td>
              </tr>";
    }
    echo "</table><br>";

    echo "<h3>3. CONSOLIDADO DE PARTICIPACIÓN POR ENTRENADOR</h3>";
    echo "<table border='1'>
            <tr style='background-color:#2980b9; color:#ffffff; font-weight:bold;'>
                <th>Entrenador</th><th>Instructor Titular 1</th><th>Instructor Titular 2</th><th>Apoyo Logística</th><th>Total Participaciones</th>
            </tr>";
    $res_e_exp = mysqli_query($conexion, $sql_entrenadores);
    while ($e = mysqli_fetch_assoc($res_e_exp)) {
        $tot_e = $e['veces_t1'] + $e['veces_t2'] + $e['veces_log'];
        echo "<tr>
                <td>" . htmlspecialchars($e['nombre_entrenador']) . "</td>
                <td>" . $e['veces_t1'] . "</td>
                <td>" . $e['veces_t2'] . "</td>
                <td>" . $e['veces_log'] . "</td>
                <td><b>$tot_e</b></td>
              </tr>";
    }
    echo "</table><br>";

    echo "<h3>4. DETALLE DE PARTICIPANTES POR GRUPO</h3>";
    echo "<table border='1'>
            <tr style='background-color:#2c3e50; color:#ffffff; font-weight:bold;'>
                <th>Grupo</th><th>Sede</th><th>Fechas</th><th>No. Empleado</th><th>Participante</th>
                <th>Región</th><th>Puesto</th><th>Invitación</th><th>Confirmación</th><th>Punto de Reunión</th><th>Estatus Asistencia</th><th>Observaciones</th>
            </tr>";
    $sql_det_exp = "SELECT g.nombre_grupo, g.sede, g.fecha_inicio, g.fecha_fin,
                           p.numero_empleado, p.nombre_completo, p.region, p.puesto, p.invitacion,
                           COALESCE(c.confirmacion, 'Pendiente') AS confirmacion,
                           COALESCE(c.punto_reunion, 'Sin asignar') AS punto_reunion,
                           CASE 
                               WHEN c.asistencia_dia1 = 'Asistió' OR c.asistencia_dia2 = 'Asistió' THEN 'Asistió'
                               WHEN c.asistencia_dia1 = 'Falta' OR c.asistencia_dia2 = 'Falta' THEN 'No asistió'
                               ELSE 'Pendiente'
                           END AS est_asistencia,
                           COALESCE(c.motivo_inasistencia, '') AS obs
                    FROM participantes_grupo p
                    JOIN grupos g ON p.id_grupo = g.id_grupo
                    LEFT JOIN control_entrenadores c ON p.id_participante = c.id_participante
                    WHERE p.estatus_inscripcion = 'Inscrito'
                    ORDER BY g.fecha_inicio ASC, p.nombre_completo ASC";
    $res_det_exp = mysqli_query($conexion, $sql_det_exp);
    while ($d = mysqli_fetch_assoc($res_det_exp)) {
        echo "<tr>
                <td>" . htmlspecialchars($d['nombre_grupo']) . "</td>
                <td>" . htmlspecialchars($d['sede']) . "</td>
                <td>" . $d['fecha_inicio'] . " al " . $d['fecha_fin'] . "</td>
                <td>" . htmlspecialchars($d['numero_empleado']) . "</td>
                <td>" . htmlspecialchars($d['nombre_completo']) . "</td>
                <td>" . htmlspecialchars($d['region']) . "</td>
                <td>" . htmlspecialchars($d['puesto']) . "</td>
                <td>" . htmlspecialchars($d['invitacion']) . "</td>
                <td>" . htmlspecialchars($d['confirmacion']) . "</td>
                <td>" . htmlspecialchars($d['punto_reunion']) . "</td>
                <td>" . htmlspecialchars($d['est_asistencia']) . "</td>
                <td>" . htmlspecialchars($d['obs']) . "</td>
              </tr>";
    }
    echo "</table><br>";

    echo "<h3>5. HISTORIAL DE BAJAS Y REASIGNACIONES</h3>";
    echo "<table border='1'>
            <tr style='background-color:#c0392b; color:#ffffff; font-weight:bold;'>
                <th>Grupo Original</th><th>Grupo Destino (Migra)</th><th>No. Empleado</th><th>Colaborador</th>
                <th>Región</th><th>Registrada Por</th><th>Motivo y Reportante</th><th>Fecha Baja</th>
            </tr>";
    $res_b_exp = mysqli_query($conexion, $sql_historial_bajas);
    while ($b = mysqli_fetch_assoc($res_b_exp)) {
        $origen_txt = ($b['grupo_origen'] ?? 'N/D') . " (" . ($b['sede_origen'] ?? 'N/D') . ")";
        $destino_txt = ($b['estatus_baja'] == 'Reasignado' && !empty($b['grupo_destino'])) 
            ? $b['grupo_destino'] . " (" . $b['sede_destino'] . ")" 
            : "Sin reasignar (Pendiente)";
        echo "<tr>
                <td>" . htmlspecialchars($origen_txt) . "</td>
                <td>" . htmlspecialchars($destino_txt) . "</td>
                <td>" . htmlspecialchars($b['numero_empleado']) . "</td>
                <td>" . htmlspecialchars($b['nombre_completo']) . "</td>
                <td>" . htmlspecialchars($b['region'] ?? '') . "</td>
                <td>" . htmlspecialchars($b['eliminado_por']) . "</td>
                <td>" . htmlspecialchars($b['motivo_baja']) . "</td>
                <td>" . $b['fecha_baja'] . "</td>
              </tr>";
    }
    echo "</table></body></html>";
    exit();
}

// Función para mostrar las fechas en español
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
    <title>Módulo 5 - Reportería Ejecutiva y Grupos</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 1280px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
        h1, h2, h3 { color: #2c3e50; text-align: center; }

        .menu { margin-bottom: 25px; padding: 15px; background: #ecf0f1; border-radius: 5px; text-align: center; }
        .menu a { text-decoration: none; background: #2980b9; color: white; padding: 9px 16px; margin: 3px 5px; border-radius: 4px; font-weight: bold; font-size: 13px; display: inline-block; }
        .menu a:hover { background: #34495e; }
        .menu a.tab-activa { background: #16a085; }
        .menu a.btn-excel { background: #27ae60; }
        .menu a.btn-excel:hover { background: #219653; }
        .menu a.btn-volver { background: #34495e; }

        .kpis-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 25px; }
        .kpi-card { background: #f8f9fa; border: 1px solid #d5dbdb; border-top: 4px solid #2980b9; border-radius: 6px; padding: 14px; text-align: center; }
        .kpi-card.verde { border-top-color: #27ae60; background: #f4fbf7; }
        .kpi-card.naranja { border-top-color: #e67e22; background: #fef9f3; }
        .kpi-card.morado { border-top-color: #8e44ad; background: #faf5fc; }
        .kpi-title { font-size: 12px; font-weight: bold; color: #566573; text-transform: uppercase; display: block; margin-bottom: 6px; }
        .kpi-main { font-size: 22px; font-weight: bold; color: #2c3e50; display: block; }
        .kpi-sub { font-size: 12px; color: #1b4f72; font-weight: bold; margin-top: 4px; display: block; }

        .sedes-box { background: #ebf5fb; border: 1px solid #aed6f1; border-radius: 6px; padding: 15px 20px; margin-bottom: 25px; }
        .sedes-box h3 { margin: 0 0 12px 0; color: #1b4f72; font-size: 16px; }

        .grid-grupos { display: flex; flex-wrap: wrap; gap: 18px; justify-content: center; margin-top: 15px; }
        .card-grupo { background: #f8f9fa; border: 2px solid #d5dbdb; border-radius: 8px; padding: 16px; width: 280px; text-align: center; }
        .card-grupo h4 { margin: 0 0 5px 0; color: #2c3e50; font-size: 16px; }
        .card-grupo p { margin: 3px 0; font-size: 12px; color: #566573; }
        .puntos-mini { background: white; border: 1px solid #d5dbdb; border-radius: 5px; padding: 8px; margin: 10px 0; font-size: 12px; text-align: left; line-height: 1.5; }
        .cupo-badge { display: inline-block; background: #2c3e50; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; margin-bottom: 8px; }
        .btn-ver { display: inline-block; text-decoration: none; background: #2980b9; color: white; padding: 7px 15px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .btn-ver:hover { background: #1f618d; }

        .banner-grupo { background: #ebf5fb; border: 2px solid #2980b9; border-radius: 8px; padding: 16px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .banner-grupo h3 { margin: 0 0 5px 0; color: #1b4f72; text-align: left; }
        .banner-grupo p { margin: 3px 0; font-size: 13px; color: #34495e; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; background: white; }
        th, td { border: 1px solid #bdc3c7; padding: 9px; text-align: center; vertical-align: middle; }
        th { background: #2c3e50; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }

        .badge { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-block; }
        .bg-verde { background: #d4efdf; color: #145a32; }
        .bg-amarillo { background: #fef9e7; color: #b7950b; }
        .bg-rojo { background: #fadbd8; color: #78281f; }
        .bg-azul { background: #d6eaf8; color: #1b4f72; }
        .bg-morado { background: #ebdef0; color: #5b2c6f; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Taller de Diálogos de Calidad</h1>

        <div class="menu">
            <a href="index.php">← Menú Principal</a>
            <a href="reportes.php?vista=grupos" class="<?php echo ($vista_activa=='grupos' && $id_grupo_sel==0) ? 'tab-activa' : ''; ?>">
                🏢 Reporte por Grupo y Sede
            </a>
            <a href="reportes.php?vista=entrenadores" class="<?php echo ($vista_activa=='entrenadores') ? 'tab-activa' : ''; ?>">
                🎓 Consolidado Entrenadores
            </a>
            <a href="reportes.php?vista=bajas" class="<?php echo ($vista_activa=='bajas') ? 'tab-activa' : ''; ?>">
                ⚠️ Bajas y Reasignados (<?php echo $total_bajas; ?>)
            </a>
            <a href="reportes.php?exportar=excel" class="btn-excel">
                📥 Descargar Todo en Excel
            </a>
        </div>

        <h2>📊 Módulo 5: Estadísticas Ejecutivas y Control por Grupo</h2>

        <div class="kpis-grid">
            <div class="kpi-card verde">
                <span class="kpi-title">Asistencia Efectiva Global</span>
                <span class="kpi-main"><?php echo $asistieron_total; ?> / <?php echo $total_inscritos; ?></span>
                <span class="kpi-sub">
                    ✅ <?php echo $pct_asistencia_inscritos; ?>% Asistieron (<?php echo $pct_asistencia_confirmados; ?>% de confirmados)<br>
                    ❌ No asistieron: <?php echo $no_asistieron_total; ?> (<?php echo $pct_no_asistencia; ?>%)
                </span>
            </div>

            <div class="kpi-card">
                <span class="kpi-title">Invitaciones por Correo</span>
                <span class="kpi-main"><?php echo $correos_enviados; ?> / <?php echo $total_inscritos; ?></span>
                <span class="kpi-sub">
                    ✉️ <?php echo $pct_enviados; ?>% Enviados<br>
                    ⏳ Pendientes de envío: <?php echo $correos_pendientes; ?>
                </span>
            </div>

            <div class="kpi-card morado">
                <span class="kpi-title">Estatus de Confirmación</span>
                <span class="kpi-main"><?php echo $confirmados; ?> Confirmados</span>
                <span class="kpi-sub">
                    👍 Confirmaron: <?php echo $confirmados; ?> (<?php echo $pct_confirmados; ?>%)<br>
                    👎 No confirmaron: <?php echo $no_confirmados; ?> (<?php echo $pct_no_confirmados; ?>%) | Pend: <?php echo $pendientes_conf; ?>
                </span>
            </div>

            <div class="kpi-card naranja">
                <span class="kpi-title">Bajas y Reasignaciones</span>
                <span class="kpi-main"><?php echo $total_bajas; ?> Bajas | <?php echo $total_reasignados; ?> Reasig.</span>
                <span class="kpi-sub">
                    CCORH: <?php echo $bajas_ccorh; ?> | Entrenadores: <?php echo $bajas_entrenador; ?><br>
                    Reasignados a otro grupo: <?php echo $total_reasignados; ?>
                </span>
            </div>
        </div>

        <!-- Pestaña 1: Resumen por sede, puntos de reunión y detalle por grupo -->
        <?php if ($vista_activa == 'grupos'): ?>

            <?php if ($id_grupo_sel == 0): ?>
                <div class="sedes-box">
                    <h3>📍 Resumen Relevante por Sede y Puntos de Reunión</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Sede</th>
                                <th>Talleres Programados</th>
                                <th>Inscritos / Capacidad</th>
                                <th>Confirmados</th>
                                <th>✈️ Aeropuerto CDMX</th>
                                <th>🏢 Corporativo Coppel</th>
                                <th>Asistieron</th>
                                <th>% Asistencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res_sedes = mysqli_query($conexion, $sql_sedes);
                            while ($s = mysqli_fetch_assoc($res_sedes)):
                                $cap_sede = $s['num_grupos'] * 26;
                                $pct_asist_sede = ($s['inscritos'] > 0) ? round(($s['asistieron'] / $s['inscritos']) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td><b><?php echo htmlspecialchars($s['sede']); ?></b></td>
                                    <td><?php echo $s['num_grupos']; ?> grupo(s)</td>
                                    <td><b><?php echo $s['inscritos']; ?> / <?php echo $cap_sede; ?></b></td>
                                    <td><span class="badge bg-verde"><?php echo intval($s['confirmados']); ?></span></td>
                                    <td><span class="badge bg-morado"><?php echo intval($s['cdmx']); ?> personas</span></td>
                                    <td><span class="badge bg-azul"><?php echo intval($s['coppel']); ?> personas</span></td>
                                    <td><b><?php echo intval($s['asistieron']); ?></b></td>
                                    <td><b><?php echo $pct_asist_sede; ?>%</b></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <h3>🏢 Detalle Individual por Grupo</h3>
                <p style="text-align: center; color: #7f8c8d; font-size: 13px; margin-bottom: 15px;">
                    Consulta los puntos de reunión y métricas de cada grupo, o entra a su listado completo.
                </p>

                <div class="grid-grupos">
                    <?php
                    $sql_g_cards = "SELECT g.*,
                                           e1.nombre_entrenador AS t1, e2.nombre_entrenador AS t2, el.nombre_entrenador AS logi,
                                           COUNT(p.id_participante) AS inscritos,
                                           SUM(CASE WHEN c.confirmacion = 'Confirmó' THEN 1 ELSE 0 END) AS confirmados,
                                           SUM(CASE WHEN c.punto_reunion = 'Aeropuerto CDMX' THEN 1 ELSE 0 END) AS cdmx,
                                           SUM(CASE WHEN c.punto_reunion = 'Corporativo Coppel' THEN 1 ELSE 0 END) AS coppel,
                                           SUM(CASE WHEN c.asistencia_dia1 = 'Asistió' OR c.asistencia_dia2 = 'Asistió' THEN 1 ELSE 0 END) AS asistieron
                                    FROM grupos g
                                    LEFT JOIN entrenadores e1 ON g.id_entrenador_1 = e1.id_entrenador
                                    LEFT JOIN entrenadores e2 ON g.id_entrenador_2 = e2.id_entrenador
                                    LEFT JOIN entrenadores el ON g.id_entrenador_logistica = el.id_entrenador
                                    LEFT JOIN participantes_grupo p ON g.id_grupo = p.id_grupo AND p.estatus_inscripcion = 'Inscrito'
                                    LEFT JOIN control_entrenadores c ON p.id_participante = c.id_participante
                                    GROUP BY g.id_grupo
                                    ORDER BY g.fecha_inicio ASC";
                    $res_gc = mysqli_query($conexion, $sql_g_cards);

                    while ($gc = mysqli_fetch_assoc($res_gc)):
                        $pct_g = ($gc['inscritos'] > 0) ? round(($gc['asistieron'] / $gc['inscritos']) * 100, 1) : 0;
                    ?>
                        <div class="card-grupo">
                            <h4><?php echo htmlspecialchars($gc['nombre_grupo']); ?></h4>
                            <p><b>Sede:</b> <?php echo htmlspecialchars($gc['sede']); ?></p>
                            <p><b>Fechas:</b> <?php echo $gc['fecha_inicio']; ?> al <?php echo $gc['fecha_fin']; ?></p>

                            <div class="puntos-mini">
                                ✈️ <b>Aeropuerto CDMX:</b> <?php echo intval($gc['cdmx']); ?><br>
                                🏢 <b>Corporativo Coppel:</b> <?php echo intval($gc['coppel']); ?><br>
                                👍 <b>Confirmados:</b> <?php echo intval($gc['confirmados']); ?> / <?php echo $gc['inscritos']; ?><br>
                                ✅ <b>Asistieron:</b> <?php echo intval($gc['asistieron']); ?> (<b><?php echo $pct_g; ?>%</b>)
                            </div>

                            <div class="cupo-badge"><?php echo $gc['inscritos']; ?> / 26 inscritos</div><br>
                            <a class="btn-ver" href="reportes.php?vista=grupos&id_grupo=<?php echo $gc['id_grupo']; ?>">
                                Ver Listado del Grupo
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>

            <?php else: ?>
                <?php
                $res_ginfo = mysqli_query($conexion, "SELECT g.*, e1.nombre_entrenador AS t1, e2.nombre_entrenador AS t2, el.nombre_entrenador AS logi
                                                       FROM grupos g
                                                       LEFT JOIN entrenadores e1 ON g.id_entrenador_1 = e1.id_entrenador
                                                       LEFT JOIN entrenadores e2 ON g.id_entrenador_2 = e2.id_entrenador
                                                       LEFT JOIN entrenadores el ON g.id_entrenador_logistica = el.id_entrenador
                                                       WHERE g.id_grupo = $id_grupo_sel");
                $ginfo = mysqli_fetch_assoc($res_ginfo);
                ?>
                <div class="banner-grupo">
                    <div>
                        <h3>📋 <?php echo htmlspecialchars($ginfo['nombre_grupo']); ?> — Sede: <?php echo htmlspecialchars($ginfo['sede']); ?></h3>
                        <p><b>📅 Fechas:</b> Del <?php echo formatearFechaEspanol($ginfo['fecha_inicio']); ?> al <?php echo formatearFechaEspanol($ginfo['fecha_fin']); ?></p>
                        <p><b>🎓 Staff:</b> T1: <?php echo htmlspecialchars($ginfo['t1'] ?? 'N/D'); ?> | T2: <?php echo htmlspecialchars($ginfo['t2'] ?? 'N/D'); ?> | Logística: <?php echo htmlspecialchars($ginfo['logi'] ?? 'N/D'); ?></p>
                    </div>
                    <a href="reportes.php?vista=grupos" style="background:#34495e; color:white; text-decoration:none; padding:9px 16px; border-radius:4px; font-weight:bold; font-size:13px;">
                        ← Regresar a Todos los Grupos
                    </a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>No. Emp.</th>
                            <th>Participante</th>
                            <th>Región / Puesto</th>
                            <th>Invitación</th>
                            <th>Confirmación</th>
                            <th>Punto de Reunión</th>
                            <th>Asistencia Taller</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql_det = "SELECT p.*, 
                                           COALESCE(c.confirmacion, 'Pendiente') AS confirmacion,
                                           COALESCE(c.punto_reunion, 'Ninguno') AS punto_reunion,
                                           c.asistencia_dia1, c.asistencia_dia2, c.motivo_inasistencia
                                    FROM participantes_grupo p
                                    LEFT JOIN control_entrenadores c ON p.id_participante = c.id_participante
                                    WHERE p.id_grupo = $id_grupo_sel AND p.estatus_inscripcion = 'Inscrito'
                                    ORDER BY p.nombre_completo ASC";
                        $res_det = mysqli_query($conexion, $sql_det);
                        $num = 1;
                        while ($d = mysqli_fetch_assoc($res_det)):
                            $asistio_str = ($d['asistencia_dia1'] == 'Asistió' || $d['asistencia_dia2'] == 'Asistió') ? 'Asistió' : (($d['asistencia_dia1'] == 'Falta' || $d['asistencia_dia2'] == 'Falta') ? 'No asistió' : 'Pendiente');
                            $clase_asist = ($asistio_str == 'Asistió') ? 'bg-verde' : (($asistio_str == 'No asistió') ? 'bg-rojo' : 'bg-amarillo');
                            $clase_conf  = ($d['confirmacion'] == 'Confirmó') ? 'bg-verde' : (($d['confirmacion'] == 'No confirmó') ? 'bg-rojo' : 'bg-amarillo');
                        ?>
                            <tr>
                                <td><b><?php echo $num++; ?></b></td>
                                <td><?php echo htmlspecialchars($d['numero_empleado']); ?></td>
                                <td style="text-align:left;"><b><?php echo htmlspecialchars($d['nombre_completo']); ?></b></td>
                                <td><b><?php echo htmlspecialchars($d['region'] ?: '-'); ?></b><br><small><?php echo htmlspecialchars($d['puesto']); ?></small></td>
                                <td><span class="badge <?php echo ($d['invitacion']=='Enviada') ? 'bg-azul' : 'bg-amarillo'; ?>"><?php echo htmlspecialchars($d['invitacion']); ?></span></td>
                                <td><span class="badge <?php echo $clase_conf; ?>"><?php echo htmlspecialchars($d['confirmacion']); ?></span></td>
                                <td>
                                    <?php if ($d['punto_reunion'] != 'Ninguno' && $d['punto_reunion'] != ''): ?>
                                        <span class="badge bg-morado"><?php echo htmlspecialchars($d['punto_reunion']); ?></span>
                                    <?php else: ?>
                                        <span style="color:#95a5a6;">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?php echo $clase_asist; ?>"><?php echo $asistio_str; ?></span></td>
                                <td style="text-align:left; font-size:12px;"><?php echo htmlspecialchars($d['motivo_inasistencia'] ?? ''); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <!-- Pestaña 2: Tabla de cuántas veces participó cada entrenador -->
        <?php elseif ($vista_activa == 'entrenadores'): ?>
            <h3>🎓 Consolidado de Participación por Entrenador</h3>
            <p style="text-align: center; color: #7f8c8d; font-size: 13px; margin-bottom: 18px;">
                Conteo de talleres asignados a cada facilitador según su rol operativo.
            </p>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre del Entrenador</th>
                        <th>Instructor Titular 1</th>
                        <th>Instructor Titular 2</th>
                        <th>Apoyo Logística</th>
                        <th>Total Talleres Asignados</th>
                        <th>% de Participación en Talleres</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res_ent = mysqli_query($conexion, $sql_entrenadores);
                    $ne = 1;
                    while ($e = mysqli_fetch_assoc($res_ent)):
                        $tot_roles = $e['veces_t1'] + $e['veces_t2'] + $e['veces_log'];
                        $pct_part_ent = ($total_grupos > 0) ? round(($tot_roles / $total_grupos) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td><b><?php echo $ne++; ?></b></td>
                            <td style="text-align:left;"><b><?php echo htmlspecialchars($e['nombre_entrenador']); ?></b></td>
                            <td><span class="badge bg-azul"><?php echo $e['veces_t1']; ?> vez/veces</span></td>
                            <td><span class="badge bg-verde"><?php echo $e['veces_t2']; ?> vez/veces</span></td>
                            <td><span class="badge bg-morado"><?php echo $e['veces_log']; ?> vez/veces</span></td>
                            <td><b><?php echo $tot_roles; ?></b></td>
                            <td><b><?php echo $pct_part_ent; ?>%</b> de los grupos</td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        <!-- Pestaña 3: Historial completo de bajas y migraciones -->
        <?php elseif ($vista_activa == 'bajas'): ?>
            <h3 style="color:#c0392b;">⚠️ Historial de Bajas y Reasignaciones (CCORH y Entrenadores)</h3>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Grupo Original</th>
                        <th>Grupo Destino (Migra)</th>
                        <th>No. Emp.</th>
                        <th>Colaborador</th>
                        <th>Región / Puesto</th>
                        <th>Registrada Por</th>
                        <th>Motivo y Reportante</th>
                        <th>Fecha Baja</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res_bajas = mysqli_query($conexion, $sql_historial_bajas);
                    if ($res_bajas && mysqli_num_rows($res_bajas) > 0):
                        $nb = 1;
                        while ($b = mysqli_fetch_assoc($res_bajas)):
                            $es_ccorh = (stripos($b['eliminado_por'], 'CCORH') !== false);
                            $clase_rol = $es_ccorh ? 'bg-azul' : 'bg-morado';
                    ?>
                        <tr>
                            <td><b><?php echo $nb++; ?></b></td>
                            <td>
                                <b><?php echo htmlspecialchars($b['grupo_origen'] ?? 'N/D'); ?></b><br>
                                <small style="color:#566573;"><?php echo htmlspecialchars($b['sede_origen'] ?? ''); ?></small>
                            </td>
                            <td>
                                <?php if ($b['estatus_baja'] == 'Reasignado' && !empty($b['grupo_destino'])): ?>
                                    <span class="badge bg-verde">➡️ <?php echo htmlspecialchars($b['grupo_destino']); ?></span><br>
                                    <small style="color:#145a32;"><?php echo htmlspecialchars($b['sede_destino']); ?> (<?php echo $b['fecha_destino']; ?>)</small>
                                <?php else: ?>
                                    <span class="badge bg-amarillo">Sin reasignar</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($b['numero_empleado']); ?></td>
                            <td style="text-align:left;"><b><?php echo htmlspecialchars($b['nombre_completo']); ?></b></td>
                            <td><b><?php echo htmlspecialchars($b['region'] ?: '-'); ?></b><br><small><?php echo htmlspecialchars($b['puesto'] ?? ''); ?></small></td>
                            <td><span class="badge <?php echo $clase_rol; ?>"><?php echo htmlspecialchars($b['eliminado_por']); ?></span></td>
                            <td style="text-align:left; color:#c0392b;"><b><?php echo htmlspecialchars($b['motivo_baja']); ?></b></td>
                            <td><?php echo $b['fecha_baja']; ?></td>
                        </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr><td colspan="9" style="padding:25px; color:#7f8c8d;">No hay bajas registradas en el historial.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>

</body>
</html>