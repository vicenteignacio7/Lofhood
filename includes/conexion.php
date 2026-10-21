<?php
$host = "localhost";
$user = "root";        // Usuario por defecto en XAMPP
$pass = "";            // Sin contraseña en XAMPP
$dbname = "tokiramen_bd";  // Tu base de datos local

$conn = new mysqli($host, $user, $pass, $dbname, 3309);


if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

// =========================================================
// 4) CONFIGURACIÓN DE ZONA HORARIA (CHILE)
// =========================================================

// 1. Forzar PHP a usar hora de Santiago
date_default_timezone_set('America/Santiago');

// 2. Función para mostrar "Hace X tiempo"
function tiempo_transcurrido($fecha_bd) {
    $tiempo_inicio = strtotime($fecha_bd);
    $tiempo_actual = time();
    $diferencia = $tiempo_actual - $tiempo_inicio;

    if ($diferencia < 60) {
        return "Hace menos de 1 min";
    } elseif ($diferencia < 3600) {
        $min = floor($diferencia / 60);
        return "Hace " . $min . " min";
    } elseif ($diferencia < 86400) {
        $horas = floor($diferencia / 3600);
        $restantes = floor(($diferencia % 3600) / 60);
        return "Hace " . $horas . "h " . $restantes . "m";
    } else {
        $dias = floor($diferencia / 86400);
        return "Hace " . $dias . " días";
    }
}
?>
