<?php
// Enlazo mi conexión a la base de datos y calculo los días del mes seleccionado para el calendario
include("conexion.php");

$mes  = isset($_GET['mes']) ? intval($_GET['mes']) : intval(date('m'));
$anio = isset($_GET['anio']) ? intval($_GET['anio']) : intval(date('Y'));

if ($mes < 1) { $mes = 12; $anio--; }
if ($mes > 12) { $mes = 1; $anio++; }

$mes_ant = $mes - 1; $anio_ant = $anio;
if ($mes_ant < 1) { $mes_ant = 12; $anio_ant--; }

$mes_sig = $mes + 1; $anio_sig = $anio;
if ($mes_sig > 12) { $mes_sig = 1; $anio_sig++; }

$nombres_meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$primer_dia_mes = mktime(0, 0, 0, $mes, 1, $anio);
$dias_en_mes    = date('t', $primer_dia_mes);
$dia_semana_inicio = date('N', $primer_dia_mes);

// Consulto los talleres programados que caen dentro de este mes junto con sus entrenadores
$fecha_inicio_mes = sprintf("%04d-%02d-01", $anio, $mes);
$fecha_fin_mes    = sprintf("%04d-%02d-%02d", $anio, $mes, $dias_en_mes);

$sql_grupos = "SELECT g.*, 
                      e1.nombre_entrenador AS nom1, 
                      e2.nombre_entrenador AS nom2, 
                      el.nombre_entrenador AS nom_log
               FROM grupos g
               LEFT JOIN entrenadores e1 ON g.id_entrenador_1 = e1.id_entrenador
               LEFT JOIN entrenadores e2 ON g.id_entrenador_2 = e2.id_entrenador
               LEFT JOIN entrenadores el ON g.id_entrenador_logistica = el.id_entrenador
               WHERE g.fecha_inicio <= '$fecha_fin_mes' AND g.fecha_fin >= '$fecha_inicio_mes'
               ORDER BY g.fecha_inicio ASC";

$res_grupos = mysqli_query($conexion, $sql_grupos);
$grupos_mes = [];
while ($g = mysqli_fetch_assoc($res_grupos)) {
    $grupos_mes[] = $g;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cronograma Visual - Módulo Entrenadores</title>
    <!-- Estilos visuales de la cuadrícula mensual y las tarjetas de cada grupo -->
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 1280px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #2c3e50; text-align: center; }

        .menu { margin-bottom: 20px; padding: 15px; background: #ecf0f1; border-radius: 5px; text-align: center; }
        .menu a { text-decoration: none; background: #2980b9; color: white; padding: 9px 16px; margin: 0 5px; border-radius: 4px; font-weight: bold; font-size: 13px; display: inline-block; }
        .menu a:hover { background: #34495e; }
        .menu a.btn-volver { background: #34495e; }

        .nav-mes { background: #ebf5fb; border: 1px solid #aed6f1; padding: 12px 20px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .nav-mes h3 { margin: 0; color: #1b4f72; font-size: 18px; }
        .btn-mes { text-decoration: none; background: #2980b9; color: white; padding: 7px 14px; border-radius: 4px; font-size: 13px; font-weight: bold; }
        .btn-mes:hover { background: #1f618d; }

        .calendario-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
        .dia-header { background: #2c3e50; color: white; text-align: center; padding: 9px; font-weight: bold; font-size: 13px; border-radius: 4px; }
        .celda-dia { border: 1px solid #d5dbdb; min-height: 115px; padding: 6px; border-radius: 4px; background: #ffffff; }
        .celda-vacia { background: #f2f4f4; border: 1px solid #e5e8e8; min-height: 115px; border-radius: 4px; }
        .num-dia { font-weight: bold; font-size: 12px; color: #2c3e50; text-align: right; margin-bottom: 5px; }

        .evento-grupo { background: #ebf5fb; border-left: 4px solid #2980b9; padding: 5px 6px; margin-bottom: 5px; border-radius: 3px; font-size: 11px; line-height: 1.35; color: #1b4f72; }
        .evento-grupo b { display: block; font-size: 11.5px; color: #154360; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Taller de Diálogos de Calidad</h1>

        <!-- Barra superior para regresar al menú principal o a los grupos asignados -->
        <div class="menu">
            <a href="index.php">← Menú Principal</a>
            <a href="mis_asignaciones.php" class="btn-volver">← Regresar a Mis Grupos Asignados</a>
        </div>

        <h2>📅 Cronograma Visual de Talleres (Consulta Entrenadores)</h2>
        <p style="text-align: center; color: #7f8c8d; font-size: 13px; margin-bottom: 20px;">
            Vista informativa de fechas, sedes y facilitadores asignados por mes.
        </p>

        <!-- Botones para cambiar entre el mes anterior y el siguiente -->
        <div class="nav-mes">
            <a class="btn-mes" href="cronograma_entrenadores.php?mes=<?php echo $mes_ant; ?>&anio=<?php echo $anio_ant; ?>">← Mes Anterior</a>
            <h3><?php echo $nombres_meses[$mes] . " " . $anio; ?></h3>
            <a class="btn-mes" href="cronograma_entrenadores.php?mes=<?php echo $mes_sig; ?>&anio=<?php echo $anio_sig; ?>">Mes Siguiente →</a>
        </div>

        <!-- Cuadrícula del calendario donde dibujo cada grupo dentro de los días que dura su taller -->
        <div class="calendario-grid">
            <div class="dia-header">Lunes</div>
            <div class="dia-header">Martes</div>
            <div class="dia-header">Miércoles</div>
            <div class="dia-header">Jueves</div>
            <div class="dia-header">Viernes</div>
            <div class="dia-header">Sábado</div>
            <div class="dia-header">Domingo</div>

            <?php
            // Relleno con cuadros vacíos antes de que empiece el día 1 del mes
            for ($v = 1; $v < $dia_semana_inicio; $v++) {
                echo "<div class='celda-vacia'></div>";
            }

            // Recorro cada día del mes y pinto los talleres que caen en esa fecha
            for ($dia = 1; $dia <= $dias_en_mes; $dia++) {
                $fecha_actual = sprintf("%04d-%02d-%02d", $anio, $mes, $dia);
                echo "<div class='celda-dia'>";
                echo "<div class='num-dia'>$dia</div>";

                foreach ($grupos_mes as $g) {
                    if ($fecha_actual >= $g['fecha_inicio'] && $fecha_actual <= $g['fecha_fin']) {
                        echo "<div class='evento-grupo'>";
                        echo "<b>" . htmlspecialchars($g['nombre_grupo']) . "</b>";
                        echo "📍 " . htmlspecialchars($g['sede']) . "<br>";
                        echo "👤 T1: " . htmlspecialchars($g['nom1'] ?? 'N/D') . "<br>";
                        echo "👤 T2: " . htmlspecialchars($g['nom2'] ?? 'N/D') . "<br>";
                        echo "📋 Log: " . htmlspecialchars($g['nom_log'] ?? 'N/D');
                        echo "</div>";
                    }
                }

                echo "</div>";
            }
            ?>
        </div>
    </div>

</body>
</html>