<?php
include("includes/conexion.php");

$sql = "SELECT * FROM catalogo_productos LIMIT 5";
$result = $conn->query($sql);

if (!$result) {
    die("❌ Error en la consulta: " . $conn->error);
}

if ($result->num_rows > 0) {
    echo "<h2>✅ Productos en la base de datos:</h2><ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . $row['nombre'] . " - $" . $row['precio'] . " (" . $row['categoria'] . ")</li>";
    }
    echo "</ul>";
} else {
    echo "⚠️ No hay productos en la base de datos.";
}
?>
