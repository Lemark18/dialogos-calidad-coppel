<?php
// Enlazo mi conexión a la base de datos para consultar el catálogo de entrenadores
include("conexion.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Entrenadores - Diálogos de Calidad</title>
    <!-- Estilos visuales del menú y la tabla del padrón de facilitadores -->
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #2c3e50; text-align: center; }
        .menu { margin-bottom: 20px; padding: 15px; background: #ecf0f1; border-radius: 5px; text-align: center; }
        .menu a { text-decoration: none; background: #2980b9; color: white; padding: 8px 15px; margin: 0 5px; border-radius: 4px; font-weight: bold; }
        .menu a:hover { background: #34495e; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #bdc3c7; padding: 10px; text-align: left; font-size: 13px; }
        th { background: #2980b9; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Taller de Diálogos de Calidad</h1>
        
        <div class="menu">
            <a href="index.php">Inicio</a>
            <a href="grupos.php">Ver Grupos</a>
            <a href="participantes.php">Participantes</a>
            <a href="entrenadores.php">Entrenadores</a>
            <a href="nuevo_grupo.php">Nuevo Grupo</a>
        </div>

        <h2>Padrón de Entrenadores / Universidad Corporativa Coppel</h2>
        <p>Control de facilitadores autorizados para la impartición de talleres.</p>

        <!-- Tabla que muestra todos los facilitadores dados de alta en la base de datos -->
        <table>
            <tr>
                <th>Entrenador</th>
                <th>No. Colaborador</th>
                <th>No. Centro</th>
                <th>Ciudad</th>
                <th>Estado</th>
                <th>Correo</th>
                <th>Teléfono</th>
            </tr>
            <?php
            // Consulto la tabla entrenadores y dibujo una fila por cada facilitador
            $resultado = mysqli_query($conexion, "SELECT * FROM entrenadores");
            
            if ($resultado && mysqli_num_rows($resultado) > 0) {
                while($row = mysqli_fetch_assoc($resultado)) {
                    echo "<tr>";
                    echo "<td>" . $row['nombre_entrenador'] . "</td>";
                    echo "<td>" . $row['numero_colaborador'] . "</td>";
                    echo "<td>" . $row['numero_centro'] . "</td>";
                    echo "<td>" . $row['ciudad'] . "</td>";
                    echo "<td>" . $row['estado'] . "</td>";
                    echo "<td>" . $row['correo'] . "</td>";
                    echo "<td>" . $row['telefono'] . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7' style='text-align:center;'>No hay entrenadores registrados todavía.</td></tr>";
            }
            ?>
        </table>
    </div>

</body>
</html>