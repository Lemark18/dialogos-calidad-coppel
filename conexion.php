<?php
// Aquí pongo los datos de mi servidor local (XAMPP) y el nombre de mi base de datos
$servidor = "localhost";
$usuario = "root";
$password = "";
$base_datos = "dialogos_calidad";

// En esta línea abro la conexión hacia MySQL
$conexion = mysqli_connect($servidor, $usuario, $password, $base_datos);

// Si falla la conexión, detengo la página y muestro el mensaje de error
if (!$conexion) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}

// Configuro UTF-8 para que se guarden y lean bien los acentos y las eñes
mysqli_set_charset($conexion, "utf8mb4");
?>