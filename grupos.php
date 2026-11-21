<?php
// Enlazo mi conexión a la base de datos y verifico si piden eliminar un grupo
include("conexion.php");

$mensaje_alerta = "";
$mensaje_exito = "";

// Antes de borrar un grupo reviso que no tenga participantes inscritos para no perder sus datos
if (isset($_GET['eliminar_grupo'])) {
    $id_g_eliminar = intval($_GET['eliminar_grupo']);

    $sql_check_p = "SELECT COUNT(*) as total FROM participantes_grupo WHERE id_grupo = '$id_g_eliminar' AND estatus_inscripcion = 'Inscrito'";
    $res_check_p = mysqli_query($conexion, $sql_check_p);
    $data_p = mysqli_fetch_assoc($res_check_p);

    if ($data_p['total'] > 0) {
        $mensaje_alerta = "<div class='alerta-err'>
            ⚠️ <b>No se puede eliminar el grupo:</b> Este taller cuenta con <b>" . $data_p['total'] . " participantes inscritos</b>. Primero reasigna a los participantes a otro grupo antes de eliminarlo.
        </div>";
    } else {
        $sql_del = "DELETE FROM grupos WHERE id_grupo = '$id_g_eliminar'";
        if (mysqli_query($conexion, $sql_del)) {
            header("Location: grupos.php?eliminado=1");
            exit();
        }
    }
}

if (isset($_GET['eliminado'])) {
    $mensaje_exito = "<div class='alerta-msg'>
        ✅ El grupo ha sido eliminado correctamente.
    </div>";
}

// Consulto todos los grupos ordenados por fecha de inicio junto con su total de inscritos
$sql_grupos = "SELECT g.*, 
               (SELECT COUNT(*) FROM participantes_grupo p 
                WHERE p.id_grupo = g.id_grupo AND p.estatus_inscripcion = 'Inscrito') AS total_inscritos
               FROM grupos g 
               ORDER BY g.fecha_inicio ASC";
$res_grupos = mysqli_query($conexion, $sql_grupos);
$grupos = [];
while($g = mysqli_fetch_assoc($res_grupos)) {
    $grupos[] = $g;
}

// Cargo los inscritos activos para que el buscador filtre al instante en pantalla
$sql_busq = "SELECT p.nombre_completo, p.numero_empleado, p.region, 
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

$id_grupo_activo = isset($_GET['id_grupo']) ? intval($_GET['id_grupo']) : 0;

// Función sencilla para mostrar las fechas del taller en español
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
    <title>Módulo Logística - Gestión de Grupos y Staff</title>
    <!-- Estilos visuales de tarjetas, buscador, banner del grupo y tabla de 26 lugares -->
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 1180px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
        h1, h2, h3 { color: #2c3e50; text-align: center; }
        
        .menu { margin-bottom: 25px; padding: 15px; background: #ecf0f1; border-radius: 5px; text-align: center; }
        .menu a { text-decoration: none; background: #2980b9; color: white; padding: 9px 16px; margin: 0 5px; border-radius: 4px; font-weight: bold; font-size: 13px; display: inline-block; }
        .menu a:hover { background: #34495e; }
        .menu a.btn-volver-grupos { background: #34495e; }
        .menu a.btn-volver-grupos:hover { background: #2c3e50; }
        .menu a.btn-nuevo { background: #27ae60; }
        .menu a.btn-nuevo:hover { background: #219653; }

        .alerta-msg { background: #d4efdf; color: #145a32; padding: 12px; border-radius: 4px; text-align: center; margin-bottom: 20px; font-size: 14px; border: 1px solid #a9dfbf; font-weight: bold; }
        .alerta-err { background: #fadbd8; color: #78281f; padding: 12px; border-radius: 4px; text-align: center; margin-bottom: 20px; font-size: 14px; border: 1px solid #f5b7b1; font-weight: bold; }

        .buscador-box { background: #ebf5fb; border: 1px solid #aed6f1; border-radius: 6px; padding: 15px 20px; margin-bottom: 22px; }
        .buscador-box label { display: block; font-weight: bold; color: #1b4f72; font-size: 13px; margin-bottom: 8px; }
        .buscador-box input { width: 100%; box-sizing: border-box; padding: 10px 12px; border: 2px solid #2980b9; border-radius: 5px; font-size: 14px; }
        .resultados-busq { margin-top: 10px; display: none; background: white; border: 1px solid #bdc3c7; border-radius: 5px; max-height: 260px; overflow-y: auto; }
        .item-res { padding: 10px 14px; border-bottom: 1px solid #ecf0f1; display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
        .item-res:last-child { border-bottom: none; }
        .item-res:hover { background: #f8f9fa; }
        .btn-ir-grupo { background: #2980b9; color: white; text-decoration: none; padding: 5px 12px; border-radius: 4px; font-weight: bold; font-size: 12px; }
        .btn-ir-grupo:hover { background: #1f618d; }

        .grid-grupos { display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; margin-bottom: 25px; }
        .card-grupo { background: #f8f9fa; border: 2px solid #d5dbdb; border-radius: 8px; padding: 15px; width: 240px; text-align: center; }
        .card-grupo h4 { margin: 0 0 6px 0; color: #2c3e50; font-size: 16px; }
        .card-grupo p { margin: 4px 0; font-size: 12px; color: #566573; }
        .cupo-badge { display: inline-block; background: #2c3e50; color: white; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; margin: 8px 0; }
        .btn-ver { display: inline-block; text-decoration: none; background: #2980b9; color: white; padding: 6px 14px; border-radius: 4px; font-size: 12px; font-weight: bold; margin-top: 5px; }
        .btn-ver:hover { background: #1f618d; }

        .banner-grupo { background: #ebf5fb; border: 2px solid #2980b9; border-radius: 8px; padding: 18px 22px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .banner-grupo h3 { margin: 0 0 6px 0; color: #1b4f72; text-align: left; font-size: 19px; }
        .banner-grupo p { margin: 3px 0; font-size: 13px; color: #34495e; }
        .staff-lista { margin-top: 8px; font-size: 13px; color: #1b4f72; line-height: 1.5; }

        .acciones-grupo { display: flex; flex-direction: column; gap: 8px; min-width: 155px; }
        .btn-modificar-g { background: #f39c12; color: white; padding: 8px 14px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 12px; text-align: center; }
        .btn-modificar-g:hover { background: #d68910; }
        .btn-eliminar-g { background: #e74c3c; color: white; padding: 8px 14px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 12px; text-align: center; }
        .btn-eliminar-g:hover { background: #c0392b; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; background: white; }
        th, td { border: 1px solid #bdc3c7; padding: 8px; text-align: center; }
        th { background: #2c3e50; color: white; }
        tr.fila-vacia { background-color: #fbfcfc; color: #95a5a6; font-style: italic; }
    </style>

    <!-- Filtro en JavaScript para buscar participantes inscritos sin recargar la página -->
    <script>
    const participantesLogistica = <?php echo json_encode($lista_busqueda, JSON_UNESCAPED_UNICODE); ?>;

    function filtrarParticipantesLog(texto) {
        const cont = document.getElementById('resultadosBuscadorLog');
        const q = texto.trim().toLowerCase();

        if (q.length < 2) {
            cont.style.display = 'none';
            cont.innerHTML = '';
            return;
        }

        const res = participantesLogistica.filter(p =>
            (p.nombre_completo && p.nombre_completo.toLowerCase().includes(q)) ||
            (p.numero_empleado && p.numero_empleado.toLowerCase().includes(q)) ||
            (p.nombre_grupo && p.nombre_grupo.toLowerCase().includes(q)) ||
            (p.region && p.region.toLowerCase().includes(q))
        );

        cont.style.display = 'block';
        if (res.length === 0) {
            cont.innerHTML = '<div style="padding:12px; color:#7f8c8d; text-align:center;">No se encontraron participantes inscritos con ese criterio.</div>';
            return;
        }

        let html = '';
        res.slice(0, 20).forEach(p => {
            html += `
                <div class="item-res">
                    <div>
                        <b>${p.nombre_completo}</b> <span style="color:#7f8c8d;">(No. Emp: ${p.numero_empleado})</span><br>
                        <small style="color:#1b4f72;"><b>${p.nombre_grupo}</b> — Sede: ${p.sede} (${p.fecha_inicio} al ${p.fecha_fin}) | Región: ${p.region || 'N/D'}</small>
                    </div>
                    <a href="grupos.php?id_grupo=${p.id_grupo}" class="btn-ir-grupo">Administrar Grupo →</a>
                </div>
            `;
        });
        cont.innerHTML = html;
    }
    </script>
</head>
<body>

    <div class="container">
        <h1>Taller de Diálogos de Calidad</h1>
        
        <!-- Menú superior de logística para programar grupos nuevos o abrir el cronograma visual -->
        <div class="menu">
            <a href="index.php">← Menú Principal</a>
            
            <?php if ($id_grupo_activo != 0): ?>
                <a href="grupos.php" class="btn-volver-grupos">← Ver Grupos</a>
            <?php endif; ?>

            <a href="nuevo_grupo.php" class="btn-nuevo">+ Programar Nuevo Grupo</a>
            <a href="cronograma_visual.php">📅 Cronograma Visual</a>
        </div>

        <h2>Módulo Logística: Coordinación de Staff y Grupos</h2>

        <?php 
            echo $mensaje_alerta; 
            echo $mensaje_exito; 
        ?>

        <?php if (empty($grupos)): ?>
            <div style="text-align: center; padding: 40px; color: #7f8c8d; font-size: 15px;">
                No hay grupos programados todavía. Utiliza el botón <b>"+ Programar Nuevo Grupo"</b> para comenzar.
            </div>
        <?php else: ?>

            <!-- Pantalla inicial con el buscador y las tarjetas de los grupos programados -->
            <?php if ($id_grupo_activo == 0): ?>

                <div class="buscador-box">
                    <label for="inputBuscadorLog">🔎 Buscar Participante (Inscritos activos):</label>
                    <input type="text" id="inputBuscadorLog" onkeyup="filtrarParticipantesLog(this.value)" 
                           placeholder="Escribe nombre, número de empleado, grupo o región...">
                    <div id="resultadosBuscadorLog" class="resultados-busq"></div>
                </div>

                <div class="grid-grupos">
                    <?php foreach ($grupos as $g): ?>
                        <div class="card-grupo">
                            <h4><?php echo htmlspecialchars($g['nombre_grupo']); ?></h4>
                            <p><b>Sede:</b> <?php echo htmlspecialchars($g['sede']); ?></p>
                            <p><b>Fechas:</b> <?php echo $g['fecha_inicio']; ?> al <?php echo $g['fecha_fin']; ?></p>
                            <div class="cupo-badge"><?php echo $g['total_inscritos']; ?> / 26 inscritos</div><br>
                            <a class="btn-ver" href="grupos.php?id_grupo=<?php echo $g['id_grupo']; ?>">
                                Administrar Grupo
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p style="text-align: center; color: #7f8c8d; margin-top: 10px;">👆 Haz clic en <b>"Administrar Grupo"</b> para consultar su staff asignado y los 26 lugares.</p>
            <?php else: ?>

                <!-- Cuando seleccionan un grupo, muestro sus entrenadores asignados y la tabla de 26 lugares -->
                <?php
                $sql_info = "SELECT g.*, 
                                    e1.nombre_entrenador AS nom1, 
                                    e2.nombre_entrenador AS nom2, 
                                    el.nombre_entrenador AS nom_log
                             FROM grupos g
                             LEFT JOIN entrenadores e1 ON g.id_entrenador_1 = e1.id_entrenador
                             LEFT JOIN entrenadores e2 ON g.id_entrenador_2 = e2.id_entrenador
                             LEFT JOIN entrenadores el ON g.id_entrenador_logistica = el.id_entrenador
                             WHERE g.id_grupo = '$id_grupo_activo'";
                $res_info = mysqli_query($conexion, $sql_info);
                $grupo_info = mysqli_fetch_assoc($res_info);

                $sql_part = "SELECT * FROM participantes_grupo 
                             WHERE id_grupo = '$id_grupo_activo' AND estatus_inscripcion = 'Inscrito'
                             ORDER BY id_participante ASC";
                $res_part = mysqli_query($conexion, $sql_part);
                
                $participantes = [];
                while($p = mysqli_fetch_assoc($res_part)) {
                    $participantes[] = $p;
                }

                $total_inscritos = count($participantes);
                $cupo_total = 26;
                ?>

                <?php if ($grupo_info): ?>
                <div class="banner-grupo">
                    <div>
                        <h3>📋 <?php echo htmlspecialchars($grupo_info['nombre_grupo']); ?> — Sede: <?php echo htmlspecialchars($grupo_info['sede']); ?></h3>
                        <p><b>📅 Periodo:</b> Del <?php echo formatearFechaEspanol($grupo_info['fecha_inicio']); ?> al <?php echo formatearFechaEspanol($grupo_info['fecha_fin']); ?></p>
                        <div class="staff-lista">
                            <div><b>Entrenador Titular 1:</b> <?php echo htmlspecialchars($grupo_info['nom1'] ?? 'Sin asignar'); ?></div>
                            <div><b>Entrenador Titular 2:</b> <?php echo htmlspecialchars($grupo_info['nom2'] ?? 'Sin asignar'); ?></div>
                            <div><b>Apoyo Logística:</b> <?php echo htmlspecialchars($grupo_info['nom_log'] ?? 'Sin asignar'); ?></div>
                        </div>
                    </div>

                    <div style="text-align: right;">
                        <div class="cupo-badge" style="font-size: 13px; padding: 6px 14px; margin-bottom: 10px;">
                            Ocupación: <?php echo $total_inscritos; ?> / 26 lugares
                        </div>
                        <div class="acciones-grupo">
                            <a href="editar_grupo.php?id=<?php echo $id_grupo_activo; ?>" class="btn-modificar-g">✏️ Modificar Grupo</a>
                            <a href="grupos.php?id_grupo=<?php echo $id_grupo_activo; ?>&eliminar_grupo=<?php echo $id_grupo_activo; ?>" class="btn-eliminar-g" onclick="return confirm('¿Estás seguro de eliminar este grupo?');">🗑️ Eliminar Grupo</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 45px;">#</th>
                            <th>No. Empleado</th>
                            <th>Nombre Completo</th>
                            <th>Región</th>
                            <th>Puesto</th>
                            <th>Centro</th>
                            <th>Correo / Teléfono</th>
                            <th>Estatus</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        for ($i = 1; $i <= $cupo_total; $i++) {
                            echo "<tr>";
                            echo "<td><b>" . $i . "</b></td>";

                            if (isset($participantes[$i - 1])) {
                                $p = $participantes[$i - 1];
                                echo "<td><b>" . htmlspecialchars($p['numero_empleado']) . "</b></td>";
                                echo "<td style='text-align: left; padding-left: 10px;'><b>" . htmlspecialchars($p['nombre_completo']) . "</b></td>";
                                echo "<td><b>" . htmlspecialchars($p['region'] ?: '-') . "</b></td>";
                                echo "<td>" . htmlspecialchars($p['puesto']) . "</td>";
                                echo "<td>" . htmlspecialchars($p['numero_centro']) . "</td>";
                                echo "<td>" . htmlspecialchars($p['correo']) . "<br><small>" . htmlspecialchars($p['celular']) . "</small></td>";
                                echo "<td><span style='color: #2980b9; font-size: 11px; font-weight: bold;'>Inscrito</span></td>";
                            } else {
                                echo "<td colspan='7' class='fila-vacia' style='text-align: left; padding-left: 15px;'>Espacio disponible</td>";
                            }
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>

            <?php endif; ?>

        <?php endif; ?>
    </div>

</body>
</html>