<?php
// Conectamos a la base de datos
require __DIR__ . "/includes/conexion.php";

// ID de la publicación a mover
$id_publicacion = 121; 

// Coordenadas de Puerto Montt
$lat_lejos = -41.469298;
$lon_lejos = -72.942449;

// Ejecutamos la actualización
$sql = "UPDATE publicaciones 
        SET latitud = $lat_lejos, 
            longitud = $lon_lejos 
        WHERE id = $id_publicacion";

if ($conn->query($sql)) {
    echo "<h1>✅ ¡Listo!</h1>";
    echo "<p>La publicación ID <b>$id_publicacion</b> ('k paso') se ha movido a Puerto Montt.</p>";
    echo "<p>Coordenadas nuevas: $lat_lejos, $lon_lejos</p>";
    
    echo "<hr>";
    echo "<a href='index.php'>Volver al Inicio (Verás que ya no está)</a>";
} else {
    echo "<h1>❌ Error</h1>";
    echo "<p>No se pudo actualizar: " . $conn->error . "</p>";
}
?>