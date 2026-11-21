<?php
// Enlazo mi conexión a la base de datos y obtengo el mes y año seleccionados para el calendario
include("conexion.php");

$mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('n');
$anio = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');

// Calculo cuántos días tiene el mes y en qué día de la semana empieza
$primer_dia = mktime(0, 0, 0, $mes, 1, $anio);
$dias_en_mes = date('t', $primer_dia);
$dia_semana_inicio = date('w', $primer_dia);
$dia_semana_inicio = ($dia_semana_inicio == 0) ? 7 : $dia_semana_inicio;

$nombre_meses = [
    1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril", 
    5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto", 
    9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
];

$mes_ant = ($mes == 1) ? 12 : $mes - 1;
$anio_ant = ($mes == 1) ? $anio - 1 : $anio;
$mes_sig = ($mes == 12) ? 1 : $mes + 1;
$anio_sig = ($mes == 12) ? $anio + 1 : $anio;

// Consulto los talleres programados en ese mes junto con sus entrenadores y apoyo de logística
$fecha_inicio_mes = "$anio-" . str_pad($mes, 2, "0", STR_PAD_LEFT) . "-01";
$fecha_fin_mes = "$anio-" . str_pad($mes, 2, "0", STR_PAD_LEFT) . "-$dias_en_mes";

$sql_grupos = "SELECT g.*, 
               e1.nombre_entrenador AS nom1, 
               e2.nombre_entrenador AS nom2, 
               el.nombre_entrenador AS nom_log
               FROM grupos g
               LEFT JOIN entrenadores e1 ON g.id_entrenador_1 = e1.id_entrenador
               LEFT JOIN entrenadores e2 ON g.id_entrenador_2 = e2.id_entrenador
               LEFT JOIN entrenadores el ON g.id_entrenador_logistica = el.id_entrenador
               WHERE (g.fecha_inicio <= '$fecha_fin_mes' AND g.fecha_fin >= '$fecha_inicio_mes')
               ORDER BY g.fecha_inicio ASC";
$res_grupos = mysqli_query($conexion, $sql_grupos);

// Agrupo los talleres día por día desde su fecha de inicio hasta su fecha de fin
$eventos_por_dia = [];
if ($res_grupos) {
    while ($g = mysqli_fetch_assoc($res_grupos)) {
        $inicio = strtotime($g['fecha_inicio']);
        $fin = strtotime($g['fecha_fin']);
        
        for ($d = $inicio; $d <= $fin; $d += 86400) {
            $fecha_str = date('Y-m-d', $d);
            $eventos_por_dia[$fecha_str][] = $g;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cronograma Visual de Grupos - Logística</title>
    <!-- Estilos visuales de la cuadrícula del calendario mensual y las tarjetas de talleres -->
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 10px; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; text-align: center; margin: 5px 0 15px 0; font-size: 20px; }
        
        .menu { 
            margin-bottom: 12px; 
            padding: 8px; 
            background: #ecf0f1; 
            border-radius: 6px; 
            text-align: center; 
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .menu a { 
            text-decoration: none; 
            background: #2980b9; 
            color: white; 
            padding: 6px 12px; 
            border-radius: 4px; 
            font-weight: bold; 
            font-size: 12px;
            display: inline-block;
        }
        .menu a.btn-grupos { background: #2980b9; }
        .menu a:hover { opacity: 0.9; }

        .nav-mes {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            background: #ebf5fb;
            padding: 8px 15px;
            border-radius: 6px;
        }
        .nav-mes a {
            text-decoration: none;
            background: #2980b9;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
        }
        .nav-mes h3 { margin: 0; color: #2c3e50; font-size: 16px; }

        .calendario-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
        }
        .dia-encabezado {
            background: #2c3e50;
            color: white;
            padding: 6px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            border-radius: 3px;
        }
        .celda-dia {
            background: #fdfefe;
            border: 1px solid #dcdde1;
            min-height: 95px;
            max-height: 120px;
            overflow-y: auto;
            padding: 4px;
            border-radius: 3px;
        }
        .celda-vacia { background: #f4f6f9; border: none; }
        .num-dia { font-weight: bold; color: #2c3e50; font-size: 12px; margin-bottom: 2px; text-align: right; }

        .tarjeta-evento {
            background: #ebf5fb;
            border-left: 3px solid #2980b9;
            padding: 4px 6px;
            margin-bottom: 4px;
            border-radius: 2px;
            font-size: 10px;
            color: #2c3e50;
        }
        .tarjeta-evento a { text-decoration: none; color: #1b4f72; font-weight: bold; display: block; }
        .tarjeta-evento .detalle { font-size: 9px; color: #566573; display: block; margin-top: 1px; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Taller de Diálogos de Calidad</h1>
        
        <div class="menu">
            <a href="grupos.php" class="btn-grupos">📋 Ver Grupos</a>
        </div>
        
        <!-- Botones para moverme entre mes anterior y mes siguiente -->
        <div class="nav-mes">
            <a href="cronograma_visual.php?mes=<?php echo $mes_ant; ?>&anio=<?php echo $anio_ant; ?>">← Mes Anterior</a>
            <h3><?php echo $nombre_meses[$mes] . " " . $anio; ?></h3>
            <a href="cronograma_visual.php?mes=<?php echo $mes_sig; ?>&anio=<?php echo $anio_sig; ?>">Mes Siguiente →</a>
        </div>

        <div class="calendario-grid">
            <div class="dia-encabezado">Lunes</div>
            <div class="dia-encabezado">Martes</div>
            <div class="dia-encabezado">Miércoles</div>
            <div class="dia-encabezado">Jueves</div>
            <div class="dia-encabezado">Viernes</div>
            <div class="dia-encabezado">Sábado</div>
            <div class="dia-encabezado">Domingo</div>

            <?php
            // Relleno con cuadros vacíos antes del día 1 del mes
            for ($i = 1; $i < $dia_semana_inicio; $i++) {
                echo "<div class='celda-dia celda-vacia'></div>";
            }

            // Dibujo cada día del mes con los talleres que caen en esa fecha
            for ($dia = 1; $dia <= $dias_en_mes; $dia++) {
                $fecha_actual_loop = "$anio-" . str_pad($mes, 2, "0", STR_PAD_LEFT) . "-" . str_pad($dia, 2, "0", STR_PAD_LEFT);
                
                echo "<div class='celda-dia'>";
                echo "<div class='num-dia'>$dia</div>";

                if (isset($eventos_por_dia[$fecha_actual_loop])) {
                    foreach ($eventos_por_dia[$fecha_actual_loop] as $ev) {
                        $id_g = $ev['id_grupo'];
                        $nom_g = htmlspecialchars($ev['nombre_grupo']);
                        $sede = htmlspecialchars($ev['sede']);
                        $t1 = htmlspecialchars($ev['nom1'] ?? 'Sin asignar');
                        $t2 = htmlspecialchars($ev['nom2'] ?? 'Sin asignar');
                        $log = htmlspecialchars($ev['nom_log'] ?? 'Sin asignar');

                        echo "<div class='tarjeta-evento'>";
                        echo "<a href='grupos.php?id_grupo=$id_g'>$nom_g</a>";
                        echo "<span class='detalle'>📍 $sede</span>";
                        echo "<span class='detalle'>👨‍🏫 T1: $t1</span>";
                        echo "<span class='detalle'>🤝 T2: $t2</span>";
                        echo "<span class='detalle'>📋 Log: $log</span>";
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